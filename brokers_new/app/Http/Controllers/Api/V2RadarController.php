<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * V2RadarController — Radar de Plusvalía y Crecimiento Urbano
 *
 * Auth: Bearer session_token validado contra Cache (patrón Bridge V2).
 *       NO usa Passport ni auth:api — es el mismo patrón de V2BridgeController.
 *
 * Fase 2-A: Radar estático — computa desde la tabla `properties` en vivo
 *           y cachea resultados en `ai_zone_heatmaps` (TTL 24 h).
 * Fase 2-B: Radar predictivo — alimentado por `ai_property_price_snapshots`
 *           y `ai_market_trends` una vez que acumulen datos históricos.
 *
 * Mandamiento #2 y #7: toda query filtra estrictamente por company_id del tenant.
 */
class V2RadarController extends Controller
{
    // ── Heatmap general ──────────────────────────────────────────

    /**
     * GET /api/v2/radar/heatmap
     *
     * Devuelve todas las zonas activas del tenant con heat_score y métricas de mercado.
     * Sirve datos desde ai_zone_heatmaps (caché 24 h) si la query no está filtrada.
     */
    public function heatmap(Request $request)
    {
        $session = $this->requireSession($request);
        if ($session instanceof \Illuminate\Http\JsonResponse) {
            return $session;
        }

        $companyId    = (int) $session['company_id'];
        $minListings  = max(1, (int) $request->query('min_listings', 1));
        $propStatusId = $request->query('prop_status_id');
        $propTypeId   = $request->query('prop_type_id');
        $fresh        = filter_var($request->query('fresh', false), FILTER_VALIDATE_BOOLEAN);

        // Caché solo aplica cuando no hay filtros activos
        if (!$fresh && !$propStatusId && !$propTypeId) {
            $cached = $this->getCachedHeatmap($companyId);
            if ($cached !== null) {
                return response()->json($cached);
            }
        }

        $zones = $this->computeZones($companyId, $minListings, $propStatusId, $propTypeId);

        if ($zones->isEmpty()) {
            return response()->json([
                'success' => false,
                'error'   => 'No hay zonas con propiedades activas para analizar.',
            ], 404);
        }

        $zones = $this->enrichZones($zones);

        // Persistir en caché solo para queries sin filtros
        if (!$propStatusId && !$propTypeId) {
            $this->persistZones($companyId, $zones);
        }

        return response()->json($this->buildHeatmapResponse($companyId, $zones));
    }

    // ── Detalle de zona ──────────────────────────────────────────

    /**
     * GET /api/v2/radar/zone/{zipcode}
     *
     * Análisis detallado de una zona específica: desglose por tipo de propiedad,
     * series de tendencia (de ai_market_trends si disponibles) e insight de AURA.
     */
    public function zoneDetail(Request $request, $zipcode)
    {
        $session = $this->requireSession($request);
        if ($session instanceof \Illuminate\Http\JsonResponse) {
            return $session;
        }

        $companyId = (int) $session['company_id'];
        $zipcode   = (int) $zipcode;

        $baseQuery = DB::table('properties')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('published', 1)
            ->where('zipcode', $zipcode);

        $activeCount = (clone $baseQuery)->count();

        if ($activeCount === 0) {
            return response()->json([
                'success' => false,
                'error'   => 'No hay propiedades activas en esta zona.',
            ], 404);
        }

        $stats = (clone $baseQuery)->select(
            DB::raw('AVG(price) as avg_price'),
            DB::raw('AVG(CASE WHEN built_area > 0 THEN price / built_area WHEN total_area > 0 THEN price / total_area ELSE NULL END) as avg_price_per_m2'),
            DB::raw('MIN(price) as min_price'),
            DB::raw('MAX(price) as max_price'),
            DB::raw("AVG(CASE WHEN lat <> '' AND lat IS NOT NULL THEN CAST(lat AS DECIMAL(10,7)) ELSE NULL END) as center_lat"),
            DB::raw("AVG(CASE WHEN lng <> '' AND lng IS NOT NULL THEN CAST(lng AS DECIMAL(10,7)) ELSE NULL END) as center_lng")
        )->first();

        $byType = DB::table('properties as p')
            ->join('property_types as pt', 'p.prop_type_id', '=', 'pt.id')
            ->where('p.company_id', $companyId)
            ->whereNull('p.deleted_at')
            ->where('p.published', 1)
            ->where('p.zipcode', $zipcode)
            ->select(
                'pt.name as type',
                DB::raw('COUNT(p.id) as count'),
                DB::raw('AVG(p.price) as avg_price'),
                DB::raw('AVG(CASE WHEN p.built_area > 0 THEN p.price / p.built_area WHEN p.total_area > 0 THEN p.price / p.total_area ELSE NULL END) as avg_m2')
            )
            ->groupBy('p.prop_type_id', 'pt.name')
            ->get();

        // Series de tendencia históricas (Fase 2-B — puede estar vacío en Fase 2-A)
        $trendSeries = DB::table('ai_market_trends')
            ->where('company_id', $companyId)
            ->where('zipcode', $zipcode)
            ->whereNull('prop_status_id')
            ->whereNull('prop_type_id')
            ->orderBy('period_start', 'desc')
            ->limit(6)
            ->select('period_start', 'period_end', 'avg_price_per_m2', 'trend_pct', 'sample_count')
            ->get();

        $heatmap = DB::table('ai_zone_heatmaps')
            ->where('company_id', $companyId)
            ->where('zipcode', $zipcode)
            ->first();

        return response()->json([
            'success'              => true,
            'zipcode'              => $zipcode,
            'active_listings'      => $activeCount,
            'avg_price'            => round((float) ($stats->avg_price ?? 0), 2),
            'avg_price_per_m2'     => round((float) ($stats->avg_price_per_m2 ?? 0), 2),
            'min_price'            => round((float) ($stats->min_price ?? 0), 2),
            'max_price'            => round((float) ($stats->max_price ?? 0), 2),
            'center_lat'           => $stats->center_lat ? (float) $stats->center_lat : null,
            'center_lng'           => $stats->center_lng ? (float) $stats->center_lng : null,
            'trend_series'         => $trendSeries,
            'breakdown_by_type'    => $byType->map(fn($r) => [
                'type'      => $r->type,
                'count'     => (int) $r->count,
                'avg_price' => round((float) ($r->avg_price ?? 0), 2),
                'avg_m2'    => round((float) ($r->avg_m2 ?? 0), 2),
            ])->values(),
            'heat_score'           => $heatmap ? (int) $heatmap->heat_score : null,
            'gentrification_signal'=> $heatmap ? $heatmap->gentrification_signal : 'ninguna',
            'growth_drivers'       => $heatmap ? json_decode($heatmap->growth_drivers ?? '[]') : [],
            'aura_insight'         => $heatmap ? $heatmap->aura_insight : null,
            'confidence_score'     => $heatmap ? (int) min(100, $activeCount * 10) : 0,
            'data_freshness'       => $heatmap && $heatmap->computed_at
                ? 'computed_at:' . $heatmap->computed_at
                : 'not_computed',
        ]);
    }

    // ── Privados: auth ───────────────────────────────────────────

    private function requireSession(Request $request)
    {
        $token = $this->extractBearerToken($request);

        if (!$token) {
            return response()->json(['success' => false, 'error' => 'No autenticado.'], 401);
        }

        $session = Cache::get('v2_session_' . $token);

        if (!$session) {
            return response()->json([
                'success' => false,
                'error'   => 'Sesión expirada. Regresa al panel e intenta de nuevo.',
            ], 401);
        }

        return $session;
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    // ── Privados: cómputo ────────────────────────────────────────

    private function computeZones($companyId, $minListings, $propStatusId, $propTypeId)
    {
        $query = DB::table('properties')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('published', 1)
            ->where('price', '>', 0)
            ->whereNotNull('zipcode')
            ->where('zipcode', '>=', 10000);  // zipcodes válidos MX (5 dígitos)

        if ($propStatusId) {
            $query->where('prop_status_id', (int) $propStatusId);
        }
        if ($propTypeId) {
            $query->where('prop_type_id', (int) $propTypeId);
        }

        return $query
            ->select(
                'zipcode',
                DB::raw("AVG(CASE WHEN lat <> '' AND lat IS NOT NULL THEN CAST(lat AS DECIMAL(10,7)) ELSE NULL END) as center_lat"),
                DB::raw("AVG(CASE WHEN lng <> '' AND lng IS NOT NULL THEN CAST(lng AS DECIMAL(10,7)) ELSE NULL END) as center_lng"),
                DB::raw('COUNT(id) as active_listings'),
                DB::raw('AVG(price) as avg_price'),
                DB::raw('AVG(CASE WHEN built_area > 0 THEN price / built_area WHEN total_area > 0 THEN price / total_area ELSE NULL END) as avg_price_per_m2'),
                DB::raw('MIN(price) as min_price'),
                DB::raw('MAX(price) as max_price')
            )
            ->groupBy('zipcode')
            ->havingRaw('COUNT(id) >= ?', [$minListings])
            ->orderByDesc(DB::raw('AVG(CASE WHEN built_area > 0 THEN price / built_area WHEN total_area > 0 THEN price / total_area ELSE NULL END)'))
            ->get()
            ->map(fn($r) => [
                'zipcode'         => (int) $r->zipcode,
                'center_lat'      => $r->center_lat  ? (float) $r->center_lat  : null,
                'center_lng'      => $r->center_lng  ? (float) $r->center_lng  : null,
                'active_listings' => (int) $r->active_listings,
                'avg_price'       => round((float) ($r->avg_price ?? 0), 2),
                'avg_price_per_m2'=> round((float) ($r->avg_price_per_m2 ?? 0), 2),
                'min_price'       => round((float) ($r->min_price ?? 0), 2),
                'max_price'       => round((float) ($r->max_price ?? 0), 2),
            ]);
    }

    private function enrichZones($zones)
    {
        $valid   = $zones->filter(fn($z) => $z['avg_price_per_m2'] > 0);
        $pm2List = $valid->pluck('avg_price_per_m2')->sort()->values();

        // Usar percentil 95 como techo del rango para que outliers extremos
        // (zonas con 1 propiedad de área muy pequeña) no colapsen la escala.
        $p95Idx  = max(0, (int) floor($pm2List->count() * 0.95) - 1);
        $maxPm2  = $pm2List->isEmpty() ? 1 : ($pm2List->get($p95Idx) ?? $pm2List->last() ?? 1);
        $minPm2  = $pm2List->isEmpty() ? 0 : ($pm2List->first() ?? 0);
        $range   = max(1, $maxPm2 - $minPm2);

        return $zones->map(function ($zone) use ($maxPm2, $minPm2, $range) {
            $pm2   = $zone['avg_price_per_m2'];
            // Clampear al techo del p95 para que outliers no excedan heat_score=100
            $pm2c  = $pm2 > 0 ? min($pm2, $maxPm2) : 0;
            $score = $pm2c > 0 ? (int) round((($pm2c - $minPm2) / $range) * 100) : 0;
            $count = $zone['active_listings'];

            $zone['heat_score']            = $score;
            $zone['confidence_score']      = min(100, $count * 10);
            $zone['appreciation_index']    = null;
            $zone['trend_pct']             = null;
            $zone['gentrification_signal'] = $this->gentrificationSignal($score);
            $zone['growth_drivers']        = $this->growthDrivers($score, $count);
            $zone['aura_insight']          = null;

            return $zone;
        });
    }

    private function gentrificationSignal(int $score): string
    {
        if ($score >= 76) return 'avanzada';
        if ($score >= 51) return 'moderada';
        if ($score >= 26) return 'incipiente';
        return 'ninguna';
    }

    private function growthDrivers(int $score, int $count): array
    {
        $drivers = [];
        if ($score >= 70)              $drivers[] = 'demanda_alta';
        if ($score >= 50 && $count > 5) $drivers[] = 'plusvalia_potencial';
        if ($count >= 10)              $drivers[] = 'mercado_activo';
        if ($count <= 2 && $score > 30) $drivers[] = 'zona_emergente';
        return $drivers;
    }

    // ── Privados: persistencia ────────────────────────────────────

    private function persistZones($companyId, $zones): void
    {
        $now      = now()->format('Y-m-d H:i:s');
        $validUntil = now()->addHours(24)->format('Y-m-d H:i:s');

        foreach ($zones as $zone) {
            DB::table('ai_zone_heatmaps')->updateOrInsert(
                ['company_id' => $companyId, 'zipcode' => $zone['zipcode']],
                [
                    'center_lat'            => $zone['center_lat'],
                    'center_lng'            => $zone['center_lng'],
                    'heat_score'            => $zone['heat_score'],
                    'appreciation_index'    => $zone['appreciation_index'],
                    'avg_price_per_m2'      => $zone['avg_price_per_m2'],
                    'active_listings'       => $zone['active_listings'],
                    'gentrification_signal' => $zone['gentrification_signal'],
                    'growth_drivers'        => json_encode($zone['growth_drivers']),
                    'aura_insight'          => $zone['aura_insight'],
                    'aura_prompt_slug'      => null,
                    'computed_at'           => $now,
                    'valid_until'           => $validUntil,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]
            );
        }
    }

    private function getCachedHeatmap($companyId): ?array
    {
        $zones = DB::table('ai_zone_heatmaps')
            ->where('company_id', $companyId)
            ->where('valid_until', '>', now())
            ->orderByDesc('heat_score')
            ->get();

        if ($zones->isEmpty()) {
            return null;
        }

        $hottest   = $zones->first();
        $totalListings = $zones->sum('active_listings');

        return $this->buildHeatmapResponse($companyId, $zones->map(fn($z) => [
            'zipcode'              => (int) $z->zipcode,
            'center_lat'           => $z->center_lat  ? (float) $z->center_lat  : null,
            'center_lng'           => $z->center_lng  ? (float) $z->center_lng  : null,
            'heat_score'           => (int) $z->heat_score,
            'appreciation_index'   => $z->appreciation_index ? (float) $z->appreciation_index : null,
            'avg_price'            => 0,
            'avg_price_per_m2'     => $z->avg_price_per_m2 ? round((float) $z->avg_price_per_m2, 2) : null,
            'min_price'            => 0,
            'max_price'            => 0,
            'active_listings'      => (int) $z->active_listings,
            'confidence_score'     => min(100, (int) $z->active_listings * 10),
            'gentrification_signal'=> $z->gentrification_signal,
            'growth_drivers'       => json_decode($z->growth_drivers ?? '[]', true),
            'aura_insight'         => $z->aura_insight,
            'trend_pct'            => null,
        ]), true);
    }

    private function buildHeatmapResponse($companyId, $zones, bool $fromCache = false): array
    {
        $collection = collect(is_array($zones) ? $zones : $zones->toArray());
        $hottest    = $collection->sortByDesc('heat_score')->first();

        return [
            'success'      => true,
            'generated_at' => now()->toIso8601String(),
            'cached'       => $fromCache,
            'tenant'       => ['company_id' => $companyId],
            'zones'        => $collection->values()->toArray(),
            'summary'      => [
                'total_zones'      => $collection->count(),
                'total_listings'   => $collection->sum('active_listings'),
                'hottest_zipcode'  => $hottest ? (int) $hottest['zipcode'] : null,
                'avg_price_per_m2' => round((float) ($collection->avg('avg_price_per_m2') ?? 0), 2),
            ],
        ];
    }
}
