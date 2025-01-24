<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Cliente extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'clientes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'nombre',
        'correo',
        'direccion',
        'ciudad',
        'pais',
        'telefono',
    ];

    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class, 'cliente_id');
    }

    public function ordenes()
    {
        return $this->hasMany( Orden::class, 'cliente_id');
    }
}
