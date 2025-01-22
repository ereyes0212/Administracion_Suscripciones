<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    // Definir la clave primaria correctamente
    protected $primaryKey = 'id';
    public $incrementing = false;  // Indicar que no es autoincremental
    protected $keyType = 'string';  // UUID es una cadena

    // Campos asignables masivamente
    protected $fillable = [
        'nombre',
        'correo',
        'direccion',
        'ciudad',
        'pais',
        'telefono',
    ];

    // Generar UUID automáticamente al crear el cliente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cliente) {
            if (empty($cliente->id) || $cliente->id == 0) {
                $cliente->id = (string) Str::uuid();  // Asignar UUID correctamente
            }
        });
    }
}