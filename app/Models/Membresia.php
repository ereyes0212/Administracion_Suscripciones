<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $table = 'membresias';

    // Cambiar el tipo de clave primaria a 'int'
    protected $keyType = 'int';

    // Habilitar auto-incremento ya que el ID es un entero
    public $incrementing = true;

    protected $fillable = [
        'id',
        'nombre',
        'precio',
        'tipo_recurrencia',
        'descripcion',
    ];

    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class, 'membresia_id');
    }
}
