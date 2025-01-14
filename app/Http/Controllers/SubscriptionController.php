<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Models\Suscripcion; // Importamos el modelo Suscripcion
use Illuminate\Support\Str;  // Necesario para usar la función Str::random
use Exception;

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


    public function procesarPago(Request $request)
    {
        try {
            // Validar los datos recibidos
            // $validatedData = $request->validate([
            //     'customer_name' => 'required|string',
            //     'card_number' => 'required|string',
            //     'card_holder' => 'required|string',
            //     'card_expire' => 'required|string',
            //     'card_cvv' => 'required|string',
            //     'customer_email' => 'required|email',
            //     'billing_address' => 'required|string',
            //     'billing_city' => 'required|string',
            //     'billing_country' => 'required|string',
            //     'billing_phone' => 'required|string',
            //     'order_currency' => 'required|string',
            //     'order_amount' => 'required|numeric',
            //     'recurrence' => 'required|string', // Agregado: frecuencia de la suscripción (diario, semanal, mensual)
            // ]);

            $cliente = Cliente::where('correo', $request->input('customer_email'))->first();

            // Si no existe, creamos un nuevo cliente
            if (!$cliente) {
                $cliente = Cliente::create([
                    'nombre' => $request->input('customer_name'),
                    'correo' => $request->input('customer_email'),
                    'direccion' => $request->input('billing_address'),
                    'ciudad' => $request->input('billing_city'),
                    'pais' => $request->input('billing_country'),
                    'telefono' => $request->input('billing_phone'),
                ]);
            }

            // Verificar si el token fue proporcionado
            $token = $request->input('token');
            if (!is_null($token)) {
                $response = $this->procesarPagoConBancoLocal($token);
                return response()->json($response); // Retornamos la respuesta que da procesarPagoConBancoLocal
            }


            // Si no viene token, generamos un token aleatorio
            $tokenGenerado = Str::random(36); // Generar un token aleatorio de 36 caracteres
            Log::info("Nuevo token generado: " . $tokenGenerado);

            // Procesar el pago con los datos de la tarjeta (pasando el token generado)
            $response = $this->procesarPagoConBancoLocal($tokenGenerado);

            // Verificamos si el pago fue exitoso
            if ($response['status'] === 'success') {
                // Guardar la suscripción en la base de datos
                $suscripcion = new Suscripcion();
                $suscripcion->cliente_id = $cliente->id; // Relacionamos la suscripción con el cliente
                $suscripcion->monto = $request->input('order_amount');
                $suscripcion->token_pago = $response['token']; // Token de pago
                $suscripcion->estado = 'Activo'; // Estado de la suscripción
                $suscripcion->tipo_recurrencia = $request->input('recurrence'); // Tipo de recurrencia (diario, mensual, etc.)
                $suscripcion->fecha_inicio = now(); // Fecha de inicio de la suscripción
                $suscripcion->fecha_renovacion = now()->addMonth(); // Fecha de renovación de la suscripción, en este caso mensual
                $suscripcion->save(); // Guardamos la suscripción en la base de datos

                // Devolver respuesta exitosa
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pago procesado con éxito y suscripción guardada.',
                    'token' => $response['token']
                ]);
            } else {
                // En caso de fallo en el pago
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Hubo un error al procesar el pago. Intente nuevamente.'
                ], 400); // Respondemos con un error 400 si el pago falla
            }
        } catch (Exception $e) {
            // Manejo de excepciones
            Log::error("Error al procesar el pago: " . $e->getMessage());
            // Respuesta de error con código HTTP 500
            return response()->json(['status' => 'failed', 'message' => 'Error al procesar el pago. Intente nuevamente.'], 500);
        }
    }
    

    private function procesarPagoConBancoLocal($token = null)
    {
        // Verificar si el token fue proporcionado
        if (!is_null($token)) {
            Log::info("Token recibido: ". $token);
            
            // Si el token existe, retornar el éxito inmediatamente
            return [
                'status' => 'success',
                'message' => 'Transacción realizada con éxito',
                'token' => $token
            ];
        }
        
        // Si no se recibe un token, generar uno nuevo
        $token = Str::random(36);  // Generar un token aleatorio de 36 caracteres
        Log::info("Nuevo token generado: ". $token);
        
        // Simulación del estado de la transacción
        $status = 'success';  
        $message = $status === 'success' ? 'Transacción realizada con éxito' : 'Hubo un error en la transacción';
        
        // Retornar la respuesta con el nuevo token
        return [
            'status' => $status,
            'message' => $message,
            'token' => $token
        ];
    }
    
    
}
