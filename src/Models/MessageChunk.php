<?php

namespace ElliottLawson\Converse\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageChunk extends Model
{
    use HasFactory;
    
    const UPDATED_AT = null;
    
    protected $fillable = [
        'content',
        'sequence',
        'metadata',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
    
    protected static function newFactory()
    {
        return \ElliottLawson\Converse\Database\Factories\MessageChunkFactory::new();
    }
    
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}