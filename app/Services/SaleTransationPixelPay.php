<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SaleTransationPixelPay
{
    protected $endpoint;
    protected $key;
    protected $callbackUrl;
    protected $cancelUrl;
    protected $completeUrl;

    public function __construct()
    {
        // Configuración de las URLs y clave
        $this->endpoint = env('PIXELPAY_ENDPOINT'); // Endpoint proporcionado por PixelPay
        $this->key = env('PIXELPAY_KEY'); // Llave del comercio
        $this->callbackUrl = env('PIXELPAY_CALLBACK_URL'); // URL para recibir notificación de éxito
        $this->cancelUrl = env('PIXELPAY_CANCEL_URL'); // URL en caso de cancelación
        $this->completeUrl = env('PIXELPAY_COMPLETE_URL'); // URL en caso de éxito
    }

    public function procesarPago(array $data)
    {
        try {
            Log::info('Datos recibidos para el pago:', $data);

            // Extraer primer nombre y apellido del campo customer_name
            $nameParts = explode(' ', $data['customer_name']);
            $firstName = $nameParts[0]; // Primer nombre
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : ''; // Apellido (si existe)
            
            $postFields = [
                "_key" => $this->key,
                "_callback" => $this->callbackUrl, // URL de notificación (opcional)
                "_cancel" => $this->cancelUrl, // URL de cancelación
                "_complete" => $this->completeUrl, // URL de éxito
                "_order_id" => $data['order_id'], // ID único de la orden
                "_order_date" => date("d-m-y H:i"), // Fecha de la orden
                "_amount" => $data['order_amount'], // Monto total de la orden
                "_first_name" => $firstName, // Primer nombre del cliente
                "_last_name" => $lastName, // Apellido del cliente
                "_email" => $data['customer_email'], // Correo electrónico del cliente
                "_address" => $data['billing_address'], // Dirección del cliente (opcional)
                "_city" => $data['billing_city'], // Ciudad
                "_state" => $data['billing_state'], // Estado o provincia
                "_country" => $data['billing_country'], // País
                "json" => "true", // Incluir en modo JSON en respuestas (opcional)
            ];

            // Realizar la solicitud HTTP POST utilizando Http::post
            $response = Http::asForm()->post($this->endpoint, $postFields);

            Log::info('Respuesta de la transacción:', ['response' => $response->body()]);

            // Manejar la respuesta
            $responseData = $response->json();
            if ($responseData && isset($responseData['status']) && $responseData['status'] == 'success') {
                return [
                    'status' => 'success',
                    'message' => 'Redirección exitosa a PixelPay',
                    'redirect_url' => $responseData['redirect_url'], // URL a la que redirigir al cliente
                ];
            }

            return [
                'status' => 'error',
                'message' => $responseData['message'] ?? 'Error desconocido en la transacción',
            ];
        } catch (Exception $e) {
            Log::error('Error en el procesamiento de pago:', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
