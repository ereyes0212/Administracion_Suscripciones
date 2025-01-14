<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Clientes extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();  // Campo 'id' como clave primaria
            $table->string('nombre');  // Nombre del cliente
            $table->string('correo')->unique();  // Correo electrónico único
            $table->string('direccion')->nullable();  // Dirección del cliente
            $table->string('ciudad')->nullable();  // Ciudad
            $table->string('pais')->nullable();  // País
            $table->string('telefono')->nullable();  // Teléfono
            $table->timestamps();  // Campos 'created_at' y 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');  // Elimina la tabla 'clientes'
    }
}
