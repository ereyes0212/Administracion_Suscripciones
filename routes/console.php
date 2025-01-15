<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Suscripcion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

use App\Services\ProcesarSuscripciones; // Importa el servicio

// Comando para la inspiración
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Programación para el procesamiento de pagos
// Programación para el procesamiento de pagos
Schedule::call(function () {
    $procesarSuscripciones = new ProcesarSuscripciones(); // Instancia la clase
    $procesarSuscripciones->procesar(); // Llama al método para procesar
})->everyMinute(); // Puedes configurar la frecuencia que desees aquí