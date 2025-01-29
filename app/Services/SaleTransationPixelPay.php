<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

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
        $this->callbackUrl = env('PIXELPAY_CALLBACK_URL'); // URL para recibir notificación de éxito (opcional)
        $this->cancelUrl = env('PIXELPAY_CANCEL_URL'); // URL en caso de cancelación
        $this->completeUrl = env('PIXELPAY_COMPLETE_URL'); // URL en caso de éxito
    }

    public function procesarPago(array $data)
    {
        try {
            Log::info('Datos recibidos para el pago:', $data);

            // Parámetros requeridos
            $postFields = [
                "_key" => $this->key, 
                "_callback" => $this->callbackUrl, // URL de notificación (opcional)
                "_cancel" => $this->cancelUrl, // URL de cancelación
                "_complete" => $this->completeUrl, // URL de éxito
                "_order_id" => $data['order_id'], // ID único de la orden
                "_order_date" => date("d-m-y H:i"), // Fecha de la orden
                "_amount" => $data['order_amount'], // Monto total de la orden
                "_first_name" => $data['first_name'], // Nombre del cliente
                "_last_name" => $data['last_name'], // Apellido del cliente
                "_email" => $data['customer_email'], // Correo electrónico del cliente
                "_address" => $data['billing_address'], // Dirección del cliente (opcional)
                "_address_alt" => $data['billing_address_alt'], // Dirección alternativa (opcional)
                "_city" => $data['billing_city'], // Ciudad
                "_state" => $data['billing_state'], // Estado o provincia
                "_country" => $data['billing_country'], // País
                "_zip" => $data['billing_zip'], // Código postal (opcional)
                "json" => "true", // Incluir en modo JSON en respuestas (opcional)
            ];

            // Crear la consulta en formato query string
            $queryString = http_build_query($postFields);

            // Realizar la solicitud HTTP POST
            $ch = curl_init($this->endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            Log::info('Respuesta de la transacción:', ['response' => $response]);

            // Manejar la respuesta
            $responseData = json_decode($response, true);
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
