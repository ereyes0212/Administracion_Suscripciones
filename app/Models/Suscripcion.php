<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Suscripcion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'suscripciones';  // Nombre de la tabla

    protected $primaryKey = 'id';  // Clave primaria

    public $incrementing = false;  // Indicar que la clave primaria UUID no es autoincremental

    protected $keyType = 'string';  // Tipo de clave primaria (UUID)

    protected $fillable = [
        'cliente_id',
        'monto',
        'token_pago',
        'estado',
        'tipo_recurrencia',
        'fecha_inicio',
        'fecha_ultimo_pago',
        'fecha_renovacion',
    ];

    // Convierte 'fecha_renovacion', 'fecha_inicio', y 'fecha_ultimo_pago' a objetos Carbon automáticamente
    protected $casts = [
        'fecha_renovacion' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_ultimo_pago' => 'datetime',
    ];

    /**
     * Relación: Una suscripción pertenece a un cliente.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
