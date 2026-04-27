<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_used',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo('App\AiConversation', 'conversation_id');
    }
}
