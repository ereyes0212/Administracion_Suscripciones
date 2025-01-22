<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ordenes extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ordenes'; // Nombre de la tabla

    protected $primaryKey = 'id'; // Clave primaria

    public $incrementing = false; // Indica que no es un campo autoincremental (UUID)

    protected $keyType = 'string'; // Tipo de la clave primaria (UUID es string)

    protected $fillable = [
        'cliente_id',
        'suscripcion_id',
        'orden_id_wp', 
        'estado',
        'fecha',
    ];

    protected $casts = [
        'id' => 'string',
        'cliente_id' => 'string',
        'suscripcion_id' => 'string',
        'orden_id_wp' => 'string',
        'fecha' => 'datetime',
    ];

    /**
     * Relación con el modelo Cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
    }

    /**
     * Relación con el modelo Suscripcion.
     */
    public function suscripcion()
    {
        return $this->belongsTo(Suscripcion::class, 'suscripcion_id', 'id');
    }
}
