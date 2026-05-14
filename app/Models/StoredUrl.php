<?php

namespace App\Models;

use Database\Factories\StoredUrlFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoredUrl extends Model
{
    /** @use HasFactory<StoredUrlFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'url',
        'description',
    ];
}
