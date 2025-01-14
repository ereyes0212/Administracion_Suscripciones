<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    // Define la tabla asociada a este modelo
    protected $table = 'clientes';

    // Especifica los campos que pueden ser asignados masivamente
    protected $fillable = [
        'nombre',
        'correo',
        'direccion',
        'ciudad',
        'pais',
        'telefono',
    ];

    /**
     * Relación: Un cliente puede tener muchas suscripciones.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class);
    }
}

