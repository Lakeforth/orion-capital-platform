<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function callback($gateway, $type, $id)
    {
        try{
            $description = "Wallet funding via paystack";
            $method = "Paystack";
            $payment = Paystack::getPaymentData();

            if($gateway != 'paystack'){
                reportError(json_encode($payment),'Unauthorised callback',null,'fraud_alert' );
            }

            if (!empty($payment['data']) && $payment['data']['status'] == 'success') {
                $amount = $payment['data']['amount'] / 100;
                $id = $payment['data']['metadata']['id'];

                if($payment['data']['metadata']['type'] == 'order'){
                    $order = Order::findOrFail($id);
                    if($order->grand_total == $amount){
                        $order->payment_status = 'paid';
                        $order->payment_info = json_encode($payment['data']);

                        $order->save();
                    }

                    return redirect()->route('payment.success');

                }else{
                    reportError(json_encode($payment),'Unauthorised callback, missing metadata type',null,'fraud_alert' );
                }



            }
            else{
                return ( new PaymentController )->payment_failed();
            }
        }
        catch(\Exception $e){
            reportError($e->getMessage(),'Payment failure',null,'online_payment' );;
        }
    }

    public function safeHavenWalletCallback(Request $request, $id){
        Log::info("wallet callback called for $id");
    }
}
