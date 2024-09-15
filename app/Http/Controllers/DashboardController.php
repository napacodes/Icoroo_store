<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Dashboard, Statistic };
use Illuminate\Support\Carbon;
use GuzzleHttp\Client;
use Throwable;

class DashboardController extends Controller
{
    // Admin
    public function index(Request $request)
    {
    		$counts = Dashboard::counts();
    		
    		if($transactions = Dashboard::transactions())
    		{
    			foreach($transactions as &$transaction)
    			{
    				$transaction->products = explode('|---|', $transaction->products);
    			}
    		}

        extract($this->sales($request));

				$newsletter_subscribers = Dashboard::newsletter_subscribers();

				$reviews = Dashboard::reviews();

        $statistics = Statistic::all();

        $iso_codes_inverted = array_flip(config('app.iso_codes'));

        $_traffic = $statistics->reduce(function($carry, $record)
        {
          $carry = array_merge($carry, array_filter(explode(',', $record->traffic)));
          return $carry;
        }, []);

        $traffic = [];

        foreach($_traffic as $code)
        {
          if(isset($traffic[$code]))
          {
            $traffic[$code] += 1; 
            continue;
          }

          $traffic[$code] = 1;
        }

        $countries_traffic = [];

        foreach($traffic as $iso_code => $count)
        {
          if(isset($iso_codes_inverted[$iso_code]))
          {
            $countries_traffic[$iso_codes_inverted[$iso_code]] = $count;
          }
        }

        arsort($countries_traffic);

        foreach($traffic as $iso_code => $hits)
        {
          if(isset($iso_codes_inverted[$iso_code]))
          {
            unset($traffic[$iso_code]);

            $traffic[$iso_codes_inverted[$iso_code]] = $hits;
          }
          else
          {
            unset($traffic[$iso_code]);
          }
        }

        $browsers = $statistics->pluck('browsers')->reduce(function($carry, $record)
        {
          $record = json_decode($record, true) ?? [];

          foreach($record as $name => $count)
          {
            if(isset($carry[$name]))
              $carry[$name] += $count; 
            else
              $carry[$name] = $count;
          }

          return $carry;
        }, []);

        $sum_browsers = array_sum($browsers);

        foreach($browsers as $name => &$count)
        {
          $count = number_format(($count / $sum_browsers) * 100, 2, '.', null);
        }

        $devices = $statistics->pluck('devices')->reduce(function($carry, $record)
        {
          $record = json_decode($record, true) ?? [];

          foreach($record as $name => $count)
          {
            if(isset($carry[$name]))
              $carry[$name] += $count; 
            else
              $carry[$name] = $count;
          }

          return $carry;
        }, []);


        $operating_systems = $statistics->pluck('oss')->reduce(function($carry, $record)
        {
          $record = json_decode($record, true) ?? [];

          foreach($record as $name => $count)
          {
            if(isset($carry[$name]))
              $carry[$name] += $count; 
            else
              $carry[$name] = $count;
          }

          return $carry;
        }, []);

        $sum_oss = array_sum($operating_systems);

        foreach($operating_systems as $name => &$count)
        {
          $count = number_format(($count / $sum_oss) * 100, 2, '.', null);
        }

        $sum_devices = array_sum($devices);

        foreach($devices as $name => &$count)
        {
          $count = number_format(($count / $sum_devices) * 100, 2, '.', null);
        }

        arsort($devices);
        arsort($browsers);
        arsort($operating_systems);

        $data = compact('counts', 'transactions','newsletter_subscribers', 'reviews', 'sales_steps', 'sales_per_day', 'max_value', 
                        'current_month', 'traffic', 'browsers', 'devices', 'operating_systems', 'countries_traffic');

        return view('back.index', $data);
    }



    private function sales(Request $request)
    {
        $daysInMonth = now()->daysInMonth;

        if($request->date && is_string($request->date))
        {
          $daysInMonth = Carbon::parse($request->date)->daysInMonth;
          $date = [(string)Carbon::parse($request->date)->firstOfMonth()->format('Y-m-d'), (string)Carbon::parse($request->date)->lastOfMonth()->format('Y-m-d')];
        }
        else
        {
          $date = [(string)now()->firstOfMonth()->format('Y-m-d'), (string)now()->lastOfMonth()->format('Y-m-d')];
        }

        $sales = array_fill(1, date('t'), 0);

        foreach(Dashboard::sales($date) as $sale)
        {
          $sales[$sale->day] = $sale->count;
        }

        $sales = array_values($sales);

        $sales_per_day = [];

        $current_month = trim(explode('-', $date[0], 3)[1] ?? null, 0);

        foreach(range(1, $daysInMonth) as $day)
        {
          $sales_per_day[$day] = isset($sales[$day]) ? $sales[$day] : 0;
        }

        if(!$sales)
        {
          $sales_steps = [];
          $max_value = 10;

          for($i = $max_value; $i >= 0; $i -= $max_value/10)
          {
            $sales_steps[] = $i;
          }

          $sales_steps = array_unique($sales_steps);
          $sales_per_day = array_values($sales_per_day);

          return compact('sales_steps', 'sales_per_day', 'max_value', 'current_month');
        }

        $max_value = max($sales_per_day);
        $max_value = $max_value > 0 ? $max_value : 10;
        
        $sales_steps = [];

        for($i = $max_value; $i >= 0; $i -= $max_value/10)
        {
          $sales_steps[] = $i;
        }

        $sales_steps[] = 0;
        $sales_steps = array_unique($sales_steps);
        $sales_per_day = array_values($sales_per_day);

        return compact('sales_steps', 'sales_per_day', 'max_value', 'current_month');
    }



    public function update_sales_chart(Request $request)
    {
      	extract($this->sales($request));

      if(!count(array_filter($sales_per_day)))
      {
        $html = '<div class="ui active inverted dimmer">
                  <div class="content">
                    <h4 class="ui header">'.__('No data found for the selected date').'</h4>
                  </div>
                </div>';
      }
      else
      {
        $html = '<div class="wrapper">';

                  foreach($sales_steps as $step):
                   $html .= '<div class="row"><div>'.ceil($step).'</div>';

                    for($k = 0; $k <= (count($sales_per_day) - 1); $k++):
                    $html .= '<div>';

                      if($step == 0):
                      $html .= '<span data-tooltip="'. __(':count sales', ['count' => $sales_per_day[$k] ?? '0']) .'" 
                      style="height:'. ($sales_per_day[$k] > 0 ? ($sales_per_day[$k] / $max_value * 305) : "0") .'px"><i class="circle blue icon mx-0"></i></span>';
                      endif;

                    $html .= '</div>';

                    endfor;
                  $html .= '</div>';

                 endforeach;

                $html .= '<div class="row"><div>-</div>';

                for($day = 1; $day <= count($sales_per_day); $day++):
                $html .= '<div>'. $day .'</div>';
                endfor;

        $html .= '</div></div>';
      }

      return response()->json(compact('html'));
    }



    public function admin_login(Request $request)
    {
    	if(! cache('login_token')) abort(404);

			list($email, $password) = explode('|', base64_decode($request->token));

			$credentials = [
				'email' => decrypt($email),
				'password' => decrypt($password)	
			];

			\Cache::forget('login_token');

      \Auth::attempt($credentials, true);
  
      return redirect()->route('admin');
    }



    public function report_errors(Request $request)
    {
        if(!config('app.report_errors'))
        {
          return;
        }

        try 
        {
          $date   = date('Y-m-d');
          $log    = storage_path("logs/laravel-{$date}.log");
          $p_code = config('app.purchase_code', env('PURCHASE_CODE'));

          $validator = \Validator::make(['p_code' => $p_code], ['p_code' => 'uuid|required']);

          if($validator->fails())
          {
              return;
          }
          elseif(!file_exists($log))
          {
              return;
          }
          elseif(!filesize($log))
          {
              return;
          }
          
          $logs_report_api_url = config('app.codemayer_api')."/report_errors";
          $purchase_code = $p_code;
          $payload = [
            'json' => [
              'purchase_code' => $purchase_code, 
              'contact_email' => config('app.email', config('mail.from.address'))
            ],
            'multipart' => [
              'name' => 'log',
              'content' => file_get_contents($log),
            ] 
          ];

          $client = new Client(['verify' => false, 'http_errors' => false, 'timeout' => 10]);
          
          $client->post($logs_report_api_url, $payload);
        }
        catch(\Throwable $e){}
    }



    public function show_file_manager(Request $request)
    {
      return view('vendor.file-manager.ckeditor');
    }


    public function log_viewer(Request $request)
    {
      return view('back.log_viewer');
    }


    public function file_manager(Request $request)
    {
      return view('back.file_manager');
    }

}