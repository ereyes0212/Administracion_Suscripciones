<?php

namespace App\Services;

use PixelPay\Sdk\Base\Response;
use PixelPay\Sdk\Models\Settings;
use PixelPay\Sdk\Models\Card;
use PixelPay\Sdk\Models\Billing;
use PixelPay\Sdk\Models\Item;
use PixelPay\Sdk\Models\Order;
use PixelPay\Sdk\Requests\SaleTransaction;
use PixelPay\Sdk\Services\Transaction;
use PixelPay\Sdk\Entities\TransactionResult;
use Exception;
use Illuminate\Support\Facades\Log;

class SaleTransationPixelPay
{
    protected $settings;
    protected $transaction;

    public function __construct()
    {
        $this->settings = new Settings();
        $this->settings->setupEndpoint(env('ENDPOINT')); // Agrega esta variable en tu .env
        $this->settings->setupCredentials(env('KEY_ID'), env('SECRET_KEY'));
        $this->transaction = new Transaction($this->settings);
    }

    public function procesarPago(array $data)
    {
        try {
            Log::info('Datos recibidos para el pago:', $data);
            $card = new Card();
            $card->number = $data['card_number'];
            $card->cvv2 = $data['cvv'];
            $card->expire_month = $data['expire_month'];
            $card->expire_year = $data['expire_year'];
            $card->cardholder = $data['cardholder'];

            $billing = new Billing();
            $billing->address = $data['billing_address'];
            $billing->country = $data['billing_country'];
            $billing->state = $data['billing_state'];
            $billing->city = $data['billing_city'];
            $billing->phone = $data['billing_phone'];

            $order = new Order();
            $order->id = $data['order_id'];
            $order->currency = $data['currency'];
            $order->customer_name = $data['customer_name'];
            $order->customer_email = $data['customer_email'];
            $order->amount = $data['order_amount'];

            $sale = new SaleTransaction();
            $sale->setOrder($order);
            $sale->setCard($card);
            $sale->setBilling($billing);

            $response = $this->transaction->doSale($sale);

            if (TransactionResult::validateResponse($response)) {
                $result = TransactionResult::fromResponse($response);
                $isValidPayment = $this->transaction->verifyPaymentHash(
                    $result->payment_hash,
                    $order->id,
                    env('SECRET_KEY')
                );

                if ($isValidPayment) {
                    return [
                        'status' => 'success',
                        'message' => 'Pago exitoso',
                        'transaction_id' => $result->payment_uuid
                    ];
                }
            }

            return [
                'status' => 'error',
                'message' => $response->message ?? 'Error desconocido en la transacción'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
