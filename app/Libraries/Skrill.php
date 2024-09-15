<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Cache, Session, Auth };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };


	class Skrill 
	{
		public $name = 'skrill';
		public $status_url;
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ['EUR' , 'TWD', 'USD', 'THB', 'GBP', 'CZK', 'HKD', 'HUF', 'SGD', 'BGN', 'JPY', 'PLN', 'CAD', 'ISK', 'AUD', 'INR', 'CHF', 'KRW', 'DKK', 'ZAR', 'SEK', 'RON', 'NOK', 'HRK', 'ILS', 'JOD', 'MYR', 'OMR', 'NZD', 'RSD', 'TRY', 'TND', 'AED', 'MAD', 'QAR', 'SAR'];
		public $payment_methods = ['WLT', 'NTL', 'PSC', 'PCH', 'ACC', 'VSA', 'MSC', 'VSE', 'MAE', 'GCB', 'DNK', 'PSP', 'CSI', 'ACH', 'GCI', 'IDL', 'PWY', 'GLU', 'ALI', 'ADB', 'AOB', 'ACI'];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $error_msg;
		public static $response = "default";



		public function __construct()
		{
      exists_or_abort(config("payments_gateways.{$this->name}"), __(":payment_proc is not enabled", ["payment_proc" =>  $this->name]));

      $this->api_key  = config("payments_gateways.{$this->name}.api_key");
      $this->currency_code = config("payments.currency_code");
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

      prepare_currency($this);

      if($method_types = array_filter(explode(",", config("payments_gateways.{$this->name}.methods"))))
      {
      	$this->payment_methods = array_intersect($this->payment_methods, $method_types);
      }
      
      $this->details = [
        'items' => [],
        'gross_amount' => 0,
        'currency' => $this->currency_code,
        'exchange_rate' => $this->exchange_rate,
        'custom_amount' => null,
        'reference' => generate_transaction_ref(),
        'transaction_id' => null,
        'order_id' => null,
      ];

      $this->status_url = route('home.checkout.webhook', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
			$this->return_url = route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
      $this->cancel_url = config('checkout_cancel_url');
		}



		public function checkout_session_id(array $params, $user_id = null)
		{
			extract($params);

			$ch 		 = curl_init();
			$api_url = 'https://pay.skrill.com';

			$gross_amount = 0;
			$fee 					= 0;
			$tax 					= 0;
			$total_due 		= 0;

			foreach($cart as $item)
			{
      	$gross_amount += $item->price;

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
					$gross_amount = $custom_amount;

					$this->details['custom_amount'] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
				}


				if(($coupon->status ?? null) && !$custom_amount)
	      {
	      	$gross_amount -= $coupon->coupon->discount ?? 0;

	      	$this->details['items']['discount'] = ['name' => __('Discount'), 'value' => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
	      }
      }


      $gross_amount = format_amount($gross_amount * $this->exchange_rate, false, $this->decimals);


      if(!$prepaid_credits_pack_id)
      {
	      if($tax = config('payments.vat', 0))
	      {
	      	$tax = format_amount(($gross_amount * config('payments.vat', 0)) / 100, false, $this->decimals);

		      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $tax];

		      $gross_amount += format_amount($tax ?? 0, false, $this->decimals);
	      }

	      if($handling = config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$fee = format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals);

		      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $fee];

		      $gross_amount += format_amount($fee, false, $this->decimals);
	      }
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      $total_due = format_amount(($gross_amount + $fee + $tax), false, $this->decimals);
      $order_id  = $this->details['reference'];

			$payload = [
			  "pay_to_email" => config("payments_gateways.{$this->name}.merchant_account"),
			  "prepare_only" => 1,
			  "status_url" => $this->status_url,
			  "return_url" => $this->return_url,
			  "return_url_text" => "Return",
			  "return_url_target" => "1",
			  "cancel_url" => $this->cancel_url,
			  "cancel_url_target" => "1",
			  "dynamic_descriptor" => "Descriptor",
			  "merchant_fields" => "user_id",
			  "user_id" => $user_id,
			  "language" => "EN",
			  "logo_url" => asset("storage/images/".config('app.logo')),
			  "amount" => $total_due,
			  "currency" => $this->currency_code,
			  "amount2_description" => __('Gross amount : '),
			  "amount2" => $gross_amount,
			  "amount3_description" => __('Handling Fees : '),
			  "amount3" => $handling,
			  "amount4_description" => __('VAT :percent% : ', ['percent' => config('payments.vat')]),
			  "amount4" => $tax,
			  "detail1_description" => "ID : ",
			  "detail1_text" => $this->details['reference'],
			  "submit_id" => "Submit",
			  "Pay" => "Pay",
			  "payment_methods" => implode(',', $this->payment_methods)
			];

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: en_US']);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			$curl_errno = curl_errno($ch);
			$error_msg = curl_error($ch);

			curl_close($ch);

			if($curl_errno)
			{
				$this->error_msg = ['user_message' => $error_msg];

				return;
			}

			if(json_decode($result))
			{
				$this->error_msg = ['user_message' => $result];

				return;
			}

			return ['sid' => $result, 'order_id' => $order_id];
		}



		public function init_payment(array $config)
    {
      extract($config);

      $response = $this->checkout_session_id($params);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $response['sid'] ?? abort(404);

      $this->details['transaction_id'] = $response['sid'];

      $params['transaction_details'] = $this->details;
      $params['reference_id']        = $response['order_id'];

      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $params['reference_id'], now()->addDays(1)); 
      }
      else
      {
        Session::put('payment', $params['reference_id']);
      }

      Cache::put($params['reference_id'], $params, now()->addDays(1));

      return "https://pay.skrill.com/app/?sid={$response['sid']}";
    }




    public function complete_payment(Request $request)
    {
      if(stripos($request->processor, $this->name) !== false && $request->order_id !== null)
      {
        $transaction_id = $request->order_id;

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
          $transaction->save();
        }

        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
    }



    public function handle_webhook_notif(Request $request)
    {
      $response = ['status' => 0, 'transaction' => null, 'valid' => 0];

      if(stripos($request->processor, $this->name) !== false)
      {
        $status['valid'] = 1;
        
        $merchant_id     = $request->input('merchant_id');
	      $transaction_id  = $request->input('transaction_id');
	      $secret_word     = strtoupper(md5(config("payments_gateways.{$this->name}.mqiapi_secret_word")));
	      $mb_amount       = $request->input('mb_amount');
	      $mb_currency     = $request->input('mb_currency');
	      $status          = $request->input('status');

	      $digest = strtoupper(md5($merchant_id . $transaction_id . $secret_word . $mb_amount . $mb_currency . $status));

        if($digest === $request->input('md5sig') && $status === "2")
        {
          $order_id = $request->get('order_id');

          $transaction =  Transaction::where(function($query) use($order_id)
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
            $transaction->transaction_id = $transaction_id;

            $transaction->save();

            $response['status'] = 1;
            $response['transaction'] = $transaction;
          }
        }
      }

      return $response;
    }

	}