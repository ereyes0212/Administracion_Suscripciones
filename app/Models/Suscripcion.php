<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Suscripcion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'suscripciones';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'cliente_id',
        'membresia_id',
        'monto',
        'token_pago',
        'estado',
        'recurrencia', // Nuevo campo agregado
        'fecha_inicio',
        'fecha_ultimo_pago',
        'fecha_renovacion',
        'fecha_finalizacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_ultimo_pago' => 'datetime',
        'fecha_renovacion' => 'datetime',
        'fecha_finalizacion' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'suscripcion_id');
    }
}
