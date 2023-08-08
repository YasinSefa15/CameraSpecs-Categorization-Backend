<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SensorInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sensor_info';

    protected $fillable = [
        'camera_id',
        'sensor',
        'diagonal',
        'surface_area',
        'pixel_pitch',
        'pixel_area',
        'pixel_density',
    ];
}
