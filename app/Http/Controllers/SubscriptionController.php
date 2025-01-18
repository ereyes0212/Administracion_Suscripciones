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

            Log::info('Datos recibidos en la solicitud:', $request->all());
            // Validar los datos recibidos
            $validatedData = $request->validate([
                'customer_name' => 'required|string',
                'card_number' => 'required|string',
                'card_holder' => 'required|string',
                'card_expire' => 'required|string',
                'card_cvv' => 'required|string',
                'customer_email' => 'required|email',
                'billing_address' => 'required|string',
                'billing_city' => 'required|string',
                'billing_country' => 'required|string',
                'billing_phone' => 'required|string',
                'order_currency' => 'required|string',
                'order_amount' => 'required|numeric',
                'recurrence' => 'required|string', // Agregado: frecuencia de la suscripción (diario, semanal, mensual)
            ]);
    
            // Buscar o crear cliente
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
    
            // Si el token no fue enviado, lo generamos en el servicio de pago
            if (is_null($token)) {
                $response = $this->procesarPagoConBancoLocal(); // Generar y procesar pago
            } else {
                $response = $this->procesarPagoConBancoLocal($token); // Usar el token recibido
            }
    
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
                
                // Determinar la fecha de renovación según el tipo de recurrencia
                switch ($request->input('recurrence')) {
                    case 'Diario':
                        $suscripcion->fecha_renovacion = now()->addDay(); // Agregar un día a la fecha actual
                        break;
                    case 'Semanal':
                        $suscripcion->fecha_renovacion = now()->addWeek(); // Agregar una semana a la fecha actual
                        break;
                    case 'Mensual':
                        $suscripcion->fecha_renovacion = now()->addMonth(); // Agregar un mes a la fecha actual
                        break;
                    case 'Anual':
                        $suscripcion->fecha_renovacion = now()->addYear(); // Agregar un año a la fecha actual
                        break;
                    default:
                        // Si no se encuentra un valor válido, asignamos un valor predeterminado (opcional)
                        $suscripcion->fecha_renovacion = now()->addMonth(); // Predeterminado a mensual
                        break;
                }
                
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
        // Si el token no fue recibido, generamos uno nuevo
        if (is_null($token)) {
            $token = Str::random(36);  // Generar un token aleatorio de 36 caracteres
            Log::info("Nuevo token generado: ". $token);
        } else {
            Log::info("Token recibido: ". $token);
        }
    
        // Simulación del estado de la transacción
        $status = 'success';  
        $message = $status === 'success' ? 'Transacción realizada con éxito' : 'Hubo un error en la transacción';
        
        // Retornar la respuesta con el token
        return [
            'status' => $status,
            'message' => $message,
            'token' => $token
        ];
    }
    
    
    
}
