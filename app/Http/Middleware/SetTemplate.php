<?php

namespace App\Http\Middleware;

use Closure;

class SetTemplate
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
        $template  = config('app.template', 'axies');
        $template  = (auth_is_admin() || env_is('local')) ? session('template', $template) : $template;
            
        $template = session('template', $template);

        $templates = \File::glob(resource_path('views/front/*', GLOB_ONLYDIR));
        $base_path = resource_path('views/front/');
        $templates = array_filter($templates, 'is_dir');
        $templates = str_ireplace($base_path, '', $templates);

        if(in_array($template, $templates))
        {
          config(['app.template' => $template]);
          config(['app.top_cover' => config("app.{$template}_top_cover")]);
        }

        return $next($request);
    }
}