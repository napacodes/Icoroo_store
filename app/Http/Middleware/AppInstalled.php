<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AppInstalled
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
      if(!$request->routeIs('home.install_app') && config('app.installed') === false)
      {
        return redirect()->route('home.install_app');
      }

      if(\Auth::check())
      {
          \App\Http\Controllers\HomeController::init_notifications();
      }

      return $next($request);
    }
}
