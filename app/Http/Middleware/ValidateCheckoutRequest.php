<?php

namespace App\Http\Middleware;

use Closure;

class ValidateCheckoutRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      if($request->subscription_id || $request->prepaid_credits_pack_id)
      {
        return $next($request);
      }
      
      $cart = json_decode(base64_decode($request->cart)) ?? abort(404);
    
      $ids = array_column($cart, 'id') ?? abort(404);
      
      return $next($request);
    }
}
