<?php

namespace App\Http\Middleware;

use Closure;
use Validator;
use Illuminate\Http\Request;

class PaymentMethodValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
      $supported_processors = array_keys(config('payments_gateways', []));

      $supported_processors = implode(',', $supported_processors);
      
      $rules = [
        'processor' => "required|in:{$supported_processors},bail"
      ];

      if($request->subscription_id)
      {
        $rules['subscription_id'] = 'required|numeric|gt:0';
      }
      elseif($request->prepaid_credits_pack_id)
      {
        $rules['prepaid_credits_pack_id'] = 'required|numeric|gt:0'; 
      }
      else
      {
        $rules['cart'] = 'required';
      }

      !Validator::make($request->all(), $rules)->fails() || abort(404);

      return $next($request);
    }
}
