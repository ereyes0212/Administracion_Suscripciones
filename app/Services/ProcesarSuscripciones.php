<?php
namespace App\Services;

use App\Models\Suscripcion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcesarSuscripciones
{
    public function procesar()
    {
        Log::info('Iniciando el procesamiento de suscripciones...');

        // Obtén todas las suscripciones activas
        $suscripciones = Suscripcion::where('estado', 'Activo')->get();

        foreach ($suscripciones as $suscripcion) {
            // Verifica si hoy es la fecha de renovación
            if ($this->esTiempoDeProcesar($suscripcion)) {
                // Procesar el pago
                $resultado = $this->procesarPago($suscripcion);

                if ($resultado['status'] === 'success') {
                    $this->actualizarFechaRenovacion($suscripcion); // Actualiza la fecha de renovación
                    $this->enviarWebhook($suscripcion->token_pago, 1, $suscripcion->fecha_renovacion);

                    Log::info("Pago procesado exitosamente para la suscripción ID: {$suscripcion->id}");
                } else {
                    $this->enviarWebhook($suscripcion->token_pago, 2, $suscripcion->fecha_renovacion);

                    Log::warning("Fallo al procesar el pago para la suscripción ID: {$suscripcion->id}");
                }
            }
        }

        Log::info('Procesamiento de suscripciones finalizado.');
    }

    // Función que verifica si hoy es la fecha de renovación
    private function esTiempoDeProcesar($suscripcion)
    {
        $fechaHoy = now()->startOfDay(); // Fecha actual sin hora
        $fechaRenovacion = $suscripcion->fecha_renovacion->startOfDay(); // Fecha de renovación sin hora

        return $fechaHoy->equalTo($fechaRenovacion); // Compara las fechas sin considerar horas
    }

    // Actualiza la fecha de renovación según la frecuencia
    private function actualizarFechaRenovacion($suscripcion)
    {
        switch (strtolower($suscripcion->tipo_recurrencia)) {
            case 'diario':
                $suscripcion->fecha_renovacion = $suscripcion->fecha_renovacion->addDay();
                break;
            case 'semanal':
                $suscripcion->fecha_renovacion = $suscripcion->fecha_renovacion->addWeek();
                break;
            case 'mensual':
                $suscripcion->fecha_renovacion = $suscripcion->fecha_renovacion->addMonth();
                break;
            case 'anual':
                $suscripcion->fecha_renovacion = $suscripcion->fecha_renovacion->addYear();
                break;
            default:
                Log::warning("Frecuencia desconocida para la suscripción ID: {$suscripcion->id}");
        }

        $suscripcion->save(); // Guarda los cambios en la base de datos
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

// Función para enviar datos al webhook
private function enviarWebhook($subscriptionId, $code, $fecha_proximo_cobro)
{
    $endpoint = 'http://host.docker.internal/servicio_suscripcion/wp-admin/admin-ajax.php?action=pixelpay-webhook';


    try {
        // Intentar realizar la solicitud HTTP
        $response = Http::post($endpoint, [
            'subscription_id' => $subscriptionId,
            'code' => $code,
            'date' => $fecha_proximo_cobro,
        ]);

        // Verificar si la respuesta fue exitosa
        if ($response->successful()) {
            Log::info("Webhook enviado con éxito para la suscripción ID: $subscriptionId con código $code");
        } else {
            // Si la respuesta no fue exitosa, registrar el error con el cuerpo de la respuesta
            Log::error("Error al enviar el webhook para la suscripción ID: $subscriptionId. Respuesta: " . $response->body());
        }
    } catch (\Exception $e) {
        // Capturar cualquier excepción que ocurra durante la ejecución
        Log::error("Error al enviar el webhook para la suscripción ID: $subscriptionId. Detalle: " . $e->getMessage());
    }
}


}
