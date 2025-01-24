<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ordenes extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('cliente_id', 36);
            $table->char('suscripcion_id', 36)->nullable();
            $table->string('orden_id_wp', 100)->nullable();
            $table->enum('estado', ['Pagado', 'Rechazado', 'Pendiente'])->default('Pendiente');
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            // Definición de claves foráneas
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('suscripcion_id')->references('id')->on('suscripciones')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
}
