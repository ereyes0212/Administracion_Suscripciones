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
            $table->id();  // Campo 'id' como clave primaria
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');  // Relación con la tabla clientes
            $table->decimal('monto', 10, 2)->default(0.00);  // Monto de la suscripción
            $table->string('token_pago')->nullable();  // Token de pago de la pasarela
            $table->enum('estado', ['activo', 'inactivo', 'pendiente', 'suspendido'])->default('activo');  // Estado de la suscripción
            $table->enum('tipo_recurrencia', ['diario', 'semanal', 'mensual', 'anual']);  // Tipo de recurrencia
            $table->timestamp('fecha_inicio')->useCurrent();  // Fecha de inicio de la suscripción
            $table->timestamp('fecha_renovacion')->nullable();  // Fecha de la próxima renovación
            $table->timestamps();  // Campos 'created_at' y 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones');  // Elimina la tabla 'suscripciones'
    }
}
