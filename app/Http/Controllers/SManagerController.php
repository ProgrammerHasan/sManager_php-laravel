<?php
namespace App\Http\Controllers;
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
        $info = [
            'amount'          => "50",
            'transaction_id'  => 'trnx_' .uniqid('', true), // must be unique
            'success_url'     => route('smanager.success'),  // success url
            'fail_url'        => route('smanager.fail'),  // failed url
            'customer_name'   => "Hasan",
            'customer_mobile' => "01625568604",
            'purpose'         => 'Online Payment',
            'payment_details' => 'Payment for buying 3 items'
        ];

      return sManagerService::initiatePayment($info);
    }

   public function paymentDetails($tran_id): RedirectResponse
   {
        $url = curl_init('https://api.sheba.xyz/v1/ecom-payment/details?transaction_id='.$tran_id);

        try{
            $header = array(
                'client-id:'.env('SMANAGER_CLIENT_ID'),
                'client-secret:'.env('SMANAGER_CLIENT_SECRET'),
                'Accept: application/json'
            );
            curl_setopt($url, CURLOPT_HTTPHEADER, $header);
            curl_setopt($url, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url, CURLOPT_POSTFIELDS, $url);
            curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($url);
            curl_close($url);
            $responseJSON = json_decode($result, true);
            $code    = $responseJSON['code'];
            $message = $responseJSON['message'];

            if ($code !== 200) {
                return Redirect::back()
                    ->withErrors([$message]);
            }

            return $responseJSON;

        } catch (\Exception $ex) {
            return Redirect::back()
                ->withErrors([$ex->getMessage()]);
        }
    }



    public function success(Request $request)
    {
       
    }

    public function fail(Request $request)
    {
       
        flash('Payment Failed')->success();
        return redirect(url('/purchase_history'));
    }

     public function cancel(Request $request)
    {
       
        flash('Payment cancelled')->success();
    	return redirect(url('/purchase_history'));
    }


}
