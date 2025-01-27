<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Suscripciones extends Migration
{
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->char('id', 36)->primary();  // UUID
            $table->char('cliente_id', 36);  // UUID, debe coincidir con el tipo de 'id' en clientes
            $table->unsignedBigInteger('membresia_id')->nullable();  // Cambiado a unsignedBigInteger, debe coincidir con 'id' en membresias
            $table->decimal('monto', 10, 2)->default(0.00);
            $table->string('token_pago')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'pendiente', 'suspendido'])->default('activo');
            $table->timestamp('fecha_inicio')->useCurrent();
            $table->timestamp('fecha_ultimo_pago')->nullable();
            $table->timestamp('fecha_renovacion')->nullable();
            $table->timestamp('fecha_finalizacion')->nullable();  // Nueva columna que permite valores NULL
            $table->timestamps();
    
            // Definición de claves foráneas
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('membresia_id')->references('id')->on('membresias')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
}
