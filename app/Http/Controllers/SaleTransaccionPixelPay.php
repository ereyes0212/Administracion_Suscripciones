<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleTransaccionPixelPay extends Controller
{
    public function callback(Request $request)
    {
        // Obtener los datos enviados por PixelPay en el callback
        $data = $request->json()->all();

        // Loguear los datos recibidos
        Log::info('Callback recibido:', $data);

        // Procesar los datos según el estado de la transacción
        if ($data['status'] === 'paid') {
            // Si el pago fue exitoso
            // Aquí puedes actualizar la base de datos, notificar al usuario, etc.
            Log::info('Pago exitoso para la orden: ' . $data['order']);
        } else {
            // Si el pago no fue exitoso
            Log::warning('Error en el pago de la orden: ' . $data['order']);
        }

        // Devolver respuesta 200 para confirmar que el callback fue recibido correctamente
        return response()->json(['status' => 'success']);
    }
}
