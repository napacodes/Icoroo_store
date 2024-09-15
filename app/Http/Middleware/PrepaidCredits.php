<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\{ User_Prepaid_Credit };

class PrepaidCredits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        config(['prepaid_credits' => 0]);

        if(config('app.prepaid_credits.enabled'))
        {
            if(\Auth::check())
            {
                $user_credits = user_credits(true);
                $user_credits = $user_credits['prepaid_credits'];
                $user_credits = $user_credits->reduce(function($carry, $prepaid_credits)
                                {
                                    $carry += $prepaid_credits->credits;
                                    return $carry;
                                }, 0);

                config(['prepaid_credits' => $user_credits]);
            }
        }

        return $next($request);
    }
}
