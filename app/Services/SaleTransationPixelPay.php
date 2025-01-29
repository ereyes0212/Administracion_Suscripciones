<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SaleTransationPixelPay
{
    protected $endpoint;
    protected $apiKey;
    protected $callbackUrl;
    protected $secretKey;
    protected $cancelUrl;
    protected $completeUrl;

    public function __construct()
    {
        // Configuración de las URLs y claves
        $this->endpoint = env('ENDPOINT'); // Endpoint de la API
        $this->apiKey = env('API_KEY'); // API Key
        $this->secretKey = env('SECRET_KEY'); // Secret Key
        
        // URLs fijas de callback, cancel y complete
        $this->callbackUrl = 'https://httpbin.org/status/200'; // URL para recibir notificación de éxito
        $this->cancelUrl = 'https://httpbin.org/status/200'; // URL en caso de cancelación
        $this->completeUrl = 'https://httpbin.org/status/200'; // URL en caso de éxito
    }

    public function procesarPago(array $data)
    {
        try {
            Log::info('Datos recibidos para el pago:', $data);

            // Extraer primer nombre y apellido del campo customer_name
            $nameParts = explode(' ', $data['customer_name']);
            $firstName = $nameParts[0]; // Primer nombre
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : ''; // Apellido (si existe)

            // Preparar los parámetros
            $postFields = [
                "_key" => $this->apiKey,
                "_callback" => $this->callbackUrl,
                "_cancel" => $this->cancelUrl,
                "_complete" => $this->completeUrl,
                "_order_id" => $data['order_id'],
                "_order_date" => date("d-m-y H:i"),
                "_amount" => $data['order_amount'],
                "_first_name" => $firstName,
                "_last_name" => $lastName,
                "_email" => $data['customer_email'],
                "_address" => $data['billing_address'],
                "_city" => $data['billing_city'],
                "_state" => $data['billing_state'],
                "_country" => $data['billing_country'],
                "json" => "true", // Incluir en modo JSON en respuestas (opcional)
            ];

            // Realizar la solicitud HTTP POST usando Http::withHeaders
            $response = Http::withHeaders([
                'x-auth-key' => $this->apiKey, // API Key
                'x-auth-hash' => $this->secretKey, // Secret Key
                'Content-Type' => 'application/x-www-form-urlencoded', // Establecer el tipo de contenido adecuado
            ])->asForm()->post($this->endpoint, $postFields); // Usar asForm() para enviar los datos como x-www-form-urlencoded

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
