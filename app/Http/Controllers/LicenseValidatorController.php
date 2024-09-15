<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB };
use App\Models\{ Transaction, User, Product };


class LicenseValidatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('back.license_validation');
    }



    public function validate_license(Request $request)
    {
        $request->validate(['licenseKey' => 'required|uuid']);

        if(!auth_is_admin())
        {
            $bearer = $request->bearerToken() ?? abort(404);

            $credentials = explode(':', base64_decode($bearer), 2);

            count($credentials) === 2 || abort(404);

            list($email, $pwd) = $credentials;

            Auth::validate(['email' => $email, 'password' => $pwd, 'role' => 'admin']) || abort(404);
        }

        $response = ['status' => false, 'data' => []];

        // 2faa94c2-4713-4147-9bf4-853d7eec9580

        $transaction = Transaction::where("licenses", "REGEXP", wrap_str($request->licenseKey, '"'))->whereRaw('licenses IS NOT NULL')->first();

        if(!$transaction)
        {
            $response['error'] = __('No transaction found with this license key');

            return json($response);
        }

        $buyer_email = $transaction->guest_email ?? User::where(['id' => $transaction->user_id])->first()->email;

        $transaction->licenses = json_decode($transaction->licenses, true) ?? [];
        
        $product = null;
       
        foreach($transaction->licenses as $product_id => $license_key)
        {
            if(strtolower($license_key) === strtolower($request->licenseKey))
            {
                $product = Product::where('active', 1)->where('id', $product_id)->first();
                break;
            }
        }

        if(!$product)
        {
            $response['error'] = __('No product found with this licnese key');

            return json($response);
        }

        $response['status'] = true;
        
        $response['data'] = [
            "purchased_at"   => $transaction->created_at->format('Y-m-d H:is'),
            "reference_id"   => $transaction->reference_id,
            "processor"      => config("payment_gateways.{$transaction->processor}.name"),
            "guest_token"    => $transaction->guest_token ?? '',
            "buyer_email"    => $buyer_email,
            "item_name"      => $product->name,
            "item_url"       => item_url($product),
        ];

        extract($response);

        return response()->json(compact('status', 'data'));
    }

    
}
