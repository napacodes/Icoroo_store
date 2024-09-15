<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ Cache };
	use GuzzleHttp\Client;

	class CurrencyExchanger
	{
		public $base;
		public $to;
		public $status = 0;
		public $rate;
		public $message;
		public $name;
		public $refresh = 120; // after 120 minutes

		public function __construct($to, $base = null)
		{
			$this->base  	 = strtoupper($base ?? config('payments.currency_code'));
			$this->to      = strtoupper($to);
		}


		private function init($func)
		{
			$this->message = null;

			$name = config("exchangers.{$func}.name");

			if($rate = cache("exchange_rate_{$this->base}_{$this->to}"))
      {
      	$this->status = 1;
      	$this->rate 	= (float)number_format($rate, 8, '.', ',');
      }
			elseif(config("exchangers.{$func}.fields.api_key") && !config("exchangers.{$func}.api_key"))
      {
        $this->status = 0;
        $this->message = __("The access_key is missing for :api.", ['api' => $name]);
      }
			elseif($diff = array_diff([$this->base, $this->to], config("exchangers.{$func}.supported_currencies")))
      {
        $this->status = 0;
        $this->message = __("The selected currency (:currency) is not supported by :api API.", 
        														['currency' => implode(',', $diff), 'api' => $name]);
      }

      return $this->status;
		}


		public function api_exchangeratesapi_io()
		{
			if($this->init(__FUNCTION__))
			{
				return $this;
			}

			if($this->message === null)
			{
				$client  = new Client(['verify' => false, 'http_errors' => false]);
	      
	      $api_key = config('exchangers.'.__FUNCTION__.'.api_key');

	      $response = $client->get(str_ireplace(["[API_KEY]", "[BASE]", "[TO]"], [$api_key, $this->base, $this->to], config('exchangers.'.__FUNCTION__.'.endpoint')));

	      if($response->getStatusCode() === 200)
	      {
	        $json = json_decode($response->getBody());
	        
	        Cache::put("exchange_rate_{$this->base}_{$this->to}", $json->rates->$to, now()->addMinutes($this->refresh));

	        $this->status = 1;
	      	$this->rate 	= (float)number_format($json->rates->$to, 8, '.', ',');
	      	$this->name   = __FUNCTION__;
	      }
	      else
	      {
	        $this->status = 0;
	        $this->message = __("Unable to get an exchange rate with :api.", ['api' => $name]);
	      }
			}

			$this->resetCurrrency();

      return $this;
		}


		public function pro_api_coinmarketcap_com()
		{
			if($this->init(__FUNCTION__))
			{
				return $this;
			}

			if($this->message === null)
			{
				$headers = [
      		'X-CMC_PRO_API_KEY' => config('exchangers.'.__FUNCTION__.'.api_key'),
      		'Accept' => 'application/json'
      	];

	      $client  = new Client(['verify' => false, 'http_errors' => false, 'headers' => $headers]);

	      $response = $client->get(str_ireplace(["[BASE]", "[TO]"], [$this->base, $this->to], config('exchangers.'.__FUNCTION__.'.endpoint')));

	      if($response->getStatusCode() === 200)
	      {
	        $json = json_decode($response->getBody());
	        $status = $json->status->error_code ?? null;

	        if($status === 0)
	        {
	        	$rate = $json->data[0]->quote->{$this->to}->price;

	        	Cache::put("exchange_rate_{$this->base}_{$this->to}", $rate, now()->addMinutes($this->refresh));

		        $this->status = 1;
		      	$this->rate 	= (float)number_format($rate, 8, '.', ',');
		      	$this->name   = __FUNCTION__;
	        }
	        else
	        {
	        	$this->status = 0;
	        	$this->message = $json->status->error_message;
	        }
	      }
			}

			$this->resetCurrrency();

      return $this;
		}


		public function api_currencyscoop_com()
		{
			if($this->init(__FUNCTION__))
			{
				return $this;
			}

			if($this->message === null)
			{
				$client  = new Client(['verify' => false, 'http_errors' => false]);
	      
	      $api_key = config('exchangers.'.__FUNCTION__.'.api_key');

	      $response = $client->get(str_ireplace(["[API_KEY]", "[BASE]"], [$api_key, $this->base, $this->to], config('exchangers.'.__FUNCTION__.'.endpoint')));

	      if($response->getStatusCode() === 200)
	      {
	        $json = json_decode($response->getBody());

	        if($json->meta->code === 200)
          {
            if(!isset($json->response->rates->{$this->to}))
            {
            	$this->status  = 0;
            	$this->message = __("Currency :currency not supported by currencyscoop API.", ['currency' => $this->to]);
            }
            else
            {
            	$this->status = 1;
	      			$this->rate 	= (float)number_format($json->response->rates->{$this->to}, 8, '.', ',');
	      			$this->name   = __FUNCTION__;

	      			Cache::put("exchange_rate_{$this->base}_{$this->to}", $this->rate, now()->addMinutes($this->refresh));
            }
          }
	      }
	      else
	      {
	        $this->status = 0;
	        $this->message = __("Unable to get an exchange rate with :api.", ['api' => $name]);
	      }
			}

			$this->resetCurrrency();

      return $this;
		}


		public function api_exchangerate_host()
		{
			if($this->init(__FUNCTION__))
			{
				return $this;
			}

			if($this->message === null)
			{
				$client  = new Client(['verify' => false, 'http_errors' => false]);
	      
	      $response = $client->get(str_ireplace(["[BASE]"], [$this->base], config('exchangers.'.__FUNCTION__.'.endpoint')));

	      if($response->getStatusCode() === 200)
	      {
	        $json = json_decode($response->getBody());

          if(!isset($json->rates->{$this->to}))
          {
          	$this->status  = 0;
          	$this->message = __("Currency :currency not supported by currencyscoop API.", ['currency' => $this->to]);
          }
          else
          {
          	$this->status = 1;
      			$this->rate 	= (float)number_format($json->rates->{$this->to}, 8, '.', ',');
      			$this->name   = __FUNCTION__;

      			Cache::put("exchange_rate_{$this->base}_{$this->to}", $this->rate, now()->addMinutes($this->refresh));
          }
	      }
	      else
	      {
	        $this->status = 0;
	        $this->message = __("Unable to get an exchange rate with :api.", ['api' => $name]);
	      }
			}

			$this->resetCurrrency();

      return $this;
		}


		public function api_coingate_com()
		{
			if($this->init(__FUNCTION__))
			{
				return $this;
			}

			if($this->message === null)
			{
				$client  = new Client(['verify' => false, 'http_errors' => false]);
	      
	      $response = $client->get(str_ireplace(["[BASE]", "[TO]"], [$this->base, $this->to], config('exchangers.'.__FUNCTION__.'.endpoint')));

	      if($response->getStatusCode() === 200)
	      {
	        if($rate = (string)$response->getBody())
	        {
	        	$this->status = 1;
      			$this->rate 	= (float)number_format($rate, 8, '.', ',');
      			$this->name   = __FUNCTION__;

      			Cache::put("exchange_rate_{$this->base}_{$this->to}", $this->rate, now()->addMinutes($this->refresh));
	        }
	      }
	      else
	      {
	        $this->status = 0;
	        $this->message = __("Unable to get an exchange rate with :api.", ['api' => $name]);
	      }
			}

			$this->resetCurrrency();

      return $this;
		}


		private function resetCurrrency()
		{
			if(!$this->status)
      {
      	session(['currency' => strtoupper(config('payments.currency_code'))]);
      }
		}


		public function __call($name, $arguments)
		{
			abort(403, __("Call to undefined method [:name] :file", ['name' => $name, 'file' => __FILE__]));
		}

	}