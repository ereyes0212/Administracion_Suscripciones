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
            $table->uuid('id')->primary();  // Clave primaria UUID
            $table->string('nombre');  
            $table->string('correo')->unique();  
            $table->string('direccion')->nullable();  
            $table->string('ciudad')->nullable();  
            $table->string('pais')->nullable();  
            $table->string('telefono')->nullable();  
            $table->timestamps();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');  
    }
}
