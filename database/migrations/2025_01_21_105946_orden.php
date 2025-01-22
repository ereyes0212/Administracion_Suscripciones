<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->uuid('id')->primary();  // Clave primaria UUID
            $table->uuid('cliente_id');  // Clave foránea UUID
            $table->uuid('suscripcion_id')->nullable();  // Permitir valores nulos
            $table->string('orden_id_wp', 100)->nullable();
            $table->enum('estado', ['Pagado', 'Rechazado', 'Pendiente'])->default('Pendiente');  
            $table->timestamp('fecha')->useCurrent();  
            $table->timestamps();  

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('suscripcion_id')->references('id')->on('suscripciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
