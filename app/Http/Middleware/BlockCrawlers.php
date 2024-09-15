<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Crawler;

class BlockCrawlers
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
        if(Crawler::isCrawler())
        {
          $bot = Crawler::getMatches();

          $authorized_bots = config('app.authorized_bots');
          $authorized_bots = array_filter(array_map('trim', explode(',', $authorized_bots)));

          if(count($authorized_bots) === 0)
          {
            return $next($request);
          }

          $request_accepted = false;

          foreach($authorized_bots as $authorized_bot)
          {
            if(stripos($bot, $authorized_bot) !== false)
            {
                $request_accepted = true;
                break;                
            }
          }

          if(!$request_accepted)
          {
            abort(404);
          }
        }

        return $next($request);
    }
}
