<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Membresias extends Migration
{
    public function up(): void
    {
        Schema::create('membresias', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();  // Cambiar de char(36) a unsignedBigInteger
            $table->string('nombre');
            $table->decimal('precio', 10, 2);
            $table->enum('tipo_recurrencia', ['diario', 'semanal', 'mensual', 'anual']);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membresias');
    }
}
