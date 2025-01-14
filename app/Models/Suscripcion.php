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