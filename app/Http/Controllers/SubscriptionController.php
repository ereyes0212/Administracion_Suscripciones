<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\Orden;
use App\Models\Ordenes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Models\Suscripcion; // Importamos el modelo Suscripcion
use Illuminate\Support\Str;  // Necesario para usar la función Str::random
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{

    public function mostrarSuscripciones()
    {
        // Obtener todas las suscripciones desde la base de datos
        $suscripciones = Suscripcion::with('cliente')->get();

        // Pasar las suscripciones a la vista
        return view('suscripciones.index', compact('suscripciones'));
    }


    public function clientes()
    {
        // Obtener todas las suscripciones desde la base de datos
        $clientes = Cliente::all();

        // Pasar las suscripciones a la vista
        return view('clientes.index', compact('clientes'));
    }
    public function membresias()
    {
        // Obtener todas las suscripciones desde la base de datos
        $membresias = Membresia::all();

        // Pasar las suscripciones a la vista
        return view('membresias.index', compact('membresias'));
    }

    public function ordenes()
    {
        // Obtener las órdenes con las relaciones necesarias
        $ordenes = Orden::with(['cliente', 'suscripcion.membresia'])->get();

        // Loguear las órdenes en el archivo de log
        error_log('Órdenes obtenidas: ' . print_r($ordenes, true));

        // Pasar las órdenes a la vista
        return view('ordenes.index', compact('ordenes'));
    }



    public function CrearMembresia(Request $request)
    {
        try {
            // Validar la entrada (si es necesario)
            // $request->validate([
            //     'nombre' => 'required|string|max:255',
            //     'precio' => 'required|numeric|min:0',
            //     'tipo_recurrencia' => 'required|in:diario,semanal,mensual,anual',
            //     'descripcion' => 'nullable|string',
            // ]);
            $recurrencia = strtolower($request->tipo_recurrencia);
            // Crear la nueva membresía
            $membresia = Membresia::create([
                'id' => $request->id,
                'nombre' => $request->nombre,
                'precio' => $request->precio,
                'tipo_recurrencia' => $recurrencia,
                'descripcion' => $request->descripcion,
            ]);

            // Retornar la respuesta en formato JSON con el objeto creado
            return response()->json([
                'message' => 'Membresía creada correctamente.',
                'membresia' => $membresia
            ], 201); // Código 201 para creado exitosamente
        } catch (Exception $e) {
            // En caso de error, devolver el mensaje de error en formato JSON
            return response()->json([
                'message' => 'Hubo un error al crear la membresía.',
                'error' => $e->getMessage()
            ], 500); // Código 500 para error interno del servidor
        }
    }



    public function procesarPago(Request $request)
    {
        try {
            Log::info('Datos recibidos en la solicitud:', $request->all());
    
            // Validar los datos recibidos
            try {
                $validatedData = $request->validate([
                    'order_id'        => 'required|alpha_num', // Alfanumérico requerido
                    'order_currency'  => 'required|in:USD,HNL,NIO|size:3', // Solo USD, HNL o NIO, exactamente 3 caracteres en mayúscula
                    'order_amount'    => 'required|numeric|min:0', // Número decimal requerido
            
                    'customer_name'   => 'required|string|min:3|max:120', // Mínimo 3, máximo 120 caracteres
                    'customer_email'  => 'required|email', // Formato de correo válido
            
                    'billing_address' => 'required|string', // Texto corto requerido
                    'billing_state'   => 'required|string', // Texto corto requerido
                    'billing_country' => 'required|string|min:3|max:15', // Texto corto, entre 3 y 15 caracteres
                    'billing_phone'   => 'required|digits:8', // Exactamente 8 caracteres numéricos
            
                    'card_number'     => 'required|digits:16', // Exactamente 16 caracteres numéricos
                    'card_holder'     => 'required|string|min:3|max:120', // Texto corto, mínimo 3, máximo 120 caracteres
                    'card_cvv'        => 'required|digits_between:3,4', // Numérico, entre 3 y 4 caracteres
                    'card_expire'     => 'required|digits:4', // Exactamente 4 caracteres numéricos
                ]);
            
            } catch (ValidationException $e) {
                Log::error('Errores de validación:', $e->errors());  // Registrar los errores en el log
            
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Errores de validación',
                    'errors'  => $e->errors()  // Devuelve los campos que no cumplen con la validación
                ], 422);
            }
            
    
            // 1. Procesar el pago con el banco local primero
            $response = $this->procesarPagoConBancoLocal($request);
    
            if ($response['status'] !== 'success') {
                // Si el pago falla, no crear registros y retornar error
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Hubo un error al procesar el pago. Intente nuevamente.'
                ], 400);
            }
    
            // 2. Tokenizar la tarjeta después del pago exitoso
            $tokenData = $this->tokenizarTarjeta($request);
    
            if ($tokenData['status'] === 'success') {
                // 3. Buscar o crear cliente solo después de tokenizar exitosamente
                $cliente = Cliente::where('correo', $request->input('customer_email'))->first();
    
                if (!$cliente) {
                    $cliente = Cliente::create([
                        'nombre'    => $request->input('customer_name'),
                        'correo'    => $request->input('customer_email'),
                        'direccion' => $request->input('billing_address'),
                        'ciudad'    => $request->input('billing_city'),
                        'pais'      => $request->input('billing_country'),
                        'telefono'  => $request->input('billing_phone'),
                    ]);
                }
    
                Log::info('Cliente creado con ID: ' . json_encode($cliente));
    
                // 4. Crear la orden
                $orden = new Orden();
                $orden->cliente_id = $cliente->id;
                $orden->estado = 'Pendiente';
                $orden->fecha = now();
                $orden->save();
    
                // 5. Crear la suscripción
                $suscripcion = new Suscripcion();
                $suscripcion->cliente_id = $cliente->id;
                $suscripcion->membresia_id = $request->input('membresia_id');
                $suscripcion->monto = $request->input('order_amount');
                $suscripcion->token_pago = $tokenData['token'];
                $suscripcion->estado = 'Activo';
                $suscripcion->fecha_inicio = now();
                $suscripcion->fecha_ultimo_pago = now();
    
                // Definir fecha de renovación según la recurrencia
                switch ($request->input('recurrence')) {
                    case 'Diario':
                        $suscripcion->fecha_renovacion = now()->addDay();
                        break;
                    case 'Semanal':
                        $suscripcion->fecha_renovacion = now()->addWeek();
                        break;
                    case 'Mensual':
                        $suscripcion->fecha_renovacion = now()->addMonth();
                        break;
                    case 'Anual':
                        $suscripcion->fecha_renovacion = now()->addYear();
                        break;
                    default:
                        $suscripcion->fecha_renovacion = now()->addMonth();
                        break;
                }
    
                $suscripcion->save();
    
                // Actualizar la orden a "Pagado"
                $orden->estado = 'Pagado';
                $orden->suscripcion_id = $suscripcion->id;
                $orden->orden_id_wp = $request->input('order_id');
                $orden->save();
    
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Pago procesado con éxito, suscripción guardada y orden marcada como pagada.',
                    'token'   => $tokenData['token']
                ]);
            } else {
                // Error en la tokenización, no se crea el cliente ni otros registros
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Error al tokenizar la tarjeta. Intente nuevamente.'
                ], 400);
            }
    
        } catch (Exception $e) {
            Log::error("Error al procesar el pago: " . $e->getMessage());
            return response()->json(['status' => 'failed', 'message' => 'Error interno. Intente nuevamente.'], 500);
        }
    }
    
    




    private function procesarPagoConBancoLocal($request)
    {
        $url = 'https://pixel-pay.com/api/v2/transaction/sale';

        $headers = [
            'x-auth-key' => '1234567890',
            'x-auth-hash' => '36cdf8271723276cb6f94904f8bde4b6',
        ];

        // Datos para la transacción
        $data = [
            'customer_name' => $request->input('customer_name'),
            'card_number' => $request->input('card_number'),
            'card_holder' => $request->input('card_holder'),
            'card_expire' => $request->input('card_expire'),
            'card_cvv' => $request->input('card_cvv'),
            'customer_email' => $request->input('customer_email'),
            'billing_address' => $request->input('billing_address'),
            'billing_city' => $request->input('billing_city'),
            'billing_country' => $request->input('billing_country'),
            'billing_state' => $request->input('billing_state'),
            'billing_phone' => $request->input('billing_phone'),
            'order_id' => $request->input('order_id'),
            'order_currency' => $request->input('order_currency'),
            'order_amount' => $request->input('order_amount'),
            'env' => 'sandbox',
            'lang' => 'es',
        ];
        Log::info('Datos completos de la transacción:', $data);



        $response = Http::withHeaders($headers)->post($url, $data);

        $responseData = $response->json();

        // Verificamos si la respuesta fue exitosa
        if ($responseData['success'] === true) {
            return [
                'status' => 'success',
                'message' => 'Pago realizado exitosamente',
                'token' => $responseData['data']['payment_uuid'], // Usamos el UUID como token
            ];
        } else {
            // Log de error
            Log::error('Error en la transacción', [
                'request' => $data,
                'response' => $responseData,
            ]);

            return [
                'status' => 'failed',
                'message' => 'Error en la transacción: ' . $responseData['message'],
            ];
        }
    }

    private function tokenizarTarjeta($request)
    {
        $url = 'https://pixel-pay.com/api/v2/tokenization/card';

        $headers = [
            'x-auth-key' => '1234567890',
            'x-auth-hash' => '36cdf8271723276cb6f94904f8bde4b6',
        ];

        // Datos para tokenizar la tarjeta
        $data = [
            'cvv2' => $request->input('card_cvv'),
            'number' => $request->input('card_number'),
            'expire_month' => substr($request->input('card_expire'), 0, 2), // Mes
            'expire_year' => '20' . substr($request->input('card_expire'), 2, 4), // Año
            'cardholder' => $request->input('card_holder'),
            'address' => $request->input('billing_address'),
            'country' => $request->input('billing_country'),
            'city' => $request->input('billing_city'),
            'state' => $request->input('billing_state'),
            'zip' => $request->input('billing_zip'),
            'phone' => $request->input('billing_phone'),
            'lang' => 'es',
            'env' => 'sandbox',
        ];

        $response = Http::withHeaders($headers)->post($url, $data);

        $responseData = $response->json();

        // Verificamos si la tokenización fue exitosa
        if ($responseData['success'] === true) {
            return [
                'status' => 'success',
                'message' => 'Tarjeta tokenizada con éxito',
                'token' => $responseData['data']['token'], // Retornamos el token de la tarjeta
            ];
        } else {
            // Log de error
            Log::error('Error al tokenizar la tarjeta', [
                'request' => $data,
                'response' => $responseData,
            ]);

            return [
                'status' => 'failed',
                'message' => 'Error al tokenizar la tarjeta: ' . $responseData['message'],
            ];
        }
    }
}
