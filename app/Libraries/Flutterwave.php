<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };

	class Flutterwave 
	{
		public $name = 'flutterwave';
		public $status_url;
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ['GBP', 'CAD', 'CVE', 'CLP', 'COP', 'CDF', 'EGP', 'EUR', 'GMD', 'GHS', 'GNF', 'KES', 'LRD', 'MWK', 'MAD', 'MZN', 'NGN', 'SOL', 'RWF', 'SLL', 'STD', 'ZAR', 'TZS', 'UGX', 'USD', 'XAF', 'XOF', 'ZMK', 'ZMW', 'BRL', 'MXN', 'ARS'];
		public $currency_code;
		public $methods = ['account', 'card', 'banktransfer', 'mpesa', 'mobilemoneyrwanda', 'mobilemoneyzambia', 'qr', 'mobilemoneyuganda', 'ussd', 'credit', 'barter', 'mobilemoneyghana', 'payattitude', 'mobilemoneyfranco', 'paga', '1voucher', 'mobilemoneytanzania'];
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $error_msg;
		public static $response = "default";



		public function __construct()
		{
			$this->name       = mb_strtolower($this->name);
			$this->status_url = route('home.checkout.webhook');
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

      exists_or_abort(config("payment_gateways.{$this->name}"), __(':payment_proc is not enabled', ['payment_proc' =>  mb_ucfirst($this->name)]));

      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

      prepare_currency($this);

      if($methods = array_filter(explode(',', config("payments_gateways.{$this->name}.methods"))))
      {
      	$this->methods = array_intersect($this->methods, $methods);
      }
      
      $this->details = [
        "items" => [],
	      "total_amount" => 0,
	      "currency" => $this->currency_code,
	      "exchange_rate" => $this->exchange_rate,
	      "custom_amount" => null,
	      "reference" => generate_transaction_ref(),
	      "transaction_id" => null,
				"order_id" => null,
      ];
		}



		public function create_payment_link(array $params, $user)
		{
			extract($params);

			$total_amount = 0;

			foreach($cart as $item)
			{
      	$total_amount += $item->price;

				$this->details['items'][] = [
					'name' => $item->name, 
					"id" => $item->id ?? null,
					'value' => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
					'license' => $item->license_id ?? null
				];
			}


			if(!$prepaid_credits_pack_id)
			{
				if(config("pay_what_you_want.enabled") && config('pay_what_you_want.for.'.($subscription_id ? 'subscriptions': 'products')) && $custom_amount)
				{
					$total_amount = $custom_amount;

					$this->details['custom_amount'] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
				}

				if(($coupon->status ?? null) && !$custom_amount)
	      {
	      	$total_amount -= $coupon->coupon->discount ?? 0;

	      	$this->details['items']['discount'] = ['name' => __('Discount'), 'value' => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
		    }
			}


      $total_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if(!$prepaid_credits_pack_id)
      {
	      if(config('payments.vat', 0))
	      {
	      	$tax = format_amount(($total_amount * config('payments.vat', 0)) / 100, false, $this->decimals);

		      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $tax];

		      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
	      }


	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$fee = format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals);

		      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $fee];

		      $total_amount += format_amount($fee, false, $this->decimals);
	      }
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      $total_amount = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);
      
      $this->details['processor'] = $this->name;

			$payload = [
			  'tx_ref' => $this->details['reference'],
			  'amount' => $total_amount,
			  'currency' => $this->currency_code,
			  'redirect_url' => $this->return_url,
			  'callback_url' => "https://c713-197-144-181-96.eu.ngrok.io/checkout/webhook?processor={$this->name}&order_id={$this->details['reference']}&t=".time(),
			  'payment_options' => implode(',', $this->methods),
			  'customer' => [
			    'email' => $user->email,
			    'phonenumber' => $user->phone,
			    'name' => "{$user->lastname} {$user->firstname}",
			  ],
			  'customizations' => [
			    'title' => __('Purchase from :app_name', ['app_name' => config('app.name')]),
			    //'description' => 'Middleout isn\'t free. Pay the price',
			    //'logo' => 'https://assets.piedpiper.com/logo.png',
			  ],
			];

			$secret_key = config("payments_gateways.{$this->name}.secret_key");

			$ch = curl_init("https://api.flutterwave.com/v3/payments");

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$secret_key}", "Content-Type: application/json"]);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			$curl_errno = curl_errno($ch);
			$error_msg 	= curl_error($ch);

			curl_close($ch);

			if($curl_errno)
			{
				$this->error_msg = ['user_message' => $error_msg];

				return;
			}

			$result = json_decode($result);

			if($result->status == 'error')
			{
				$this->error_msg = ['user_message' => $result->message];

				return;	
			}								

			return (object)['payment_link' => $result->data->link];
		}




		public function init_payment(array $config)
		{
			extract($config);

			if(!$return_url && !$user)
      {
        $request->validate([
          'buyer.firstname' => 'required|bail|string',
          'buyer.lastname'  => 'required|bail|string',
          'buyer.phone'     => 'required|bail|string',
          'buyer.email'     => 'required|bail|string'
        ]);
      }

      $user = $user ?? (object)$request->input('buyer');

      $flutterwave = new Flutterwave();

      if($flutterwave->error_msg)
      {
        return $flutterwave->error_msg;
      }

      $order = $flutterwave->create_payment_link($params, $user);

      if($flutterwave->error_msg)
      {
        return $flutterwave->error_msg;
      }

      $params['transaction_details'] = $flutterwave->details;
      
      $params['payment_token']  = str_extract($order->payment_link, '/pay\/(?P<token>.+)\/?/i', 'token');

      Cache::put($flutterwave->details['reference'], $params, now()->addDays(1));
      
      $transaction_details = $flutterwave->details;

      return $order->payment_link;
		}



		public function complete_payment(Request $request)
		{
			if(stripos($request->get('processor'), $this->name) !== false && $request->get('order_id') !== null)
      {
        $transaction_id = $request->get('order_id');

        if(is_null($transaction_id))
        {
          return [
            'status' => false, 
            'user_message' => __('Missing transaction order_id.')
          ];
        }

        $transaction =  Transaction::where(['processor' => $this->name])
                        ->where(function($builder) use($transaction_id)
                        {
                          $builder->where(['transaction_id' => $transaction_id])
                                  ->orWhere(['order_id' => $transaction_id])
                                  ->orWhere(['reference_id' => $transaction_id]);
                        })
                        ->first();

        if(!$transaction)
        {
          return [
            'status' => false, 
            'user_message' => __('Missing transaction database record [:transaction_id].', ['transaction_id' => $transaction_id])
          ];
        }

        if($transaction->status !== 'paid')
        {
	        $transaction->status = 'pending';
	        $transaction->confirmed = $request->get('success');

	        $transaction->save();
      	}

        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
		}



		public function handle_webhook_notif(Request $request)
    {
    	$response = ['status' => 0, 'transaction' => null, 'valid' => 0];

    	if(stripos($request->get('processor'), $this->name) !== false && $request->get('order_id') !== null)
      {
      	$status['valid'] = 1;

      	if(strtolower($request->post('status')) === 'paid')
      	{
      		$order_id = $request->post('order_id');

      		$transaction = 	Transaction::where(function($query) use($order_id)
							        		{
							        			$query->where('order_id', $order_id)
							        						->orWhere('transaction_id', $order_id)
							        						->orWhere('reference_id', $order_id);
							        		})
							        		->where(['processor' => $this->name, 'status' => 'pending'])
							        		->first();

					if($transaction)
					{
						$transaction->status = 'paid';
						$transaction->confirmed = 1;

						$transaction->save();

						$response['status'] = 1;
						$response['transaction'] = $transaction;
					}
      	}
      }

			return $response;
    }
	}