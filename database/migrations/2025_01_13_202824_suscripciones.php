<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Suscripciones extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->uuid('id')->primary();  // Clave primaria UUID
            $table->uuid('cliente_id');  // Clave foránea UUID
            $table->decimal('monto', 10, 2)->default(0.00);  
            $table->string('token_pago')->nullable();  
            $table->enum('estado', ['activo', 'inactivo', 'pendiente', 'suspendido'])->default('activo');  
            $table->enum('tipo_recurrencia', ['diario', 'semanal', 'mensual', 'anual']);  
            $table->timestamp('fecha_inicio')->useCurrent();  
            $table->timestamp('fecha_ultimo_pago')->nullable();  
            $table->timestamp('fecha_renovacion')->nullable();  
            $table->timestamps();  

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones');  
    }
}
