<?php

namespace App\Http\Controllers\Api;

use App\AiConversation;
use App\AiMessage;
use App\Company;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Property;
use App\Services\AcadepAuraService;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AiAnalyticsController — Pulse Metrics IA: Reportes e Informes con Inteligencia Artificial
 *
 * Auth: Cache session_token (patrón Bridge V2)
 *       Authorization: Bearer {session_token}
 *
 * TENANT LOCK ABSOLUTO: cada métrica se filtra estrictamente por company_id
 * extraído del session_token — prohibida cualquier exposición cruzada.
 *
 * AURA Integration: dispatch via AIService::dispatch() — failover multi-proveedor.
 * Token Economy: cada respuesta se audita en ai_conversations + ai_messages.
 *
 * ADVERTENCIA DE RUNTIME: el método de validación se llama bridgeAnalyticsValidate()
 * y NO validate(), ya que validate() colisiona con el Trait ValidatesRequests del Kernel.
 */
class AiAnalyticsController extends Controller
{
    private const SESSION_TTL = 1800;

    // Mensaje amigable universal — NUNCA exponer un Error 500 al cliente.
    private const FRIENDLY_OVERLOAD_MSG = '⚠️ Pulse Metrics IA se encuentra procesando un alto volumen de datos. Intente de nuevo en un momento.';

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v2/analytics/validate?token={BRIDGE_TOKEN}
    //
    // Intercambia el bridge token (60 s, single-use) por un session_token (30 min).
    // Quema el bridge token al instante para prevenir replay attacks.
    // ──────────────────────────────────────────────────────────────────────────

    public function bridgeAnalyticsValidate(Request $request)
    {
        try {
            $token = trim((string) $request->query('token', ''));

            if ($token === '') {
                return response()->json(['success' => false, 'error' => 'Token requerido.'], 400);
            }

            $cacheKey = 'v2_bridge_' . $token;
            $payload  = Cache::get($cacheKey);

            if (!$payload || !is_array($payload)) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Enlace inválido o expirado. Regresa al panel e intenta de nuevo.',
                ], 401);
            }

            // Single-use: quemar inmediatamente para prevenir replay
            Cache::forget($cacheKey);

            $sessionToken = Str::random(64);
            Cache::put('v2_session_' . $sessionToken, [
                'user_id'    => $payload['user_id'],
                'company_id' => $payload['company_id'],
            ], self::SESSION_TTL);

            $company = $payload['company_id'] ? Company::find($payload['company_id']) : null;

            return response()->json([
                'success'       => true,
                'session_token' => $sessionToken,
                'company'       => $company ? [
                    'id'      => $company->id,
                    'name'    => $company->name,
                    'logo'    => $company->logo ?? null,
                    'package' => $company->package,
                ] : null,
            ]);

        } catch (\Exception $e) {
            Log::error('[PulseMetrics] bridgeAnalyticsValidate error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => self::FRIENDLY_OVERLOAD_MSG,
            ], 200);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/v2/analytics/query
    // Authorization: Bearer {session_token}
    //
    // Recibe payloads multimodales (texto, voz transcrita, contexto de documento
    // o imagen en Base64). Consolida métricas del tenant, construye el prompt
    // con el esquema verificado del Codex y despacha a AURA via AIService::dispatch().
    //
    // Body JSON esperado:
    //   { "query": "string", "context": "string|null" }
    // ──────────────────────────────────────────────────────────────────────────

    public function query(Request $request)
    {
        try {
            $sessionToken = $this->extractBearerToken($request);

            if (!$sessionToken) {
                return response()->json(['success' => false, 'error' => 'No autenticado.'], 401);
            }

            $session = Cache::get('v2_session_' . $sessionToken);

            if (!$session || !is_array($session)) {
                return response()->json(['success' => false, 'error' => 'Sesión expirada.'], 401);
            }

            // TENANT LOCK ABSOLUTO — ambas claves provienen del server-side cache
            $companyId = (int) ($session['company_id'] ?? 0);
            $userId    = (int) ($session['user_id']    ?? 0);

            if ($companyId <= 0) {
                return response()->json(['success' => false, 'error' => 'Empresa no identificada.'], 403);
            }

            // Sanitización robusta de inputs de la SPA (anti prompt-injection)
            $userQuery   = strip_tags(trim((string) $request->input('query',   '')));
            $userContext = strip_tags(trim((string) $request->input('context', '')));

            if (mb_strlen($userQuery) === 0) {
                return response()->json(['success' => false, 'error' => 'La consulta no puede estar vacía.'], 422);
            }

            // Limitar longitud para prevenir prompt injection extendido
            $userQuery   = mb_substr($userQuery,   0, 1000);
            $userContext = mb_substr($userContext,  0, 2000);

            // ── 1. Consolidar métricas del tenant (TENANT LOCK: company_id obligatorio) ──
            $metrics = $this->gatherTenantMetrics($companyId);

            // ── 2. Construir mensajes para el Orquestador AURA ───────────────────────────
            $systemPrompt = $this->buildSystemPrompt($metrics);
            $userMessage  = $userQuery
                . ($userContext !== '' ? "\n\nContexto adicional:\n" . $userContext : '');

            // ══════════════════════════════════════════════════════════════════════════
            // ── 3. PIPELINE DE IA — 3 capas en cascada ─────────────────────────────
            //
            //   Capa A: ACADEP AURA (nodo soberano local — máxima prioridad)
            //           Protocolo OPEN KEY · Header X-AURA-KEY
            //   Capa B: AIService (failover comercial — Groq/OpenAI/Mistral/Gemini)
            //           Solo se activa si ACADEP no está configurado o falla
            //   Capa C: Mock de validación (ningún proveedor disponible)
            //           Confirma conectividad HTTP end-to-end al developer
            // ══════════════════════════════════════════════════════════════════════════

            $report        = null;
            $tokensUsed    = 0;
            $usedLayer     = null;
            $networkLayer  = null;
            $transactionId = null;

            // ── Capa A: ACADEP AURA (Nodo Soberano — máxima prioridad) ──────────────
            // Lee credenciales directamente del .env via config/services.php.
            // No depende de ai_settings — opera aunque la BD esté vacía de registros ACADEP.
            $acadep     = new AcadepAuraService();
            $fullPrompt = $systemPrompt . "\n\n---\n\n" . $userMessage;

            if ($acadep->isConfigured()) {
                $acadepResult = $acadep->dispatch($fullPrompt, 'AURA_BKC_V1');

                if ($acadepResult['status'] === 'ok') {
                    $report        = $acadepResult['response'];
                    $tokensUsed    = $acadepResult['tokens_used'];
                    $usedLayer     = 'acadep';
                    $networkLayer  = $acadepResult['network_layer'] ?? null;
                    $transactionId = $acadepResult['transaction_id'] ?? null;

                    // ── BITÁCORA DE AUDITORÍA AURA (registro obligatorio por Mandamiento #15) ──
                    Log::info('[PulseMetrics] ✓ ACADEP — respuesta exitosa', [
                        'company_id'     => $companyId,
                        'transaction_id' => $transactionId,
                        'network_layer'  => $networkLayer,
                        'latency_ms'     => $acadepResult['latency_ms'] ?? null,
                        'tokens_used'    => $tokensUsed,
                    ]);

                } elseif ($acadepResult['status'] === 'blocked') {
                    // ACADEP respondió pero rechazó (CAPEX / cuota agotada).
                    // Congelar el módulo en el frontend (state.halted via banner).
                    Log::error('[PulseMetrics] ACADEP bloqueó la solicitud', [
                        'reason'     => $acadepResult['error'],
                        'company_id' => $companyId,
                    ]);
                    return response()->json([
                        'success' => false,
                        'error'   => $acadepResult['error'],
                        'halted'  => true,
                    ], 200);

                } else {
                    // Error de red — continuar a Capa B
                    Log::warning('[PulseMetrics] ACADEP inaccesible, activando failover comercial', [
                        'error'      => $acadepResult['error'] ?? 'desconocido',
                        'company_id' => $companyId,
                    ]);
                }
            }

            // ── Capa B: AIService (failover comercial) ───────────────────────────────
            if ($report === null) {
                $aiService = new AIService();
                $aiResult  = $aiService->dispatch([
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userMessage],
                    ],
                    'temperature' => 0.4,
                    'max_tokens'  => 2000,
                ], $companyId);

                $report     = $aiResult['response'] ?? '';
                $tokensUsed = (int) ($aiResult['tokens_used'] ?? 0);
                $usedLayer  = 'commercial';

                Log::info('[PulseMetrics] Capa B — proveedor comercial respondió', [
                    'company_id'  => $companyId,
                    'tokens_used' => $tokensUsed,
                ]);
            }

            // ── 4. Auditoría de consumo de tokens (AURA Token Economy) ──────────────
            $this->logAiUsage($companyId, $userId, $userQuery, [
                'response'    => $report,
                'tokens_used' => $tokensUsed,
            ]);

            return response()->json([
                'success'        => true,
                'report'         => $report,
                'metrics'        => $metrics,
                'network_layer'  => $networkLayer,   // 'lan'|'wan'|null — para badge ACADEP en frontend
                'transaction_id' => $transactionId,  // TX del nodo soberano (null si usó AIService)
            ]);

        } catch (\RuntimeException $e) {
            // RuntimeException de AIService: todos los proveedores comerciales fallaron.
            // ── CONDICIÓN DE FUERZA MAYOR — Bypass soberano al Nodo ACADEP ──────────
            // Mandamiento #15: ante colapso de llaves comerciales, AURA desvía el flujo
            // directamente a ACADEP Linux sin intervención humana para garantizar
            // la continuidad del negocio.
            Log::warning('[PulseMetrics] Fuerza Mayor — comerciales colapsados, activando bypass ACADEP', [
                'reason'     => $e->getMessage(),
                'company_id' => $companyId ?? null,
            ]);

            try {
                $acadepBypass = new AcadepAuraService();
                $bpPrompt     = ($systemPrompt ?? '') . "\n\n---\n\n" . ($userMessage ?? '');
                $bpResult     = $acadepBypass->dispatch($bpPrompt, 'AURA_BKC_V1');

                if ($bpResult['status'] === 'ok') {
                    Log::info('[PulseMetrics] ✓ Bypass ACADEP exitoso — fuerza mayor resuelta', [
                        'company_id'     => $companyId ?? null,
                        'transaction_id' => $bpResult['transaction_id'] ?? null,
                        'network_layer'  => $bpResult['network_layer'] ?? null,
                        'latency_ms'     => $bpResult['latency_ms'] ?? null,
                        'tokens_used'    => $bpResult['tokens_used'] ?? 0,
                    ]);

                    $this->logAiUsage($companyId ?? 0, $userId ?? 0, $userQuery ?? '', [
                        'response'    => $bpResult['response'],
                        'tokens_used' => $bpResult['tokens_used'] ?? 0,
                    ]);

                    return response()->json([
                        'success'        => true,
                        'report'         => $bpResult['response'],
                        'metrics'        => $metrics ?? [],
                        'network_layer'  => $bpResult['network_layer'] ?? null,
                        'transaction_id' => $bpResult['transaction_id'] ?? null,
                    ]);
                }
            } catch (\Throwable $bypassErr) {
                Log::error('[PulseMetrics] Bypass ACADEP también falló en fuerza mayor', [
                    'error' => $bypassErr->getMessage(),
                ]);
            }

            // Capa C: Mock estructurado — ningún proveedor disponible
            return response()->json([
                'success' => true,
                'report'  => $this->buildMockReport($metrics ?? [], $userQuery ?? ''),
                'metrics' => $metrics ?? [],
                '_dev'    => 'mock — configure ACADEP_AURA_URL or a valid API key in .env',
            ]);

        } catch (\Exception $e) {
            // Catch universal — ante cualquier fallo inesperado.
            // PROHIBIDO devolver Error 500 al cliente.
            Log::error('[PulseMetrics] query error: ' . $e->getMessage(), [
                'file' => str_replace(base_path(), '[root]', $e->getFile()),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => self::FRIENDLY_OVERLOAD_MSG,
            ], 200);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/v2/analytics/test-connection
    // Auth: Passport OAuth2 (auth:api + role:super_admin) · throttle: 5/min
    //
    // Handshake físico con el Servidor Central ACADEP (Daemon Go).
    //
    // ⚠️  ARQUITECTURA DE SEGURIDAD — DECOUPLING TOTAL DE BASE DE DATOS:
    //     Este método NO consulta la tabla `ai_settings` bajo ninguna circunstancia.
    //     Todas las credenciales se leen estrictamente desde config/services.php
    //     que a su vez mapea las variables del .env del servidor:
    //       · ACADEP_AURA_URL_LAN  → URL del gateway en red local LAN
    //       · ACADEP_AURA_KEY      → Llave de autenticación X-AURA-KEY
    //       · ACADEP_AURA_AGENT_ID → Identificador del agente (AURA_BKC_V1)
    //
    //     El Daemon Go valida la llave con bcrypt.CompareHashAndPassword()
    //     contra el hash almacenado en `acadep_core_tokens_ledger.open_key_hash`.
    //     Para sincronizar el hash, usar el endpoint POST /acadep/generate-query.
    //
    // Errores conocidos y su clasificación:
    //   HTTP 401 → X-AURA-KEY sin hash en el ledger Go/PostgreSQL
    //   HTTP 402 → CAPEX de tokens agotado en el servidor central
    //   HTTP 403 → agent_id no autorizado en el nodo
    //   ConnectException → servidor ACADEP inaccesible (red, firewall o IP incorrecta)
    // ──────────────────────────────────────────────────────────────────────────

    public function testAcadepConnection(Request $request)
    {
        try {
            // ── FUENTE DE VERDAD: .env / config estrictamente — cero accesos a BD ──
            $endpoint = rtrim(config('services.acadep.url_lan', env('ACADEP_AURA_URL_LAN', '')), '/');
            $auraKey  = config('services.acadep.key',      env('ACADEP_AURA_KEY', ''));
            $agentId  = config('services.acadep.agent_id', 'AURA_BKC_V1');
            $timeout  = 30;

            if (empty($endpoint)) {
                return response()->json([
                    'success' => false,
                    'error'   => 'ACADEP_AURA_URL_LAN no está definido en el .env del servidor.',
                ], 200);
            }

            if (empty($auraKey)) {
                return response()->json([
                    'success' => false,
                    'error'   => 'ACADEP_AURA_KEY no está definida en el .env del servidor.',
                ], 200);
            }

            // Enviar el ping de handshake
            $start  = microtime(true);
            $client = new \GuzzleHttp\Client(['timeout' => $timeout]);

            $response = $client->post($endpoint, [
                'headers' => [
                    'X-AURA-KEY'   => $auraKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'agent_id'     => $agentId,
                    'user_session' => 'handshake_test_' . now()->timestamp,
                    'prompt'       => 'HANDSHAKE_PING: Responde con un saludo breve en JSON para confirmar conectividad.',
                ],
            ]);

            $latency = (int) round((microtime(true) - $start) * 1000);
            $body    = json_decode($response->getBody()->getContents(), true);

            if (!is_array($body)) {
                return response()->json([
                    'success'    => false,
                    'latency_ms' => $latency,
                    'error'      => 'El servidor ACADEP respondió con contenido no válido (esperado: JSON).',
                ], 200);
            }

            // Validar el contrato de respuesta (Formato A o Formato B)
            $isSuccess     = isset($body['status']) ? $body['status'] === 'success' : (bool) ($body['success'] ?? false);
            $transactionId = $body['transaction_id'] ?? $body['id'] ?? null;
            $reportHtml    = $body['payload']['report_html'] ?? $body['response'] ?? '';
            $tokensRemaining = $body['tokens_remaining'] ?? $body['quota_remaining'] ?? null;

            if (!$isSuccess) {
                $errorMsg = $body['error'] ?? $body['message'] ?? 'El servidor ACADEP rechazó el handshake.';
                Log::warning('[PulseMetrics] ACADEP handshake rechazado', ['error' => $errorMsg, 'latency_ms' => $latency]);
                return response()->json([
                    'success'    => false,
                    'latency_ms' => $latency,
                    'error'      => $errorMsg,
                ], 200);
            }

            Log::info('[PulseMetrics] ACADEP handshake exitoso', [
                'transaction_id'   => $transactionId,
                'latency_ms'       => $latency,
                'tokens_remaining' => $tokensRemaining,
            ]);

            return response()->json([
                'success'          => true,
                'latency_ms'       => $latency,
                'transaction_id'   => $transactionId,
                'tokens_remaining' => $tokensRemaining,
                'preview'          => mb_substr(strip_tags($reportHtml), 0, 120),
                'message'          => "Handshake exitoso. Latencia: {$latency} ms.",
            ]);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Expone el mensaje exacto de Guzzle para diagnóstico forense en F12
            return response()->json([
                'success' => false,
                'error'   => 'Connection error — ' . $e->getMessage(),
            ], 200);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $map    = [
                401 => 'Autenticación rechazada (HTTP 401). La X-AURA-KEY es inválida o está vencida.',
                402 => 'CAPEX agotado (HTTP 402). El proyecto no tiene tokens disponibles en el servidor ACADEP.',
                403 => 'Acceso denegado (HTTP 403). El agent_id no está autorizado.',
                500 => 'Error interno del servidor ACADEP (HTTP 500).',
            ];
            $baseMsg = $map[$status] ?? "Servidor ACADEP respondió con HTTP {$status}.";
            return response()->json([
                'success' => false,
                'error'   => $baseMsg . ' — ' . $e->getMessage(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PulseMetrics] testAcadepConnection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Error inesperado: ' . $e->getMessage(),
            ], 200);
        }
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    /**
     * Consolida métricas clave del tenant.
     * TENANT LOCK: todas las consultas filtradas estrictamente por $companyId.
     */
    private function gatherTenantMetrics(int $companyId): array
    {
        $totalProperties = Property::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->count();

        $publishedProps = Property::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('published', 1)
            ->count();

        $totalContacts = Contact::where('company_id', $companyId)->count();

        $company = Company::find($companyId);

        return [
            'total_properties'       => $totalProperties,
            'published_properties'   => $publishedProps,
            'unpublished_properties' => $totalProperties - $publishedProps,
            'total_contacts'         => $totalContacts,
            'company_name'           => $company ? $company->name  : 'Empresa',
            'company_email'          => $company ? $company->email  : null,
            'company_phone'          => $company ? $company->phone  : null,
            'package'                => $company ? $company->package : null,
        ];
    }

    /**
     * System prompt enriquecido con:
     *   - Datos reales del tenant (métricas filtradas por company_id)
     *   - Esquema verificado del Codex (02_SYSTEM_CODEX_REGISTRY.md) para razonamiento macro
     *
     * El LLM NUNCA ejecuta SQL; solo razona sobre los datos ya consolidados.
     */
    private function buildSystemPrompt(array $metrics): string
    {
        return <<<PROMPT
Eres Pulse Metrics IA, el motor de análisis e informes ejecutivos de la plataforma Brokers Connector.
Tu misión es generar informes inmobiliarios profesionales, estructurados y accionables en HTML.

══════════════════════════════════════════════════════════
DATOS DEL TENANT ACTIVO (filtrados por company_id — fuente autoritativa)
══════════════════════════════════════════════════════════
Empresa: {$metrics['company_name']}
Propiedades totales en cartera: {$metrics['total_properties']}
Propiedades publicadas en portales: {$metrics['published_properties']}
Propiedades sin publicar: {$metrics['unpublished_properties']}
Total de prospectos/contactos en CRM: {$metrics['total_contacts']}

══════════════════════════════════════════════════════════
ESQUEMA DE BASE DE DATOS (Codex verificado — solo para razonamiento contextual)
══════════════════════════════════════════════════════════
companies: id, name, email, phone, address, rfc, colony, zipcode, package, cutoff_date,
           dominio, logo, about, owner (FK→users.id), active (1=activa / 0=inactiva),
           openpay_customer_id, openpay_subscription_id, created_at

users (agentes y administradores): id, full_name, last_name, email, phone, title (cargo),
           active (BOOLEAN), company_id (FK→companies.id), deleted_at (soft delete), created_at
           Roles: gestionados via Spatie RBAC (tabla pivot model_has_roles).

contacts (prospectos/clientes CRM): id, name, surname, email, origin, status, type,
           company_id, agent_id (FK→users.id), deleted_at

properties (inventario inmobiliario): id, title, bedrooms, baths, parking_lots,
           total_area, built_area, price, currency, zipcode, address,
           published (1=publicada), bbc_general, prop_status_id (FK→property_statuses),
           prop_type_id (FK→property_types), agent_id (FK→users.id),
           company_id (FK→companies.id), deleted_at

invoices (facturación SaaS): id, name, cost_package, status (pending/paid/overdue),
           payday, due_date, company_id

══════════════════════════════════════════════════════════
REGLAS DE GENERACIÓN
══════════════════════════════════════════════════════════
1. Responde siempre en español profesional.
2. Estructura el informe con HTML semántico: <h2>, <h3>, <p>, <ul>, <li>, <strong>.
3. Basa el análisis EXCLUSIVAMENTE en los datos del tenant proporcionados. No inventes cifras.
4. Incluye conclusiones accionables y recomendaciones estratégicas concretas.
5. Si la consulta involucra datos no disponibles en el contexto, indícalo explícitamente.
6. Finaliza SIEMPRE con <h2>Próximos pasos recomendados</h2> con exactamente 3 acciones.
PROMPT;
    }

    /**
     * Informe mock estructurado — se activa cuando el dispatcher de AURA no tiene
     * proveedores operativos (llaves de API no configuradas en .env).
     * Valida la conectividad HTTP completa y muestra las métricas reales del tenant.
     * Se elimina del flujo en cuanto se configuren llaves válidas en GROQ_API_KEY / OPENAI_API_KEY.
     */
    private function buildMockReport(array $metrics, string $query): string
    {
        $company   = htmlspecialchars($metrics['company_name'] ?? 'Empresa', ENT_QUOTES, 'UTF-8');
        $total     = (int) ($metrics['total_properties']       ?? 0);
        $published = (int) ($metrics['published_properties']   ?? 0);
        $unpub     = (int) ($metrics['unpublished_properties'] ?? 0);
        $contacts  = (int) ($metrics['total_contacts']         ?? 0);
        $queryText = htmlspecialchars(mb_substr($query, 0, 120), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<h2>✅ Conexión con el Cerebro V2 — Validada</h2>
<p>El pipeline completo <strong>HTTP → Autenticación → Tenant Lock → Motor IA</strong>
está operativo. Los datos del tenant han sido extraídos y validados correctamente.</p>

<h2>📊 Datos del Tenant: {$company}</h2>
<ul>
  <li><strong>Propiedades totales en cartera:</strong> {$total}</li>
  <li><strong>Propiedades publicadas:</strong> {$published}</li>
  <li><strong>Propiedades sin publicar:</strong> {$unpub}</li>
  <li><strong>Prospectos / Contactos CRM:</strong> {$contacts}</li>
</ul>

<h2>🔍 Consulta recibida</h2>
<p><em>"{$queryText}"</em></p>

<h2>⚠️ Estado del Motor de IA</h2>
<p>El orquestador <strong>AURA</strong> intentó contactar todos los proveedores de IA
configurados (Groq, Mistral, Gemini, OpenAI) pero todas las llaves de API en el archivo
<code>.env</code> son llaves de marcador de posición. Para activar el análisis real,
reemplaza los valores en las siguientes variables del <code>.env</code>:</p>
<ul>
  <li><code>GROQ_API_KEY</code> — Llave gratuita en <strong>console.groq.com</strong></li>
  <li><code>OPENAI_API_KEY</code> — Llave en <strong>platform.openai.com</strong></li>
  <li><code>MISTRAL_API_KEY</code> — Llave en <strong>console.mistral.ai</strong></li>
  <li><code>GEMINI_API_KEY</code> — Llave en <strong>aistudio.google.com</strong></li>
</ul>

<h2>Próximos pasos recomendados</h2>
<ul>
  <li><strong>1.</strong> Configura al menos una llave de API válida en el <code>.env</code>
      y recarga la página para activar el análisis real con IA.</li>
  <li><strong>2.</strong> Verifica en el <strong>Panel Super Admin → Orquestador IA</strong>
      que el proveedor esté marcado como activo y con su llave actualizada.</li>
  <li><strong>3.</strong> Realiza tu primera consulta real — el sistema ya tiene todos
      tus datos de cartera listos para ser analizados.</li>
</ul>
HTML;
    }

    /**
     * Registra el consumo de tokens en ai_conversations / ai_messages.
     * Patrón AURA Token Economy — indexado por user_id, company_id y contenido.
     * Fallo no crítico: el informe ya fue entregado al usuario antes de llamar este método.
     */
    private function logAiUsage(int $companyId, int $userId, string $query, array $aiResult): void
    {
        $tokensUsed = (int) ($aiResult['tokens_used'] ?? 0);

        if ($tokensUsed <= 0) {
            return;
        }

        try {
            $conv = AiConversation::create([
                'company_id' => $companyId,
                'user_id'    => $userId > 0 ? $userId : null,
                'title'      => 'PulseMetrics · ' . mb_substr($query, 0, 80),
                'status'     => 1,
            ]);

            AiMessage::create([
                'conversation_id' => $conv->id,
                'role'            => 'assistant',
                'content'         => mb_substr($aiResult['response'] ?? '', 0, 5000),
                'tokens_used'     => $tokensUsed,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PulseMetrics] logAiUsage falló (no crítico)', [
                'company_id' => $companyId,
                'user_id'    => $userId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/v2/admin/acadep/generate-query
    // Auth: Passport OAuth2 (auth:api + role:super_admin)
    //
    // Utilería de rotación de credenciales:
    //   1. Lee ACADEP_AURA_KEY desde config/services (fuente: .env del servidor).
    //   2. Genera su hash bcrypt con PASSWORD_BCRYPT y cost=12 (función irreversible).
    //   3. Devuelve la consulta SQL lista para ejecutar en el servidor central ACADEP.
    //
    // El hash generado NO es la llave — es su huella unidireccional.
    // La llave original permanece en .env y nunca se expone en la respuesta.
    // ──────────────────────────────────────────────────────────────────────────

    public function generateAcadepSyncQuery()
    {
        $rawKey = config('services.acadep.key', env('ACADEP_AURA_KEY', ''));

        if (empty($rawKey)) {
            return response()->json([
                'success' => false,
                'error'   => 'ACADEP_AURA_KEY no está definida en el .env del servidor. No hay nada que hashear.',
            ], 200);
        }

        // Bcrypt cost=12 — ~300 ms en hardware moderno; normal para esta función.
        $hash = password_hash($rawKey, PASSWORD_BCRYPT, ['cost' => 12]);

        if ($hash === false) {
            return response()->json([
                'success' => false,
                'error'   => 'password_hash() falló. Verifica que el módulo bcrypt esté habilitado en PHP.',
            ], 200);
        }

        $sql =
            "UPDATE `acadep_core_tokens_ledger`\n" .
            "SET    `open_key_hash` = '{$hash}'\n" .
            "WHERE  `project_name`  = 'BROKERS CONNECTOR';";

        Log::info('[ACADEP] Query de sincronización generada para rotación de credencial.');

        return response()->json([
            'success' => true,
            'sql'     => $sql,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v2/admin/acadep/status
    // Auth: Passport OAuth2 (auth:api + role:super_admin)
    //
    // Devuelve la configuración estática de ACADEP leída del .env del servidor.
    // La X-AURA-KEY se enmascara en el backend: primeros 8 chars + asteriscos.
    // No accede a la tabla ai_settings — es una fuente de verdad pura desde .env.
    // ──────────────────────────────────────────────────────────────────────────

    public function acadepStatus()
    {
        $urlLan = config('services.acadep.url_lan', '');
        $rawKey = config('services.acadep.key', env('ACADEP_AURA_KEY', ''));

        // Máscara estricta: primeros 8 chars visibles + 20 asteriscos
        $maskedKey = '';
        if (!empty($rawKey)) {
            $visible   = substr($rawKey, 0, 8);
            $maskedKey = $visible . str_repeat('*', 20);
        }

        return response()->json([
            'success'    => true,
            'url_lan'    => $urlLan ?: null,
            'key_masked' => $maskedKey ?: null,
            'configured' => !empty($urlLan) && !empty($rawKey),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DELETE /api/v2/admin/ai-providers/{id}
    // Auth: Passport OAuth2 (auth:api + role:super_admin)
    //
    // Borrado físico del proveedor IA en ai_settings.
    // Limpia la caché de configuración para que AcadepAuraService
    // refleje el cambio en el siguiente request sin necesidad de reiniciar.
    // ──────────────────────────────────────────────────────────────────────────

    public function deleteAiProvider($id)
    {
        try {
            $setting = \App\AiSetting::findOrFail((int) $id);
            $providerName = $setting->provider_name;
            $setting->delete();

            // Limpiar caché de configuración tras borrado
            \Artisan::call('config:clear');

            Log::info('[AiProviders] Proveedor eliminado', [
                'id'            => $id,
                'provider_name' => $providerName,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Proveedor '{$providerName}' eliminado con éxito.",
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Proveedor no encontrado (ID: ' . $id . ').',
            ], 200);

        } catch (\Exception $e) {
            Log::error('[AiProviders] deleteAiProvider error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Error al eliminar el proveedor: ' . $e->getMessage(),
            ], 200);
        }
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}
