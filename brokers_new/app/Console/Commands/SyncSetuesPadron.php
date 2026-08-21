<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SyncSetuesPadron extends Command
{
    /**
     * Verificación puntual de UN folio contra el RAPI de SETUES BCS.
     * NO enumera IDs ni hace scraping masivo del padrón — ver Informe
     * Forense 2026-07-07: solo se persisten folio/nombre/razón social/status,
     * nunca email, teléfono, domicilio ni foto (datos personales sensibles
     * innecesarios para validar una licencia).
     *
     * @var string
     */
    protected $signature = 'setues:sync {folio : Folio de licencia a verificar, formato LIC-RAPIBCS-####}';

    /**
     * @var string
     */
    protected $description = 'Verifica un folio de licencia SETUES BCS de forma puntual y actualiza government_licenses';

    public function handle()
    {
        $folio = trim($this->argument('folio'));

        if (!preg_match('/^LIC-RAPIBCS-\d{4}$/', $folio)) {
            $this->error("Formato de folio inválido: {$folio}. Se espera LIC-RAPIBCS-####.");
            return 1;
        }

        $scriptPath = base_path('storage/scripts/setuesScraper.js');
        $nodeBinary = env('NODE_BINARY_PATH', 'node');

        $process = new Process([$nodeBinary, $scriptPath, $folio]);
        $process->setTimeout(30);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            Log::warning('setues:sync — el script Node falló', [
                'folio' => $folio,
                'error' => $e->getMessage(),
            ]);
            $this->error("No se pudo verificar el folio {$folio}. Ver logs.");
            return 1;
        }

        $payload = json_decode($process->getOutput(), true);

        if (!is_array($payload) || empty($payload['found'])) {
            $this->warn("Folio {$folio} no encontrado en el padrón SETUES.");
            return 0;
        }

        // Solo los 4 campos aprobados — email/telefono/domicilio/foto_url
        // se descartan aunque el script los devuelva.
        DB::table('government_licenses')->updateOrInsert(
            ['folio_licencia' => $folio],
            [
                'nombre_titular' => $this->sanitize($payload['nombre_titular'] ?? ''),
                'razon_social'   => $this->sanitize($payload['razon_social'] ?? null),
                'status_oficial' => $this->sanitize($payload['status_oficial'] ?? 'Activa'),
                'last_sync'      => now(),
            ]
        );

        $this->info("Folio {$folio} verificado y sincronizado.");
        return 0;
    }

    private function sanitize($value)
    {
        if ($value === null) {
            return null;
        }

        return trim(strip_tags((string) $value));
    }
}
