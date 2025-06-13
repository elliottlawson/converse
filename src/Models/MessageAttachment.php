<?php

namespace ElliottLawson\Converse\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = [
        'type',
        'path',
        'mime_type',
        'size',
        'metadata',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
    ];
    
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}