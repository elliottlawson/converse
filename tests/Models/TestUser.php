<?php

namespace ElliottLawson\Converse\Tests\Models;

use ElliottLawson\Converse\Traits\HasAIConversations;
use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    use HasAIConversations;

    protected $fillable = ['name', 'email'];

    protected $table = 'users';
}