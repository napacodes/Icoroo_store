<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetExchangeRate
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
        try
        {
          $exchange_rate = 1;

          if(config('payments.currency_by_country'))
          {
            session(['currency' => session('currency') ?? country_currency()]);
          }

          if($currency = strtoupper(session('currency')))
          {
            $exchanger = exchange_rate($currency);

            if($exchanger->status)
            {
              $exchange_rate = $exchanger->rate;
            }
            elseif($exchanger->message)
            {
              \Session::flash('user_message', $exchanger->message);

              return $next($request);
            }
          }

          config(['payments.exchange_rate' => $exchange_rate]);

          $fees = config('fees', []);

          foreach($fees as &$fee)
          {
              $fee = $fee * config('payments.exchange_rate');
          }

          config(['fees' => $fees]);   
        } 
        catch(\Exception $e)
        {

        }

        return $next($request);
    }
}
