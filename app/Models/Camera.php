<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Camera extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'model',
        'megapixels',
        'year',
    ];

    public function images()
    {
        return $this->morphMany('App\Models\Image', 'taggable');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function sensor_info()
    {
        return $this->hasOne(SensorInfo::class);
    }

    public function specifications()
    {
        return $this->hasMany(Specification::class);
    }
}
