<?php

namespace App\Services;

use App\Models\Orden;
use App\Models\Suscripcion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ProcesarSuscripciones
{
    public function procesar()
    {
        try {
            // Obtén todas las suscripciones activas con fecha de renovación (recurrentes)
            $suscripcionesRecurrentes = Suscripcion::where('estado', 'Activo')
                ->whereDate('fecha_renovacion', Carbon::today()) // Compara solo la fecha sin hora
                ->with('cliente')
                ->get();
    
            // Obtén todas las suscripciones activas con fecha de finalización (no recurrentes)
            $suscripcionesNoRecurrentes = Suscripcion::where('estado', 'Activo')
                ->whereDate('fecha_finalizacion', Carbon::today()) // Compara solo la fecha sin hora
                ->with('cliente')
                ->get();
    
            Log::info('Total de suscripciones recurrentes encontradas: ' . $suscripcionesRecurrentes->count());
            Log::info('Total de suscripciones no recurrentes encontradas: ' . $suscripcionesNoRecurrentes->count());
    
            // Procesar suscripciones recurrentes
            foreach ($suscripcionesRecurrentes as $suscripcion) {
                $orderId = $this->obtenerOrderId($suscripcion->token_pago);
    
                if ($orderId) {
                    $resultado = $this->procesarPago($suscripcion, $orderId);
    
                    $orden = new Orden();
                    $orden->cliente_id = $suscripcion->cliente_id;
                    $orden->suscripcion_id = $suscripcion->id;
                    $orden->orden_id_wp = $orderId;
                    $orden->estado = 'Pendiente';
                    $orden->fecha = now()->format('Y-m-d H:i:s');
                    $orden->save();
    
                    if ($resultado['status'] === 'success') {
                        $orden->estado = 'Pagado';
                        $this->actualizarFechaRenovacion($suscripcion); // Actualiza la fecha de renovación
                        $this->enviarWebhook($orderId, 'paid');
                        Log::info("Pago procesado exitosamente para la suscripción ID: {$suscripcion->id}");
                    } else {
                        $orden->estado = 'Rechazado';
                        $this->enviarWebhook($orderId, 'failed');
                        $suscripcion->estado = 'inactivo';
                        $suscripcion->save();
                        Log::warning("Fallo al procesar el pago para la suscripción ID: {$suscripcion->id}");
                    }
                    $orden->save();
                } else {
                    Log::error("No se pudo obtener el order_id para la suscripción ID: {$suscripcion->id}");
                }
            }
    
            // Procesar suscripciones no recurrentes
            foreach ($suscripcionesNoRecurrentes as $suscripcion) {
                // Cambiar el estado de la suscripción a inactivo
                $suscripcion->estado = 'inactivo';
                $suscripcion->save();
    
                Log::info("La suscripción no recurrente ID: {$suscripcion->id} se ha marcado como inactiva.");
            }
    
            Log::info('Procesamiento de suscripciones finalizado.');
        } catch (\Exception $e) {
            Log::error("Error en el procesamiento de suscripciones: " . $e->getMessage());
        }
    }
    
    
    // Actualiza la fecha de renovación según la frecuencia
    private function actualizarFechaRenovacion($suscripcion)
    {
        switch (strtolower($suscripcion->membresia->tipo_recurrencia)) {
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

    // Función para obtener el order_id de PixelPay
    private function obtenerOrderId($subscriptionId)
    {
        $endpoint = 'http://host.docker.internal/servicio_suscripcion/wp-admin/admin-ajax.php?action=pixelpay_create_order';

        try {
            // Intentar realizar la solicitud HTTP para obtener el order_id
            $response = Http::post($endpoint, [
                'subscription_id' => $subscriptionId,
            ]);

            // Verificar si la respuesta fue exitosa
            if ($response->successful() && isset($response['data']['order_id'])) {
                return $response['data']['order_id']; // Retorna el order_id
            } else {
                Log::error("Error al obtener order_id para la suscripción ID: $subscriptionId. Respuesta: " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            // Capturar cualquier excepción que ocurra durante la ejecución
            Log::error("Error al obtener order_id para la suscripción ID: $subscriptionId. Detalle: " . $e->getMessage());
            return null;
        }
    }

    // Función para procesar el pago
    private function procesarPago($suscripcion, $orderId)
    {
        $endpoint = 'https://pixel-pay.com/api/v2/transaction/sale';
        $headers = [
            'x-auth-key' => '1234567890',
            'x-auth-hash' => '36cdf8271723276cb6f94904f8bde4b6',
        ];
        
        try {
            // Preparar los datos para la solicitud
            $response = Http::withHeaders($headers)->post($endpoint, [
                'customer_name' => $suscripcion->cliente->nombre,
                'card_token' => $suscripcion->token_pago,
                'customer_email' => $suscripcion->cliente->correo,
                'order_id' => $orderId,
                'order_currency' => 'HNL',
                'order_amount' => $suscripcion->monto, // Puedes cambiarlo dependiendo de cómo guardes el monto
                'env' => 'sandbox',
                'lang' => 'es',
            ]);
    
            // Verificar si la respuesta fue exitosa
            if ($response->successful()) {
                // Decodificar la respuesta JSON
                $responseData = json_decode($response->body(), true);
    
                // Verificar si el pago fue exitoso
                if (isset($responseData['success']) && $responseData['success'] === true) {
                    
                    return [
                        'status' => 'success',
                        'message' => 'Pago realizado exitosamente',
                        'transaction_id' => $responseData['data']['transaction_id'], // Puedes devolver más detalles si es necesario
                    ];
                } else {
                    Log::error("Error al procesar el pago para la suscripción ID: {$suscripcion->id}. Respuesta: " . $response->body());
                    return [
                        'status' => 'failed',
                        'message' => 'Transacción no aprobada. Respuesta: ' . $response->body()
                    ];
                }
            } else {
                Log::error("Error al procesar el pago para la suscripción ID: {$suscripcion->id}. Respuesta: " . $response->body());
                return [
                    'status' => 'failed',
                    'message' => 'Error en la solicitud. Respuesta: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error("Error al procesar el pago para la suscripción ID: {$suscripcion->id}. Detalle: " . $e->getMessage());
            return [
                'status' => 'failed',
                'message' => 'Excepción en el procesamiento del pago: ' . $e->getMessage()
            ];
        }
    }
    

    // Función para enviar datos al webhook
    private function enviarWebhook($orderId, $status)
    {
        $endpoint = 'http://host.docker.internal/servicio_suscripcion/wp-admin/admin-ajax.php?action=pixelpay_update_order';
    
        $headers = [
            'x-auth-key' => '1234567890',
            'x-auth-hash' => '36cdf8271723276cb6f94904f8bde4b6',
        ];
    
        try {
    $response = Http::withHeaders($headers)->post($endpoint, [
        'order_id' => $orderId,
        'status' => $status, // El estado será 'failed' o 'paid'
    ]);

            // Verificar si la respuesta fue exitosa
            if ($response->successful()) {
                Log::info("Webhook enviado con éxito para la orden ID: $orderId con estado $status");
            } else {
                // Si la respuesta no fue exitosa, registrar el error con el cuerpo de la respuesta
                Log::error("Error al enviar el webhook para la orden ID: $orderId. Respuesta: " . $response->body());
            }
        } catch (\Exception $e) {
            // Capturar cualquier excepción que ocurra durante la ejecución
            Log::error("Error al enviar el webhook para la orden ID: $orderId. Detalle: " . $e->getMessage());
        }
    }
}
