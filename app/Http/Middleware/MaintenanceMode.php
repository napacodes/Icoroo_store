<?php

namespace App\Http\Middleware;

use Closure;

class MaintenanceMode
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
        if(config('app.maintenance.enabled'))
        {
            if($exception = array_map('trim', explode(',', config('app.maintenance.exception'))))
            {
                if(in_array($request->ip(), $exception))
                {
                    return $next($request);
                }
            }

            if(auth_is_admin())
            {
                return redirect()->route('admin');
            }
            
            if(config('app.maintenance.auto_disable') && config('app.maintenance.expires_at'))
            {
                if(format_date(config('app.maintenance.expires_at'), 'Y-m-d h:i:s') <= date('Y-m-d h:i:s'))
                {
                    $settings = \App\Models\Setting::first();
                    
                    $maintenance_settings = json_decode($settings->maintenance);
                    
                    $maintenance_settings->enabled = '0';

                    $settings->maintenance = json_encode($maintenance_settings);

                    $settings->save();

                    return $next($request);
                }
            }

            abort('307');
        }

        return $next($request);
    }
}
