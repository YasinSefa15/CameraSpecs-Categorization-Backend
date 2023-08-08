<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'headquarters',
        'is_major',
    ];

    protected $casts = [
        'is_major' => 'boolean',
    ];

    public function images()
    {
        return $this->morphOne('App\Models\Image', 'taggable');
    }

    public function cameras()
    {
        return $this->hasMany(Camera::class);
    }
}
