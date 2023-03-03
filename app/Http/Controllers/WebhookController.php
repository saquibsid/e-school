<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Razorpay\Api\Api;
use App\Models\Parents;
use App\Models\FeesPaid;
use Illuminate\Http\Request;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    //razorpay webhooks
    public function razorpay(Request $request)
    {
        try {
            // get the json data of payment
            $webhookBody = $request->getContent();
            $webhookBody = file_get_contents('php://input');
            $data = json_decode($webhookBody);

            // gets the signature from header
            $webhookSignature = $request->header('X-Razorpay-Signature');
            $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
            $api = new Api(env('RAZORPAY_API_KEY'), env('RAZORPAY_SECRET_KEY'));

            // get the metadata
            $parent_id = $data->payload->payment->entity->notes->parent_id;
            $student_id = $data->payload->payment->entity->notes->student_id;
            $class_id = $data->payload->payment->entity->notes->class_id;
            $session_year_id = $data->payload->payment->entity->notes->session_year_id;
            $payment_transaction_id = $data->payload->payment->entity->notes->payment_transaction_id;

            // get the current today's date
            $current_date = Carbon::now()->format('Y-m-d');

            //get the payment_id
            $payment_id  = $data->payload->payment->entity->id;

            //if the transaction is success
            if ($data->event == 'payment.captured') {

                //checks the signature
                $expectedSignature = hash_hmac("SHA256", $webhookBody, $webhookSecret);
                Log::error("expectedSignature --->" . $expectedSignature);
                Log::error("Header Signature --->" . $webhookSignature);

                if ($expectedSignature == $webhookSignature) {
                    Log::error("Signature Matched --->");
                }
                $api->utility->verifyWebhookSignature($webhookBody, $webhookSignature, $webhookSecret);

                // udpate data in payment transaction table local
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    if ($transaction_db->status != 1) {

                        //get the total amount from table
                        $total_amount = $transaction_db->total_amount;

                        //udpate the values in payment transaction
                        $transaction_db->payment_id = $payment_id;
                        $transaction_db->payment_status = 1;
                        $transaction_db->save();

                        // add data in fees paid table local
                        $fees_paid_db = new FeesPaid();
                        $fees_paid_db->parent_id = $parent_id;
                        $fees_paid_db->student_id = $student_id;
                        $fees_paid_db->class_id = $class_id;
                        $fees_paid_db->mode = 2;
                        $fees_paid_db->payment_transaction_id = $payment_transaction_id;
                        $fees_paid_db->total_amount = $total_amount;
                        $fees_paid_db->date = $current_date;
                        $fees_paid_db->session_year_id = $session_year_id;
                        $fees_paid_db->save();

                        http_response_code(200);

                        $user = Parents::where('id', $parent_id)->pluck('user_id');
                        $body = 'Amount :- ' . $total_amount;
                        $type = 'Online';
                        send_notification($user, 'Payment Success', $body, $type);
                    }else{
                        Log::error("Transaction Already Successed --->");
                        return false;
                    }
                } else {
                    Log::error("Payment Transaction id not found --->");
                    return false;
                }
            }

            //if the transaction is failed
            if ($data->event == 'payment.failed') {
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    $total_amount = $transaction_db->total_amount;
                    $transaction_db->payment_id = $payment_id;
                    $transaction_db->payment_status = 0;
                    $transaction_db->save();
                    http_response_code(400);

                    $user = Parents::where('id', $parent_id)->pluck('user_id');
                    $body = 'Amount :- ' . $total_amount;
                    $type = 'Online';
                    send_notification($user, 'Payment Failed', $body, $type);
                }else{
                    Log::error("Payment Transaction id not found --->");
                    return false;
                }
            }
        } catch (\Throwable $th) {
            Log::error($th);
            Log::error('Razorpay --> Webhook Error Accured');
        }
    }
    public function stripe(Request $request)
    {
        // This is your test secret API key.
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        // $endpoint_secret = env('STRIPE_SECRET_KEY');

        $payload = file_get_contents('php://input');
        $event = null;

        //checks the payload json
        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Webhook error while parsing basic request. first try');
            http_response_code(400);
            exit();
        }

        // get the metadata
        $student_id = $event->data->object->metadata->student_id;
        $class_id = $event->data->object->metadata->class_id;
        $parent_id = $event->data->object->metadata->parent_id;
        $session_year_id = $event->data->object->metadata->session_year_id;
        $payment_transaction_id = $event->data->object->metadata->payment_transaction_id;

        //get the current today's date
        $current_date = Carbon::now()->format('Y-m-d');

        // handle the events
        switch ($event->type) {
            case 'payment_intent.succeeded':

                // update the values in transaction table local
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    if ($transaction_db->status != 1) {

                        //get the total from transaction table local
                        $total_amount = $transaction_db->total_amount;

                        //udpate the values in transaction table local
                        $transaction_db->payment_status = 1;
                        $transaction_db->save();

                        // add the data in fees paid table local
                        $fees_paid_db = new FeesPaid();
                        $fees_paid_db->parent_id = $parent_id;
                        $fees_paid_db->student_id = $student_id;
                        $fees_paid_db->class_id = $class_id;
                        $fees_paid_db->mode = 2;
                        $fees_paid_db->payment_transaction_id = $payment_transaction_id;
                        $fees_paid_db->total_amount = $total_amount;
                        $fees_paid_db->date = $current_date;
                        $fees_paid_db->session_year_id = $session_year_id;
                        $fees_paid_db->save();

                        $user = Parents::where('id', $parent_id)->pluck('user_id');
                        $body = 'Amount :- ' . $total_amount;
                        $type = 'Online';
                        send_notification($user, 'Payment Success', $body, $type);
                        http_response_code(200);
                        break;
                    } else {
                        Log::error("Transaction Already Successed --->");
                        break;
                    }
                } else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }

            case 'payment_intent.payment_failed':
                // update the data in transaction table local
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    $total_amount = $transaction_db->total_amount;
                    $transaction_db->payment_status = 0;
                    $transaction_db->save();
                    http_response_code(400);
                    $user = Parents::where('id', $parent_id)->pluck('user_id');
                    $body = 'Amount :- ' . $total_amount;
                    $type = 'Online';
                    send_notification($user, 'Payment Failed', $body, $type);
                    break;
                } else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }

            default:
                // Unexpected event type
                Log::error('Received unknown event type');
        }
    }
}
