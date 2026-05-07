<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $table = 'ai_settings';

    protected $fillable = [
        'provider_name',
        'api_key',
        'extra_config',
        'priority_order',
        'is_active',
        'company_id',
    ];

    protected $casts = [
        'extra_config'   => 'array',
        'is_active'      => 'boolean',
        'priority_order' => 'integer',
    ];

    // api_key nunca se serializa al exterior (encriptada en BD)
    protected $hidden = ['api_key'];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    // Devuelve la key desencriptada solo en capa de servicio (nunca en vistas)
    public function decryptedKey(): string
    {
        return decrypt($this->api_key);
    }
}
