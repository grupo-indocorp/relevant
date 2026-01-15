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
        return $this->belongsTo(Cliente::class); //Antes era asi return $this->hasOne(Cliente::class);
    }

}
