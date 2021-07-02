<?php
namespace App\Http\Controllers;
use App\Order;
use App\Services\sManagerService;
use Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Session;
use App\Http\Controllers;

class SManagerController extends Controller
{

    public function index(Request $request)
    {
        $order = Order::findOrFail($request->session()->get('order_id'));
        $post_data = array();
        $post_data['total_amount'] = $order->grand_total; # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        $post_data['transaction_id'] = uniqid('sM_', true); // tran_id must be unique
        $orderId = "orderId_".$order->id;

        $info = [
            'amount'          => $post_data['total_amount'],
            'transaction_id'  => $post_data['transaction_id'],
            'success_url'     => route('smanager.success'),  // success url
            'fail_url'        => route('smanager.fail'),  // failed url
            'customer_name'   => auth()->user()->name,
            'customer_mobile' =>  auth()->user()->phone,
            'purpose'         => @$order->orderDetails->first()->product->name,
            'payment_details' => $orderId,
        ];
        session()->put('sM_transaction_id', $post_data['transaction_id']);

        return sManagerService::initiatePayment($info);
    }

    public function success(Request $request)
    {
        $transactionId = session()->get('sM_transaction_id');
        $responseJSON = sManagerService::paymentDetails($transactionId);
        if($responseJSON['data']['payment_status'] !== 'completed')
        {
            flash('Payment is Not Valid')->error();
            return redirect(url('Your redirect url'));
        }

        $order = Order::findOrFail(session()->get('order_id'));
        if($order->payment_status =='unpaid')
        {
            $order->update(['payment_status'=>'Paid']);
        }

        $request->session()->forget('order_id');
        $request->session()->forget('payment_data');
        $request->session()->forget('sM_transaction_id');
        flash(translate('Payment Completed'))->success();
        return view('frontend.order_confirmed', compact('order'));
    }

    public function fail(Request $request)
    {
        $request->session()->forget('order_id');
        $request->session()->forget('payment_data');
        $request->session()->forget('sM_transaction_id');
        flash(translate('Payment Failed'))->error();
        return redirect(url('Your redirect url'));
    }

    public function cancel(Request $request)
    {
        $request->session()->forget('order_id');
        $request->session()->forget('payment_data');
        $request->session()->forget('sM_transaction_id');
        flash(translate('Payment cancelled'))->success();
        return redirect(url('Your redirect url'));
    }


}
