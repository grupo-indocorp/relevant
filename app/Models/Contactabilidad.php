<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contactabilidad extends Model
{
    use HasFactory;

    // Relación uno a uno
    public function comentarios()
    {
        return $this->hasMany(Comentario::class);
    }
}
