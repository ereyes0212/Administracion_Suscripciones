<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    use HasFactory;

    protected $table = 'suscripciones';

    protected $fillable = [
        'cliente_id',
        'monto',
        'token_pago',
        'estado',
        'tipo_recurrencia',
        'fecha_inicio',
        'fecha_renovacion',
    ];

    // Convierte 'fecha_renovacion' a un objeto Carbon automáticamente
    protected $casts = [
        'fecha_renovacion' => 'datetime',
        'fecha_inicio' => 'datetime',  // Asegúrate de que 'fecha_inicio' también sea tratado como datetime si es necesario
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
