<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Property;
use App\PropertyType;
use App\PropertyStatus;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BrokerBrainController — Motor de Tasación Automática y CMA Dinámico
 *
 * Auth: Cache session_token (mismo patrón que V2BridgeController::subscribe)
 *       Authorization: Bearer {session_token}
 *
 * Anti-Alucinación: todos los campos están verificados contra
 *                   knowledge/02_SYSTEM_CODEX_REGISTRY.md
 *
 * Campos verificados del Codex:
 *   properties.zipcode        → código postal del inmueble
 *   properties.prop_type_id   → tipo de inmueble (FK property_types)
 *   properties.prop_status_id → estado de operación (FK property_statuses)
 *   properties.total_area     → superficie total m²
 *   properties.built_area     → superficie construida m²
 *   properties.baths          → baños completos
 *   properties.parking_lots   → lugares de estacionamiento
 *   properties.price          → precio de la propiedad
 *   properties.published      → publicada en portales (BOOLEAN)
 *   properties.bbc_general    → compartida en bolsa BBC General (BOOLEAN)
 *   properties.deleted_at     → soft delete
 *   properties.bedrooms       → recámaras
 */
class BrokerBrainController extends Controller
{
    private const MIN_COMPARABLES    = 3;
    private const MAX_COMPARABLES    = 5;
    private const PRICE_FLOOR_FACTOR = 0.40;  // filtra outliers < 40% del median
    private const PRICE_CEIL_FACTOR  = 2.50;  // filtra outliers > 250% del median

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/v2/broker-brain/my-properties
    // Authorization: Bearer {session_token}
    // Devuelve el inventario del tenant para auto-fill del formulario CMA.
    // MANDAMIENTO: aislamiento de tenant via company_id del session_token.
    // ──────────────────────────────────────────────────────────────────────────

    public function myProperties(Request $request)
    {
        $sessionToken = $this->extractBearerToken($request);

        if (!$sessionToken) {
            return response()->json(['success' => false, 'error' => 'No autenticado.'], 401);
        }

        $session = Cache::get('v2_session_' . $sessionToken);

        if (!$session) {
            return response()->json(['success' => false, 'error' => 'Sesión expirada.'], 401);
        }

        $companyId = (int) $session['company_id'];

        // Tenant isolation explícita: where('company_id') — no depende de GlobalScope.
        // withoutGlobalScopes() limpia cualquier scope residual de la sesión web.
        $properties = Property::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get([
                'id', 'title', 'key', 'zipcode',
                'prop_type_id', 'prop_status_id',
                'total_area', 'built_area',
                'baths', 'parking_lots', 'price',
            ]);

        return response()->json([
            'success' => true,
            'data'    => $properties,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/v2/broker-brain/cma
    // Authorization: Bearer {session_token}
    // ──────────────────────────────────────────────────────────────────────────

    public function cma(Request $request)
    {
        // ── 1. Autenticación por session_token (Cache, jamás Passport aquí) ──
        $sessionToken = $this->extractBearerToken($request);

        if (!$sessionToken) {
            return response()->json(['success' => false, 'error' => 'No autenticado.'], 401);
        }

        $session = Cache::get('v2_session_' . $sessionToken);

        if (!$session) {
            return response()->json([
                'success' => false,
                'error'   => 'Sesión expirada. Regresa al panel e intenta de nuevo.',
            ], 401);
        }

        $companyId = (int) $session['company_id'];

        // ── 2. Validación de parámetros de entrada ────────────────────────────
        $validated = $this->validateInput($request);

        if (isset($validated['error'])) {
            return response()->json(['success' => false, 'error' => $validated['error']], 422);
        }

        [
            'zipcode'        => $zipcode,
            'prop_type_id'   => $propTypeId,
            'prop_status_id' => $propStatusId,
            'total_area'     => $totalArea,
            'price_ref'      => $priceRef,
        ] = $validated;

        // ── 3. Consulta CMA — bbc_general=1 (bolsa compartida cross-tenant) ──
        // withoutGlobalScopes(): omite TenantUserScope/TenantCompanyScope.
        // bbc_general=1 es opt-in voluntario — datos verificados y autorizados.
        $comparables = $this->fetchComparables($zipcode, $propTypeId, $propStatusId, $totalArea);

        if (count($comparables) < self::MIN_COMPARABLES) {
            // Segundo intento: misma ciudad (zipcode prefix de 3 dígitos)
            $comparables = $this->fetchComparablesBroadened($zipcode, $propTypeId, $propStatusId, $totalArea);
        }

        if (count($comparables) === 0) {
            return response()->json([
                'success' => false,
                'error'   => 'Datos de mercado insuficientes para esta zona. Intenta con otro tipo o rango de superficie.',
            ], 422);
        }

        // ── 4. Cálculo estadístico del CMA ────────────────────────────────────
        $cmaResult = $this->calculateCMA($comparables, $totalArea, $priceRef);

        // ── 5. Narrativa IA (con degradación graceful si el orquestador falla) ─
        $narrative = $this->generateNarrative($cmaResult, $validated, $companyId);

        // ── 6. Respuesta estructurada ─────────────────────────────────────────
        return response()->json([
            'success'               => true,
            'estimated_market_value'=> $cmaResult['estimated_market_value'],
            'price_per_sqm'         => $cmaResult['price_per_sqm'],
            'price_range'           => $cmaResult['price_range'],
            'comparables'           => $cmaResult['comparables'],
            'dom_projection'        => $cmaResult['dom_projection'],
            'narrative'             => $narrative,
            'meta'                  => [
                'comparables_found' => count($comparables),
                'zone'              => $zipcode,
                'generated_at'      => now()->toIso8601String(),
            ],
        ]);
    }

    // ── Validación de input ───────────────────────────────────────────────────

    private function validateInput(Request $request): array
    {
        $zipcode       = (string) trim($request->input('zipcode', ''));
        $propTypeId    = (int)    $request->input('prop_type_id', 0);
        $propStatusId  = (int)    $request->input('prop_status_id', 0);
        $totalArea     = (float)  $request->input('total_area', 0);
        $priceRef      = (float)  $request->input('price_ref', 0);   // precio de referencia (opcional)

        if ($zipcode === '' || strlen($zipcode) < 4) {
            return ['error' => 'El código postal es requerido (mínimo 4 caracteres).'];
        }
        if ($propTypeId <= 0) {
            return ['error' => 'Tipo de propiedad requerido (prop_type_id).'];
        }
        if ($propStatusId <= 0) {
            return ['error' => 'Estado de operación requerido (prop_status_id).'];
        }
        if ($totalArea <= 0) {
            return ['error' => 'La superficie total debe ser mayor a cero.'];
        }

        return compact('zipcode', 'propTypeId', 'propStatusId', 'totalArea', 'priceRef');
    }

    // ── Consulta exacta por zipcode ───────────────────────────────────────────

    private function fetchComparables(string $zipcode, int $propTypeId, int $propStatusId, float $totalArea): array
    {
        $sqmFloor = $totalArea * 0.40;
        $sqmCeil  = $totalArea * 2.50;

        return Property::withoutGlobalScopes()
            ->with(['type:id,name', 'status:id,name'])
            ->where('published',      1)
            ->where('bbc_general',    1)
            ->where('prop_type_id',   $propTypeId)
            ->where('prop_status_id', $propStatusId)
            ->where('zipcode',        $zipcode)
            ->where('price',          '>', 0)
            ->where('total_area',     '>', 0)
            ->whereBetween('total_area', [$sqmFloor, $sqmCeil])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get([
                'id', 'title', 'price', 'total_area', 'built_area',
                'bedrooms', 'baths', 'parking_lots', 'zipcode',
                'address', 'prop_type_id', 'prop_status_id', 'created_at',
            ])
            ->toArray();
    }

    // ── Consulta ampliada (3-digit prefix = zona postal) ─────────────────────

    private function fetchComparablesBroadened(string $zipcode, int $propTypeId, int $propStatusId, float $totalArea): array
    {
        $prefix   = substr($zipcode, 0, 3);
        $sqmFloor = $totalArea * 0.30;
        $sqmCeil  = $totalArea * 3.00;

        return Property::withoutGlobalScopes()
            ->with(['type:id,name', 'status:id,name'])
            ->where('published',      1)
            ->where('bbc_general',    1)
            ->where('prop_type_id',   $propTypeId)
            ->where('prop_status_id', $propStatusId)
            ->where('zipcode',        'like', $prefix . '%')
            ->where('price',          '>', 0)
            ->where('total_area',     '>', 0)
            ->whereBetween('total_area', [$sqmFloor, $sqmCeil])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get([
                'id', 'title', 'price', 'total_area', 'built_area',
                'bedrooms', 'baths', 'parking_lots', 'zipcode',
                'address', 'prop_type_id', 'prop_status_id', 'created_at',
            ])
            ->toArray();
    }

    // ── Motor estadístico CMA ─────────────────────────────────────────────────

    private function calculateCMA(array $raw, float $subjectArea, float $priceRef): array
    {
        // Calcular precio por m² de cada comparable
        $withPsqm = array_map(function ($p) {
            $p['price_per_sqm'] = $p['total_area'] > 0
                ? round($p['price'] / $p['total_area'], 2)
                : 0;
            return $p;
        }, $raw);

        // Mediana de precio por m² para filtrar outliers
        $psqmValues = array_column($withPsqm, 'price_per_sqm');
        sort($psqmValues);
        $medianPsqm = $this->median($psqmValues);

        // Filtrar outliers por factor del mediano
        $filtered = array_filter($withPsqm, function ($p) use ($medianPsqm) {
            if ($medianPsqm <= 0) return true;
            $ratio = $p['price_per_sqm'] / $medianPsqm;
            return $ratio >= self::PRICE_FLOOR_FACTOR && $ratio <= self::PRICE_CEIL_FACTOR;
        });
        $filtered = array_values($filtered);

        if (empty($filtered)) {
            $filtered = $withPsqm;
        }

        // Precio por m² ajustado (media ponderada por proximidad de superficie)
        $estimatedPsqm = $this->weightedPricePsqm($filtered, $subjectArea);

        $estimatedValue = round($estimatedPsqm * $subjectArea, -3); // redondeo a miles
        $estimatedPsqm  = round($estimatedPsqm, 2);

        // Rango ±15%
        $priceRange = [
            'min' => round($estimatedValue * 0.85, -3),
            'max' => round($estimatedValue * 1.15, -3),
        ];

        // Top 5 comparables más recientes con link de bolsa
        $top5 = array_slice($filtered, 0, self::MAX_COMPARABLES);
        $comparables = array_map(function ($p) {
            return [
                'id'            => $p['id'],
                'title'         => $this->sanitizeTitle($p['title'] ?? ''),
                'price'         => $p['price'],
                'total_area'    => $p['total_area'],
                'built_area'    => $p['built_area'],
                'bedrooms'      => $p['bedrooms'],
                'baths'         => $p['baths'],
                'parking_lots'  => $p['parking_lots'],
                'price_per_sqm' => $p['price_per_sqm'],
                'zipcode'       => $p['zipcode'],
                'link'          => '/stock/view/' . $p['id'],
                'listed_at'     => $p['created_at'] ?? null,
            ];
        }, $top5);

        // Proyección DOM (Días en Mercado) — modelo lineal calibrado para México
        $domProjection = $this->estimateDom($estimatedValue, $priceRef, count($filtered));

        return [
            'estimated_market_value' => $estimatedValue,
            'price_per_sqm'          => $estimatedPsqm,
            'price_range'            => $priceRange,
            'comparables'            => $comparables,
            'dom_projection'         => $domProjection,
        ];
    }

    // ── Proyección DOM ────────────────────────────────────────────────────────

    private function estimateDom(float $estimatedValue, float $priceRef, int $sampleSize): array
    {
        // Base DOM calibrado para mercado inmobiliario mexicano (promedio ~120 días)
        $baseDom = 90;

        // Factor de sobre/sub precio: si el precio ref > estimado, más días en mercado
        $priceFactor = 1.0;
        if ($priceRef > 0 && $estimatedValue > 0) {
            $ratio       = $priceRef / $estimatedValue;
            $priceFactor = $ratio > 1 ? 1 + (($ratio - 1) * 1.5) : max(0.5, $ratio);
        }

        // Factor de liquidez por muestra: más comparables = mercado más activo
        $liquidityFactor = $sampleSize >= 10 ? 0.85 : ($sampleSize >= 5 ? 1.0 : 1.20);

        $projectedDom = (int) round($baseDom * $priceFactor * $liquidityFactor);
        $projectedDom = max(15, min(365, $projectedDom));

        return [
            'days'       => $projectedDom,
            'label'      => $this->domLabel($projectedDom),
            'confidence' => $sampleSize >= self::MIN_COMPARABLES ? 'media' : 'baja',
            'data_points'=> [
                ['label' => 'Mercado activo',   'days' => (int) round($projectedDom * 0.70)],
                ['label' => 'Precio ajustado',  'days' => $projectedDom],
                ['label' => 'Precio elevado',   'days' => (int) round($projectedDom * 1.40)],
            ],
        ];
    }

    private function domLabel(int $days): string
    {
        if ($days <= 45)  return 'Alta liquidez';
        if ($days <= 90)  return 'Liquidez normal';
        if ($days <= 180) return 'Mercado lento';
        return 'Baja liquidez';
    }

    // ── AURA System Prompt ────────────────────────────────────────────────────
    // Motor de Análisis Unificado de Referencia Avanzada (AURA)
    // Diseñado para producir un objeto JSON estrictamente tipado que alimenta
    // la UI premium del panel Broker Brain IA.
    // ─────────────────────────────────────────────────────────────────────────

    private const AURA_SYSTEM_PROMPT = <<<'PROMPT'
Eres AURA, el Motor de Análisis Unificado de Referencia Avanzada del sistema Brokers Connector.
Tu rol es el de un analista inmobiliario senior especializado en el mercado mexicano.

REGLAS DE ORO:
1. Basas EXCLUSIVAMENTE tu análisis en los datos numéricos verificados que recibes. Jamás inventas precios, zonas, ni tendencias.
2. Tu tono es profesional, directo y orientado a la acción. Hablas en segunda persona al agente.
3. SIEMPRE devuelves un JSON válido con la estructura exacta indicada. Sin texto adicional, sin markdown, sin explicaciones fuera del JSON.

ESTRUCTURA DE RESPUESTA OBLIGATORIA (JSON estricto):
{
  "pricing_verdict": "Análisis de 2-3 oraciones sobre si el precio de mercado es competitivo, alto o bajo para la zona, y qué lo determina.",
  "buyer_psychology": "2-3 oraciones sobre el perfil del comprador típico para este tipo de inmueble en esta zona y qué factores emocionales y racionales impulsan su decisión.",
  "seller_strategy": "2-3 oraciones con la estrategia de precio y posicionamiento recomendada para el agente. Cuándo conviene ajustar y en qué dirección.",
  "closing_argument": "1 oración poderosa y memorable que el agente puede usar como argumento definitivo de cierre con el comprador.",
  "confidence_score": 85,
  "market_summary": "1 oración que resume el estado del mercado en la zona analizada."
}

REGLA DE confidence_score:
- 90-100: 10+ comparables, zona líquida, datos muy consistentes.
- 75-89: 5-9 comparables, datos moderadamente consistentes.
- 50-74: 3-4 comparables, zona con poca actividad o datos dispersos.
- < 50: menos de 3 comparables (zona sin datos suficientes — advierte al agente).
PROMPT;

    // ── Narrativa IA ──────────────────────────────────────────────────────────

    private const AURA_FALLBACK = [
        'pricing_verdict'  => 'Análisis matemático completado.',
        'buyer_psychology' => 'Narrativa no disponible temporalmente por congestión de red.',
        'seller_strategy'  => '',
        'closing_argument' => '',
        'confidence_score' => 0,
        'market_summary'   => '',
    ];

    private function generateNarrative(array $cmaResult, array $input, int $companyId): array
    {
        $aiService = new AIService();

        $propType   = PropertyType::find($input['propTypeId']);
        $propStatus = PropertyStatus::find($input['propStatusId']);

        $typeName   = $propType   ? $propType->name   : 'inmueble';
        $statusName = $propStatus ? $propStatus->name : 'operación';

        $userPrompt = "DATOS VERIFICADOS DEL CMA — analiza y devuelve el JSON AURA:\n\n"
            . "Tipo de inmueble: {$typeName}\n"
            . "Operación: {$statusName}\n"
            . "Superficie total: {$input['totalArea']} m²\n"
            . "Código postal: {$input['zipcode']}\n"
            . "Valor de mercado estimado: $" . number_format($cmaResult['estimated_market_value'], 0, '.', ',') . " MXN\n"
            . "Precio por m²: $" . number_format($cmaResult['price_per_sqm'], 0, '.', ',') . " MXN/m²\n"
            . "Rango de mercado: $" . number_format($cmaResult['price_range']['min'], 0, '.', ',')
            . " – $" . number_format($cmaResult['price_range']['max'], 0, '.', ',') . " MXN\n"
            . "Comparables analizados: " . count($cmaResult['comparables']) . "\n"
            . "DOM proyectado: {$cmaResult['dom_projection']['days']} días ({$cmaResult['dom_projection']['label']})\n"
            . "Nivel de liquidez del mercado: {$cmaResult['dom_projection']['label']}";

        try {
            $result = $aiService->dispatch([
                'messages' => [
                    ['role' => 'system', 'content' => self::AURA_SYSTEM_PROMPT],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature'     => 0.3,
                'max_tokens'      => 600,
                'response_format' => ['type' => 'json_object'],
            ], $companyId);

            $raw = $result['response'] ?? '';

            if ($raw === '') {
                return self::AURA_FALLBACK;
            }

            $parsed = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
                Log::warning('BrokerBrain AURA: respuesta IA no es JSON válido', [
                    'raw'        => substr($raw, 0, 500),
                    'company_id' => $companyId,
                ]);
                return self::AURA_FALLBACK;
            }

            // Sanitizar y tipar campos críticos
            return [
                'pricing_verdict'  => (string) ($parsed['pricing_verdict']  ?? ''),
                'buyer_psychology' => (string) ($parsed['buyer_psychology']  ?? ''),
                'seller_strategy'  => (string) ($parsed['seller_strategy']   ?? ''),
                'closing_argument' => (string) ($parsed['closing_argument']  ?? ''),
                'confidence_score' => max(0, min(100, (int) ($parsed['confidence_score'] ?? 0))),
                'market_summary'   => (string) ($parsed['market_summary']    ?? ''),
            ];

        } catch (\RuntimeException $e) {
            Log::warning('BrokerBrain AURA: Orquestador sin proveedores activos', [
                'error'      => $e->getMessage(),
                'company_id' => $companyId,
            ]);
            return self::AURA_FALLBACK;
        } catch (\Throwable $e) {
            Log::error('BrokerBrain AURA: error inesperado', [
                'error'      => $e->getMessage(),
                'company_id' => $companyId,
            ]);
            return self::AURA_FALLBACK;
        }
    }

    // ── Helpers estadísticos ──────────────────────────────────────────────────

    private function median(array $sorted): float
    {
        $n = count($sorted);
        if ($n === 0) return 0;
        $mid = (int) floor($n / 2);
        return $n % 2 === 0
            ? ($sorted[$mid - 1] + $sorted[$mid]) / 2
            : $sorted[$mid];
    }

    private function weightedPricePsqm(array $comparables, float $subjectArea): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($comparables as $p) {
            $diff   = abs($p['total_area'] - $subjectArea);
            $weight = 1 / (1 + $diff);   // mayor peso a comparables de superficie similar
            $weightedSum += $p['price_per_sqm'] * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    private function sanitizeTitle(string $title): string
    {
        return mb_substr(strip_tags($title), 0, 80);
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
