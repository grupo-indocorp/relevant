<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exportcliente extends Model
{
    use HasFactory;

    // Relación uno a uno
    public function cliente()
    {
        return $this->hasOne(Cliente::class);
    }

    // Relación uno a muchos inversa
    public function user()
    {
        return $this->belongsTo(User::class, 'ejecutivo_id');
    }
}
