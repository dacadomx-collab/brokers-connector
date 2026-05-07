<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    protected $table = 'payment_gateway_settings';

    protected $fillable = [
        'provider_name',
        'is_active',
        'is_sandbox',
        'credentials',
        'company_id',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_sandbox' => 'boolean',
    ];

    // credentials nunca se serializa — contiene llaves encriptadas
    protected $hidden = ['credentials'];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    // Devuelve las credenciales desencriptadas como array
    public function decryptedCredentials(): array
    {
        if (! $this->credentials) {
            return [];
        }
        try {
            return json_decode(decrypt($this->credentials), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    // Devuelve un array con los valores enmascarados para el frontend
    public function maskedCredentials(): array
    {
        $plain = $this->decryptedCredentials();
        return array_map(fn ($v) => str_repeat('•', 8) . substr((string) $v, -4), $plain);
    }
}
