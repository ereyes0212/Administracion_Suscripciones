<?php

namespace App\Services;

use App\Models\Suscripcion;
use Illuminate\Support\Facades\Log;

class ProcesarSuscripciones
{
    public function procesar()
    {
        Log::info('Iniciando el procesamiento de suscripciones...');

        // Obtén todas las suscripciones activas
        $suscripciones = Suscripcion::where('estado', 'Activo')->get();

        foreach ($suscripciones as $suscripcion) {
            $frecuencia = strtolower($suscripcion->tipo_recurrencia); // diario, semanal, mensual, anual

            // Verifica si es el momento de procesar la suscripción
            if ($this->esTiempoDeProcesar($suscripcion, $frecuencia)) {
                // Llama al método para procesar el pago
                $resultado = $this->procesarPago($suscripcion);

                // Actualiza el estado de la suscripción
                if ($resultado['status'] === 'success') {
                    $suscripcion->ultimo_pago = now();
                    $suscripcion->save();
                    Log::info("Pago procesado exitosamente para la suscripción ID: {$suscripcion->id}");
                } else {
                    Log::warning("Fallo al procesar el pago para la suscripción ID: {$suscripcion->id}");
                }
            }
        }

        Log::info('Procesamiento de suscripciones finalizado.');
    }

    // Función que verifica si es tiempo de procesar la suscripción
    private function esTiempoDeProcesar($suscripcion, $frecuencia)
    {
        $ultimoPago = $suscripcion->ultimo_pago ?? $suscripcion->created_at;

        switch ($frecuencia) {
            case 'diario':
                return now()->diffInDays($ultimoPago) >= 1;
            case 'semanal':
                return now()->diffInWeeks($ultimoPago) >= 1;
            case 'mensual':
                return now()->diffInMonths($ultimoPago) >= 1;
            case 'anual':
                return now()->diffInYears($ultimoPago) >= 1;
            default:
                return false;
        }
    }

    // Función para procesar el pago
    private function procesarPago($suscripcion)
    {
        try {
            // Simulación del procesamiento
            // En producción, llama a tu pasarela de pagos aquí
            return [
                'status' => 'success',
                'message' => 'Pago procesado exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error("Error al procesar el pago: " . $e->getMessage());
            return [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
        }
    }
}
