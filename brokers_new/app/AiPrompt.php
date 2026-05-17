<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * AiPrompt — Synaptic Core™: Prompts Modulares del Motor Cognitivo AVM.
 *
 * ARQUITECTURA DE COMPILACIÓN:
 *   Si system_role está poblado → compile() ensambla desde módulos.
 *   Si system_role está vacío   → compile() devuelve prompt_text (modo legacy).
 *
 * INYECCIÓN DE VARIABLES: {{variable}} en cualquier módulo es reemplazado
 *   por el valor correspondiente en el array $variables de compile().
 *
 * VERSION CONTROL: cada save archiva la versión anterior en ai_prompt_versions.
 *
 * Columnas verificadas contra knowledge/02_SYSTEM_CODEX_REGISTRY.md:
 *   ai_prompts.slug             → identificador único (no reserva MySQL)
 *   ai_prompts.name             → nombre legible para el panel
 *   ai_prompts.prompt_text      → prompt monolítico (fallback legacy)
 *   ai_prompts.system_role      → identidad y persona del agente IA
 *   ai_prompts.business_context → contexto situacional del negocio
 *   ai_prompts.immutable_rules  → reglas que el modelo no puede violar
 *   ai_prompts.tone_profile     → JSON: formalidad, idioma, perspectiva, estilo
 *   ai_prompts.output_schema    → JSON: estructura obligatoria de la respuesta
 *   ai_prompts.variables_schema → JSON array: variables inyectables por el caller
 *   ai_prompts.preferred_model  → modelo preferido (null = sistema por defecto)
 *   ai_prompts.version          → contador de versiones (auto-incrementado en save)
 *   ai_prompts.is_active        → flag de activación
 */
class AiPrompt extends Model
{
    protected $fillable = [
        'slug', 'name', 'prompt_text',
        'system_role', 'business_context', 'immutable_rules',
        'tone_profile', 'output_schema', 'variables_schema',
        'preferred_model', 'version', 'is_active',
    ];

    protected $casts = [
        'tone_profile'    => 'array',
        'output_schema'   => 'array',
        'variables_schema'=> 'array',
        'is_active'       => 'boolean',
    ];

    // ── Compiler Engine ───────────────────────────────────────────────────────
    // Ensambla el system prompt final desde los módulos del Synaptic Core™.
    // Si system_role está vacío → modo legacy (devuelve prompt_text).

    public function compile(array $variables = []): string
    {
        $role = trim($this->system_role ?? '');

        if ($role === '') {
            // Modo legacy: prompt_text monolítico
            return $this->injectVariables($this->prompt_text ?? '', $variables);
        }

        $sections = [];

        // 1. Identidad del agente
        $sections[] = $role;

        // 2. Contexto del negocio
        if (!empty(trim($this->business_context ?? ''))) {
            $sections[] = "## CONTEXTO DEL NEGOCIO\n" . trim($this->business_context);
        }

        // 3. Directrices de tono (derivadas del tone_profile JSON)
        $tone = $this->tone_profile;
        if (is_array($tone) && !empty($tone)) {
            $lines = [];
            if (!empty($tone['language']))              $lines[] = 'Idioma: '      . $tone['language'];
            if (!empty($tone['formality']))             $lines[] = 'Formalidad: '  . $tone['formality'];
            if (!empty($tone['perspective']))           $lines[] = 'Perspectiva: ' . $tone['perspective'];
            if (!empty($tone['style']) && is_array($tone['style'])) {
                $lines[] = 'Estilo: ' . implode(', ', $tone['style']);
            }
            if ($lines) {
                $sections[] = "## DIRECTRICES DE TONO\n" . implode("\n", $lines);
            }
        }

        // 4. Reglas inmutables
        if (!empty(trim($this->immutable_rules ?? ''))) {
            $sections[] = "## REGLAS INMUTABLES\n" . trim($this->immutable_rules);
        }

        // 5. Output schema (estructura JSON obligatoria)
        $schema = $this->output_schema;
        if (is_array($schema) && !empty($schema)) {
            $sections[] = "## ESTRUCTURA DE RESPUESTA OBLIGATORIA (JSON estricto)\n"
                . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $assembled = implode("\n\n", $sections);

        return $this->injectVariables($assembled, $variables);
    }

    private function injectVariables(string $text, array $variables): string
    {
        if (empty($variables) || $text === '') {
            return $text;
        }
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }

    // ── Helpers estáticos ─────────────────────────────────────────────────────

    /**
     * Devuelve el prompt compilado (sin variables) para un slug.
     * Backward-compatible con código que usaba getBySlug().
     */
    public static function getBySlug(string $slug, string $fallback = ''): string
    {
        try {
            $record = static::where('slug', $slug)->first();
            if (!$record) return $fallback;
            $compiled = $record->compile();
            return $compiled !== '' ? $compiled : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Devuelve el prompt compilado CON inyección de variables.
     * Usado por BrokerBrainController::synthesizeFromAI().
     */
    public static function compileBySlug(string $slug, array $variables = [], string $fallback = ''): string
    {
        try {
            $record = static::where('slug', $slug)->first();
            if (!$record) return $fallback;
            $compiled = $record->compile($variables);
            return $compiled !== '' ? $compiled : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
