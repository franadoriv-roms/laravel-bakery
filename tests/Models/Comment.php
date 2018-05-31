<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\BakeryMutable;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use BakeryMutable;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'body',
        'user',
        'article',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
