<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function messages()
    {
        return $this->hasMany('App\AiMessage', 'conversation_id');
    }
}
