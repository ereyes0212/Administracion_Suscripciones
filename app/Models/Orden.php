<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Orden extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ordenes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'cliente_id',
        'suscripcion_id',
        'orden_id_wp',
        'estado',
        'fecha',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function suscripcion()
    {
        return $this->belongsTo(Suscripcion::class, 'suscripcion_id');
    }
}
