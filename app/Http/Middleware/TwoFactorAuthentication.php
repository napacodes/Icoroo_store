<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TwoFactorAuthentication
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
        \Auth::check() ?? abort(404);

        $user = $request->user();

        if(config('app.two_factor_authentication') && $user->two_factor_auth && (!$user->two_factor_auth_secret || ($user->two_factor_auth_expiry && $user->two_factor_auth_expiry <= time()) || $user->two_factor_auth_ip != $request->ip() || (!$user->two_factor_auth_expiry && config('app.two_factor_authentication_expiry') > 0)))
        {
            $security_token = (string)\Str::uuid();

            \Session::put('2fa_sec', $security_token);

            return redirect()->route('two_factor_authentication', ['2fa_sec' => $security_token, 'redirect' => url()->full()]);
        }

        return $next($request);
    }
}
