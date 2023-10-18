<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meterial extends Model
{
    use HasFactory;
    
    protected $table = 'meterial';

    protected $fillable = [
        'id',
        'name',
        'unique_no',
        'category',
        'duration',
        'size',
        'frames'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:i:s',
        'updated_at' => 'datetime:Y-m-d h:i:s',
    ];
}
