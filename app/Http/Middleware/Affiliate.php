<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\{ User, Affiliate_Earning };

class Affiliate
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
        if(!config('affiliate.enabled'))
        {
            return $next($request);
        }

        if($referrer = $request->query('r'))
        {   
            $referrer = htmlspecialchars($referrer);
            $expire   = time() + (config('affiliate.expire', 30) * 86400);

            if($user = User::where('affiliate_name', $referrer)->first())
            {
                setcookie('referrer_id', $user->id, $expire, '/');
            }

            $url_params  = config('app.url_params');
            $current_url = url()->current();

            unset($url_params['r']);

            $url_params = http_build_query($url_params);
            $url_params = $url_params ? "?{$url_params}" : null;

            return redirect($current_url . $url_params);
        }

        if(\Auth::check())
        {
            config(['affiliate_earnings' => 0]);

            if($request->user()->affiliate_name)
            {
                $earnings = Affiliate_Earning::selectRaw('IFNULL(SUM(commission_value), 0) as earnings, transactions.id')
                            ->join('transactions', function($join)
                            {
                              $join->on('transactions.id', '=', 'affiliate_earnings.transaction_id')
                                   ->where(['transactions.status' => 'paid', 'transactions.confirmed' => 1, 'transactions.refunded' => 0, ]);
                            })
                            ->where(['affiliate_earnings.referrer_id' => \Auth::id(), 'paid' => 0])
                            ->get()->pluck('earnings')->first();

                config(['affiliate_earnings' => $earnings]);
            }
        }

        config(['referrer_id' => $_COOKIE['referrer_id'] ?? null]);

        return $next($request);
    }
}
