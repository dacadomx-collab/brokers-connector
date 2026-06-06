<?php

namespace App\Http\Controllers\Api;

use App\AiConversation;
use App\AiMessage;
use App\AiPrompt;
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

        if (!$session || !is_array($session)) {
            return response()->json(['success' => false, 'error' => 'Sesión expirada.'], 401);
        }

        // Null-coalescing defensivo: super_admin puede tener company_id null en sesión
        $companyId = (int) ($session['company_id'] ?? 0);

        if ($companyId <= 0) {
            return response()->json(['success' => true, 'data' => []]);
        }

        try {
            // 'key' es palabra reservada de MySQL — se excluye del SELECT para evitar
            // errores de SQL en configuraciones estrictas. El JS lo omite gracefully.
            // Tenant isolation explícita: where('company_id') sin depender de GlobalScopes.
            $properties = Property::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get([
                    'id', 'title', 'zipcode',
                    'prop_type_id', 'prop_status_id',
                    'total_area', 'built_area',
                    'baths', 'parking_lots', 'price',
                ]);

            return response()->json([
                'success' => true,
                'data'    => $properties,
            ]);

        } catch (\Throwable $e) {
            Log::error('BrokerBrain myProperties: error en query', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
                'line'       => $e->getLine(),
            ]);
            // Degradación graceful: el dropdown de inventario no es crítico
            return response()->json(['success' => true, 'data' => []]);
        }
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

        // compact() genera claves camelCase (el nombre de la variable PHP).
        // El destructuring debe usar las mismas claves que devuelve compact().
        [
            'zipcode'      => $zipcode,
            'propTypeId'   => $propTypeId,
            'propStatusId' => $propStatusId,
            'totalArea'    => $totalArea,
            'priceRef'     => $priceRef,
        ] = $validated;

        // ── 3. force_ai: saltar la búsqueda en DB e ir directo a AURA Layer 3 ──
        // Activado cuando el usuario pulsa "Probar Valuación Sintética con IA"
        // desde un resultado de base de datos (Layer 1 o 2).
        $forceAi = filter_var($request->input('force_ai', false), FILTER_VALIDATE_BOOLEAN);

        if ($forceAi) {
            $synthResult = $this->synthesizeFromAI($validated, $companyId);

            if ($synthResult === null) {
                $synthResult = $this->interpolateSyntheticFallback($validated);
            }

            $domDays = ($synthResult['suggested_dom_days'] ?? 0) > 0
                ? (int) $synthResult['suggested_dom_days']
                : null;

            $domProjection = $domDays
                ? [
                    'days'        => $domDays,
                    'label'       => $this->domLabel($domDays),
                    'confidence'  => 'baja',
                    'data_points' => [
                        ['label' => 'Mercado activo',  'days' => (int) round($domDays * 0.70)],
                        ['label' => 'Precio ajustado', 'days' => $domDays],
                        ['label' => 'Precio elevado',  'days' => (int) round($domDays * 1.40)],
                    ],
                ]
                : $this->estimateDom((float) $synthResult['estimated_value'], $priceRef, 0);

            return response()->json([
                'success'                => true,
                'confidence_score'       => $synthResult['confidence_score'],
                'explainability'         => $synthResult['explainability'],
                'estimated_market_value' => $synthResult['estimated_value'],
                'price_per_sqm'          => $synthResult['price_per_sqm'],
                'price_range'            => [
                    'min' => $synthResult['price_range_min'],
                    'max' => $synthResult['price_range_max'],
                ],
                'comparables'            => [],
                'dom_projection'         => $domProjection,
                'narrative'              => [
                    'pricing_verdict'  => $synthResult['pricing_verdict'],
                    'buyer_psychology' => $synthResult['buyer_psychology'],
                    'seller_strategy'  => $synthResult['seller_strategy'],
                    'closing_argument' => $synthResult['closing_argument'],
                    'confidence_score' => $synthResult['confidence_score'],
                    'market_summary'   => $synthResult['market_summary'],
                ],
                'meta'                   => [
                    'layer_used'        => 3,
                    'comparables_found' => 0,
                    'fallback_level'    => 3,
                    'forced_ai'         => true,
                    'zone'              => $zipcode,
                    'generated_at'      => now()->toIso8601String(),
                ],
            ]);
        }

        // ══ CASCADA COGNITIVA — 3 Layers ════════════════════════════════════
        //
        // Layer 1 (Exact Match):    CP exacto + bbc=1 → 3+ comparables → confianza 80-95%
        // Layer 2 (Relaxed Radius): CP ampliado / sin bbc → 1+ comparables → confianza 55-75%
        // Layer 3 (Urban AI):       Sin comparables locales → AURA infiere el precio → 25-65%
        //
        // El error 422 "Datos insuficientes" está PROHIBIDO.
        // Siempre se retorna una tasación válida con confidence_score y explainability.
        // ═════════════════════════════════════════════════════════════════════

        // ── 3A. Layer 1 & 2: Búsqueda en base de datos (cascade progresivo) ──
        $cascade       = $this->fetchComparablesCascade($zipcode, $propTypeId, $propStatusId, $totalArea);
        $comparables   = $cascade['comparables'];
        $fallbackLevel = $cascade['level'];   // 0=exact, 1=prefix+bbc, 2=prefix, 3=no-zip, -1=empty

        if (!empty($comparables)) {
            // Layer 1: comparables directos (level 0), máxima confianza matemática
            // Layer 2: comparables de zona ampliada (level 1-3), confianza reducida
            $dbLayer = $fallbackLevel === 0 ? 1 : 2;

            $cmaResult                   = $this->calculateCMA($comparables, $totalArea, $priceRef);
            $cmaResult['fallback_level'] = $fallbackLevel;

            $confidence    = $this->deriveConfidence($dbLayer, count($comparables));
            $explainability = $this->deriveExplainability($dbLayer, $fallbackLevel, count($comparables), $zipcode);
            $narrative      = $this->generateNarrative($cmaResult, $validated, $companyId);

            return response()->json([
                'success'                => true,
                'confidence_score'       => $confidence,
                'explainability'         => $explainability,
                'estimated_market_value' => $cmaResult['estimated_market_value'],
                'price_per_sqm'          => $cmaResult['price_per_sqm'],
                'price_range'            => $cmaResult['price_range'],
                'comparables'            => $cmaResult['comparables'],
                'dom_projection'         => $cmaResult['dom_projection'],
                'narrative'              => $narrative,
                'meta'                   => [
                    'layer_used'        => $dbLayer,
                    'comparables_found' => count($comparables),
                    'fallback_level'    => $fallbackLevel,
                    'forced_ai'         => false,
                    'zone'              => $zipcode,
                    'generated_at'      => now()->toIso8601String(),
                ],
            ]);
        }

        // ── 3B. Layer 3: Urban Intelligence (AURA como valuador sintético) ────
        // No hay comparables en ningún nivel de DB.
        // AURA usa conocimiento del mercado mexicano para inferir el precio.
        $synthResult = $this->synthesizeFromAI($validated, $companyId);

        if ($synthResult === null) {
            $synthResult = $this->interpolateSyntheticFallback($validated);
        }

        $domDays = ($synthResult['suggested_dom_days'] ?? 0) > 0
            ? (int) $synthResult['suggested_dom_days']
            : null;

        $domProjection = $domDays
            ? [
                'days'        => $domDays,
                'label'       => $this->domLabel($domDays),
                'confidence'  => 'baja',
                'data_points' => [
                    ['label' => 'Mercado activo',  'days' => (int) round($domDays * 0.70)],
                    ['label' => 'Precio ajustado', 'days' => $domDays],
                    ['label' => 'Precio elevado',  'days' => (int) round($domDays * 1.40)],
                ],
            ]
            : $this->estimateDom((float) $synthResult['estimated_value'], $priceRef, 0);

        return response()->json([
            'success'                => true,
            'confidence_score'       => $synthResult['confidence_score'],
            'explainability'         => $synthResult['explainability'],
            'estimated_market_value' => $synthResult['estimated_value'],
            'price_per_sqm'          => $synthResult['price_per_sqm'],
            'price_range'            => [
                'min' => $synthResult['price_range_min'],
                'max' => $synthResult['price_range_max'],
            ],
            'comparables'            => [],
            'dom_projection'         => $domProjection,
            'narrative'              => [
                'pricing_verdict'  => $synthResult['pricing_verdict'],
                'buyer_psychology' => $synthResult['buyer_psychology'],
                'seller_strategy'  => $synthResult['seller_strategy'],
                'closing_argument' => $synthResult['closing_argument'],
                'confidence_score' => $synthResult['confidence_score'],
                'market_summary'   => $synthResult['market_summary'],
            ],
            'meta'                   => [
                'layer_used'        => 3,
                'comparables_found' => 0,
                'fallback_level'    => 3,
                'forced_ai'         => false,
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

    // ── Búsqueda Progresiva (Fallback Radius) — 4 niveles ────────────────────
    // Retorna ['comparables' => array, 'level' => int]:
    //   0 = CP exacto + bbc=1 + tipo + status  (resultado ideal)
    //   1 = prefijo 3-dígitos + bbc=1 + tipo + status
    //   2 = prefijo 3-dígitos + sin bbc + tipo + status
    //   3 = sin CP + sin bbc + solo tipo  (tasación de último recurso)
    //  -1 = ningún comparable encontrado (el caller retorna 422)

    private function fetchComparablesCascade(
        string $zipcode,
        int    $propTypeId,
        int    $propStatusId,
        float  $totalArea
    ): array {
        $prefix = substr($zipcode, 0, 3);

        $levels = [
            // L0: CP exacto · bbc=1 · tipo+status · área ±40-250%
            [
                'zipcode_exact'   => $zipcode,
                'zipcode_prefix'  => null,
                'bbc_required'    => true,
                'status_required' => true,
                'area_floor_pct'  => 0.40,
                'area_ceil_pct'   => 2.50,
                'min_required'    => self::MIN_COMPARABLES,
            ],
            // L1: prefijo 3-dígitos · bbc=1 · tipo+status · área ±30-300%
            [
                'zipcode_exact'   => null,
                'zipcode_prefix'  => $prefix,
                'bbc_required'    => true,
                'status_required' => true,
                'area_floor_pct'  => 0.30,
                'area_ceil_pct'   => 3.00,
                'min_required'    => self::MIN_COMPARABLES,
            ],
            // L2: prefijo 3-dígitos · sin bbc · tipo+status · área ±30-300%
            [
                'zipcode_exact'   => null,
                'zipcode_prefix'  => $prefix,
                'bbc_required'    => false,
                'status_required' => true,
                'area_floor_pct'  => 0.30,
                'area_ceil_pct'   => 3.00,
                'min_required'    => self::MIN_COMPARABLES,
            ],
            // L3: sin CP · sin bbc · solo tipo · área ±10-1000% · acepta 1+
            [
                'zipcode_exact'   => null,
                'zipcode_prefix'  => null,
                'bbc_required'    => false,
                'status_required' => false,
                'area_floor_pct'  => 0.10,
                'area_ceil_pct'   => 10.00,
                'min_required'    => 1,
            ],
        ];

        foreach ($levels as $level => $cfg) {
            $comparables = $this->runComparableQuery($cfg, $propTypeId, $propStatusId, $totalArea);
            if (count($comparables) >= $cfg['min_required']) {
                return ['comparables' => $comparables, 'level' => $level];
            }
        }

        return ['comparables' => [], 'level' => -1];
    }

    private function runComparableQuery(
        array $cfg,
        int   $propTypeId,
        int   $propStatusId,
        float $totalArea
    ): array {
        $sqmFloor = $totalArea * $cfg['area_floor_pct'];
        $sqmCeil  = $totalArea * $cfg['area_ceil_pct'];

        $q = Property::withoutGlobalScopes()
            ->with(['type:id,name', 'status:id,name'])
            ->where('published',    1)
            ->where('prop_type_id', $propTypeId)
            ->where('price',        '>', 0)
            ->where('total_area',   '>', 0)
            ->whereBetween('total_area', [$sqmFloor, $sqmCeil])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(20);

        if ($cfg['bbc_required']) {
            $q->where('bbc_general', 1);
        }
        if ($cfg['status_required']) {
            $q->where('prop_status_id', $propStatusId);
        }
        if ($cfg['zipcode_exact'] !== null) {
            $q->where('zipcode', $cfg['zipcode_exact']);
        } elseif ($cfg['zipcode_prefix'] !== null) {
            $q->where('zipcode', 'like', $cfg['zipcode_prefix'] . '%');
        }

        return $q->get([
            'id', 'title', 'price', 'total_area', 'built_area',
            'bedrooms', 'baths', 'parking_lots', 'zipcode',
            'address', 'prop_type_id', 'prop_status_id', 'created_at',
        ])->toArray();
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
- 90-100: 10+ comparables, zona líquida, búsqueda exacta (nivel 0), datos muy consistentes.
- 75-89: 5-9 comparables, datos moderadamente consistentes.
- 50-74: 3-4 comparables, zona con poca actividad; O búsqueda de zona ampliada (nivel 1).
- 25-49: búsqueda ampliada sin filtros BBC (nivel 2-3), datos geográficamente dispersos.
- < 25: menos de 3 comparables con búsqueda ampliada — advierte explícitamente al agente.
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

    // ── Prompt de Inteligencia Urbana (Layer 3) ───────────────────────────────
    // Actúa como fallback si el slug 'cma_urban_intelligence' no existe en DB.
    // El Super Admin puede editarlo en tiempo real desde la Consola de Prompts.

    private const AURA_URBAN_SYSTEM_PROMPT = <<<'PROMPT'
Eres AURA, Motor de Inteligencia Urbana de Brokers Connector.
Eres un perito valuador certificado con profundo conocimiento del mercado inmobiliario mexicano.

SITUACIÓN ACTIVADA: No existen comparables locales en la base de datos para este inmueble. Has activado el modo de VALUACIÓN SINTÉTICA. Usa tu conocimiento del mercado mexicano para calcular el valor estimado.

METODOLOGÍA DE VALUACIÓN:
1. Identifica la zona por el Código Postal: ciudad, colonia, estrato socioeconómico típico.
2. Aplica precios de mercado vigentes para el tipo de inmueble en esa zona.
3. Ajusta por superficie (el precio/m² varía: unidades pequeñas tienen +precio/m², grandes -precio/m²).
4. Considera el tipo de operación: venta vs. arrendamiento tienen rangos muy distintos.
5. Proporciona un rango realista (±15% en zonas conocidas, ±25% en zonas con menor certeza).

REGLAS ABSOLUTAS:
1. Jamás inventes un precio fuera del rango real del mercado mexicano.
2. El confidence_score debe reflejar HONESTAMENTE tu nivel de certeza.
3. Devuelves ÚNICAMENTE un JSON válido. Sin texto adicional, sin markdown.

ESTRUCTURA DE RESPUESTA OBLIGATORIA (JSON estricto):
{
  "estimated_price_per_sqm": 45000,
  "estimated_value": 5400000,
  "price_range_min": 4590000,
  "price_range_max": 6210000,
  "suggested_dom_days": 90,
  "confidence_score": 55,
  "explainability": "Valuación sintética basada en conocimiento de mercado para CP XXXXX. Precio estimado según zona y tipo de inmueble.",
  "pricing_verdict": "2-3 oraciones sobre el posicionamiento de precio en esa zona.",
  "buyer_psychology": "2-3 oraciones sobre el perfil y motivación del comprador típico.",
  "seller_strategy": "2-3 oraciones con estrategia recomendada para el agente.",
  "closing_argument": "1 argumento de cierre memorable que el agente puede usar.",
  "market_summary": "1 oración resumiendo el estado del mercado local."
}

REGLA DE confidence_score para Inteligencia Urbana:
- 55-65: zona principal consolidada (CDMX, GDL, MTY, QRO, MID — datos de mercado abundantes).
- 40-54: ciudad mediana o zona suburbana con actividad inmobiliaria documentada.
- 25-39: zona rural, localidad pequeña o CP con poca información de mercado disponible.
PROMPT;

    // ── Layer 3: Valuación Sintética por Inteligencia Urbana ──────────────────
    // Invocado cuando fetchComparablesCascade() retorna vacío.
    // Carga el prompt desde ai_prompts DB; fallback al constant si no existe.
    // Retorna array estructurado o null si el Orquestador no está disponible.

    private function synthesizeFromAI(array $input, int $companyId): ?array
    {
        $aiService = new AIService();

        // Resolver nombres de tipo/estado ANTES de compilar el prompt
        $propType   = PropertyType::find($input['propTypeId']);
        $propStatus = PropertyStatus::find($input['propStatusId']);
        $typeName   = $propType   ? $propType->name   : 'inmueble';
        $statusName = $propStatus ? $propStatus->name : 'operación';

        // Compiler Engine: carga y compila desde los módulos del Synaptic Core™.
        $systemPrompt = AiPrompt::compileBySlug(
            'cma_urban_intelligence',
            [
                'zipcode'     => $input['zipcode'],
                'total_area'  => (string) $input['totalArea'],
                'prop_type'   => $typeName,
                'prop_status' => $statusName,
            ],
            self::AURA_URBAN_SYSTEM_PROMPT
        );

        $userPrompt = "DATOS DEL INMUEBLE A VALUAR:\n\n"
            . "Código Postal: {$input['zipcode']}\n"
            . "Tipo de inmueble: {$typeName}\n"
            . "Operación: {$statusName}\n"
            . "Superficie total: {$input['totalArea']} m²\n"
            . ($input['priceRef'] > 0
                ? "Precio de referencia del propietario: $" . number_format($input['priceRef'], 0, '.', ',') . " MXN\n"
                : '')
            . "\nACTIVA MODO VALUACIÓN SINTÉTICA: calcula el valor de mercado para este inmueble.";

        try {
            $result = $aiService->dispatch([
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature'     => 0.2,
                'max_tokens'      => 800,
                'response_format' => ['type' => 'json_object'],
            ], $companyId);

            $raw = $result['response'] ?? '';

            // Registrar consumo de tokens del Layer 3 en ai_messages
            $this->logAiUsage($companyId, 'cma_urban_intelligence', $result);

            if ($raw === '') {
                return null;
            }

            $parsed = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
                Log::warning('BrokerBrain Layer3: respuesta IA no es JSON válido', [
                    'raw'        => substr($raw, 0, 500),
                    'company_id' => $companyId,
                ]);
                return null;
            }

            $estimatedValue  = (float) ($parsed['estimated_value']       ?? 0);
            $pricePerSqm     = (float) ($parsed['estimated_price_per_sqm'] ?? 0);
            $rangeMin        = (float) ($parsed['price_range_min']        ?? round($estimatedValue * 0.85, -3));
            $rangeMax        = (float) ($parsed['price_range_max']        ?? round($estimatedValue * 1.15, -3));
            $confidenceScore = max(1, min(100, (int) ($parsed['confidence_score'] ?? 45)));

            if ($estimatedValue <= 0 || $pricePerSqm <= 0) {
                Log::warning('BrokerBrain Layer3: AURA devolvió valores numéricos inválidos', [
                    'estimated_value'    => $estimatedValue,
                    'price_per_sqm'      => $pricePerSqm,
                    'company_id'         => $companyId,
                ]);
                return null;
            }

            return [
                'estimated_value'      => round($estimatedValue, -3),
                'price_per_sqm'        => round($pricePerSqm, 2),
                'price_range_min'      => round($rangeMin, -3),
                'price_range_max'      => round($rangeMax, -3),
                'suggested_dom_days'   => max(0, (int) ($parsed['suggested_dom_days'] ?? 0)),
                'confidence_score'     => $confidenceScore,
                'explainability'       => (string) ($parsed['explainability']  ?? 'Valuación por inteligencia urbana — sin comparables locales.'),
                'pricing_verdict'      => (string) ($parsed['pricing_verdict']  ?? ''),
                'buyer_psychology'     => (string) ($parsed['buyer_psychology'] ?? ''),
                'seller_strategy'      => (string) ($parsed['seller_strategy']  ?? ''),
                'closing_argument'     => (string) ($parsed['closing_argument'] ?? ''),
                'market_summary'       => (string) ($parsed['market_summary']   ?? ''),
            ];

        } catch (\RuntimeException $e) {
            Log::warning('BrokerBrain Layer3: Orquestador sin proveedores activos', [
                'error'      => $e->getMessage(),
                'company_id' => $companyId,
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('BrokerBrain Layer3: error inesperado en síntesis IA', [
                'error'      => $e->getMessage(),
                'company_id' => $companyId,
            ]);
            return null;
        }
    }

    // ── Confidence score por capa de datos ───────────────────────────────────

    private function deriveConfidence(int $layer, int $comparableCount): int
    {
        if ($layer === 1) {
            // Layer 1: datos exactos — alta confianza
            if ($comparableCount >= 10) return 95;
            if ($comparableCount >= 5)  return 88;
            return 80;
        }
        // Layer 2: zona ampliada — confianza reducida
        if ($comparableCount >= 5) return 72;
        if ($comparableCount >= 3) return 65;
        return 55;
    }

    // ── Explainability string por capa ───────────────────────────────────────

    private function deriveExplainability(int $layer, int $fallbackLevel, int $count, string $zipcode): string
    {
        $prefix = substr($zipcode, 0, 3);

        if ($layer === 1) {
            return "Valuación basada en {$count} comparable" . ($count !== 1 ? 's' : '')
                . " directos del CP {$zipcode} (Bolsa BBC General). Alta precisión geográfica.";
        }

        $sources = [
            1 => "comparables de la región postal {$prefix}XX (BBC General)",
            2 => "comparables de la región {$prefix}XX (sin filtro de bolsa)",
            3 => "comparables sin restricción de zona (último recurso de datos)",
        ];

        $source = $sources[$fallbackLevel] ?? "comparables de búsqueda ampliada";

        return "Valuación basada en {$count} {$source}. "
            . "Sin datos suficientes en CP {$zipcode} — precisión geográfica reducida.";
    }

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

        $fallbackLevel = (int) ($cmaResult['fallback_level'] ?? 0);
        if ($fallbackLevel >= 2) {
            $userPrompt .= "\n⚠ Contexto de búsqueda: los comparables provienen de una búsqueda ampliada (nivel {$fallbackLevel}/3 — sin filtros de zona ni bolsa BBC). La precisión geográfica es reducida; refleja esto con un confidence_score más bajo.";
        } elseif ($fallbackLevel === 1) {
            $userPrompt .= "\nNota: los comparables son de la zona postal ampliada (prefijo 3-dígitos del CP), no del CP exacto.";
        }

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

            // Registrar consumo de tokens de la narrativa AURA en ai_messages
            $this->logAiUsage($companyId, 'cma_narrative_aura', $result);

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

    private function interpolateSyntheticFallback(array $input): array
    {
        $cp      = (string) $input['zipcode'];
        $prefix2 = (int) substr($cp, 0, 2);
        $area    = max(1.0, (float) $input['totalArea']);

        $bands = [
            [ 1,  6,  60000, 100000, 'CDMX Centro'],
            [ 7, 16,  35000,  80000, 'CDMX Periferia'],
            [17, 19,  20000,  45000, 'CDMX Limite / Estado de Mexico'],
            [20, 25,  12000,  22000, 'Aguascalientes / Zacatecas'],
            [36, 38,  14000,  28000, 'Guanajuato'],
            [40, 43,  14000,  30000, 'San Luis Potosi / Zacatecas'],
            [44, 49,  16000,  38000, 'ZMG Guadalajara'],
            [50, 57,  12000,  25000, 'Estado de Mexico'],
            [58, 61,  14000,  28000, 'Michoacan'],
            [62, 63,  18000,  40000, 'Morelos (Cuernavaca)'],
            [64, 67,  18000,  45000, 'ZMM Monterrey'],
            [68, 69,  12000,  22000, 'Nuevo Leon Interior'],
            [76, 77,  16000,  36000, 'Queretaro'],
            [80, 82,  14000,  28000, 'Sinaloa'],
            [83, 85,  14000,  30000, 'Sonora'],
            [86, 87,  12000,  22000, 'Tabasco / Chiapas Norte'],
            [97, 97,  14000,  32000, 'Merida / Yucatan'],
        ];

        $minPsqm  = 10000;
        $maxPsqm  = 18000;
        $zoneDesc = 'zona sin banda definida (referencia nacional minima)';

        foreach ($bands as [$lo, $hi, $min, $max, $desc]) {
            if ($prefix2 >= $lo && $prefix2 <= $hi) {
                $minPsqm  = $min;
                $maxPsqm  = $max;
                $zoneDesc = $desc;
                break;
            }
        }

        $midPsqm        = ($minPsqm + $maxPsqm) / 2;
        $estimatedValue = round($midPsqm * $area, -3);

        return [
            'estimated_value'    => $estimatedValue,
            'price_per_sqm'      => round($midPsqm, 2),
            'price_range_min'    => round($minPsqm * $area, -3),
            'price_range_max'    => round($maxPsqm * $area, -3),
            'suggested_dom_days' => 120,
            'confidence_score'   => 18,
            'explainability'     => "Modo de ultimo recurso: sin comparables en DB y sin proveedor IA activo. Interpolacion por bandas regionales ({$zoneDesc}). CP: {$cp}. Confianza minima.",
            'pricing_verdict'    => 'Precio estimado por interpolacion regional. Validar con fuentes externas.',
            'buyer_psychology'   => 'Analisis de perfil comprador no disponible por ausencia de datos locales.',
            'seller_strategy'    => 'Consultar valuador certificado para respaldar estrategia de precio.',
            'closing_argument'   => 'Esta propiedad merece una tasacion formal para negociar con certeza.',
            'market_summary'     => "Sin datos suficientes en CP {$cp}. Estimacion regional de referencia.",
        ];
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    // ── Registro de consumo de tokens en ai_messages ──────────────────────────
    // Crea un ai_conversations sintético por llamada CMA para que el Monitor
    // de Consumo del Super Admin pueda agregar tokens por tenant.
    // Silencioso por diseño: un fallo de logging no puede detener la tasación.

    private function logAiUsage(int $companyId, string $context, array $aiResult): void
    {
        $tokensUsed = (int) ($aiResult['tokens_used'] ?? 0);

        if ($tokensUsed <= 0) {
            return;
        }

        try {
            $conv = AiConversation::create([
                'company_id' => $companyId,
                'title'      => 'AURA CMA · ' . $context,
                'status'     => 1,
            ]);

            AiMessage::create([
                'conversation_id' => $conv->id,
                'role'            => 'assistant',
                'content'         => mb_substr($aiResult['response'] ?? '', 0, 5000),
                'tokens_used'     => $tokensUsed,
            ]);
        } catch (\Throwable $e) {
            Log::warning('BrokerBrain: logAiUsage falló (no crítico)', [
                'context'    => $context,
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
