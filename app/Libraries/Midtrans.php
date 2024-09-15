<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use App\Models\{ Transaction };

	class Midtrans 
	{
		public $name = "midtrans";
		public $supported_currencies = ["IDR"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals = 0;
		public $details = [];
		public  $error_msg;
		public $enabled_payments = [
							"credit_card", 
							"mandiri_clickpay", 
							"cimb_clicks", 
							"bca_klikbca", 
							"bca_klikpay", 
							"bri_epay", 
							"echannel", 
							"mandiri_ecash", 
							"permata_va", 
							"bca_va", 
							"bni_va", 
							"other_va", 
							"gopay", 
							"indomaret", 
							"alfamart", 
							"danamon_online", 
							"akulaku"
            ];
    public static $response = "default";



    public function __construct()
		{
			exists_or_abort(config("payments_gateways.{$this->name}.enabled"), __(":payment_proc is not enabled", ["payment_proc" =>  "Midtrans"]));

			$this->currency_code = config("payments.currency_code");
			$this->decimals 	   = config("payments.currencies.{$this->currency_code}.decimals");
			$this->enabled_payments = array_filter(explode(",", config("payments_gateways.{$this->name}.methods"))) ?? $this->enabled_payments;

			prepare_currency($this);

      $this->details = [
      	"items" 				=> [],
	      "total_amount"  => 0,
	      "currency" 		  => $this->currency_code,
	      "exchange_rate" => $this->exchange_rate,
	      "custom_amount" => null,
	      "reference" 		=> generate_transaction_ref(),
	      "transaction_id" => null,
        "order_id" => null,
	    ];

	    $this->return_url  = route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
	    $this->cancel_url  = route('home.checkout');
	    $this->webhook_url = route('home.checkout.webhook', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
		}




		public function create_order(array $params)
		{			
			extract($params);
      
			$total_amount = 0;


			foreach($cart as $item)
			{
				$total_amount += $item->price;

				$this->details["items"][] = [
					"name" 		=> $item->name, 
					"id" => $item->id ?? null,
					"value" 	=> format_amount($item->price * $this->exchange_rate, false, $this->decimals),
					"license" => $item->license_id ?? null
				];
			}


			if(!$prepaid_credits_pack_id)
      {
				if(config("pay_what_you_want.enabled") && config("pay_what_you_want.for.".($subscription_id ? "subscriptions": "products")) && $custom_amount)
	      {
	        $total_amount = $custom_amount;

	        $this->details["custom_amount"] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
	      }


				if(($coupon->status ?? null) && !$custom_amount)
	      {
	      	$total_amount -= $coupon->coupon->discount ?? 0;

	      	$this->details["items"]["discount"] = ["name" => __("Discount"), "value" => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
	      }
	    }


      $total_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);

      $item_details = [];

      $item_details[] = [
			      		"id" 			 => "PURCHASE",
                "name" 		 => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
                "price" 	 => ceil($total_amount),
                "quantity" => 1
              ];


      if(!$prepaid_credits_pack_id)
      {
	      if(config("payments.vat", 0))
	      {
	      	$tax = ($total_amount * config("payments.vat", 0)) / 100;

	      	$value = format_amount($tax ?? 0, false, $this->decimals);

	      	$item_details[] = [
	      		"id" 			 => "TAX",
	          "name" 		 => __("VAT :percent%", ["percent" => config("payments.vat", 0)]),
	          "price" 	 => ceil($value),
	          "quantity" => 1
	      	];

		      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

		      $total_amount += $value;
	      }


	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$value = format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals);

	      	$item_details[] = [
	      		"id" 			 => "FEE",
	          "name" 		 => __("Handling fee"),
	          "price" 	 => ceil($value),
	          "quantity" => 1
	      	];

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

		      $total_amount += $value;
	      }
      }


      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      session(["transaction_details" => $this->details]);

      $total_amount = format_amount($total_amount, false, $this->decimals);


			$payload = [
				"transaction_details" => 	[
				    "order_id" => $this->details['reference'],
				    "gross_amount" => array_sum(array_column($item_details, "price")),
				],
				"item_details" => $item_details,
				"enabled_payments" => $this->enabled_payments,
				"callbacks" => [
					"finish" => $this->return_url,
					"unfinish" => $this->cancel_url,
					"error" => route('home')
				]
			];

			$ch 			= curl_init();
			$api_url 	= "https://app.sandbox.midtrans.com/snap/v1/transactions/";

			if(config("payments_gateways.{$this->name}.mode") === "live")
			{
				$api_url = "https://app.midtrans.com/snap/v1/transactions/";
			}
			
			$headers = [
				"Accept: application/json",
				"Content-Type: application/json",
				"Authorization: Basic " . base64_encode(config("payments_gateways.{$this->name}.server_key").":"),
				"X-Override-Notification: {$this->webhook_url}",
				"X-Append-Notification: {$this->webhook_url}",
				"cache-control: no-cache",
			];

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			if(curl_errno($ch) || !json_decode($result))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				$this->error_msg = ["user_message" => $error_msg];

				return;
      }
      
      curl_close($ch);

      $result = json_decode($result);

      if(($result->error_messages ?? null))
      {
      	settype($result->error_messages, "array");

      	$this->error_msg = ["user_message" => implode(",", array_values($result->error_messages))];

				return;
      }

      return $result;
		} 



		public function status(string $orderId)
		{
			$ch 			= curl_init();
			$api_url 	= "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

			if(config("payments_gateways.{$this->name}.mode") === "live")
				$api_url = "https://api.midtrans.com/v2/{$orderId}/status";
			
			$headers = [
				"Accept: application/json",
				"Content-Type: application/json",
				"Authorization: Basic " . base64_encode(config("payments_gateways.{$this->name}.server_key").":"),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			return json_decode($result);
		}




		public function approve($orderId)
		{
			$ch 			= curl_init();
			$api_url 	= "https://api.sandbox.midtrans.com/v2/{$orderId}/approve";

			if(config("payments_gateways.{$this->name}.mode") === "live")
				$api_url = "https://api.midtrans.com/v2/{$orderId}/approve";
			
			$headers = [
				"Accept: application/json",
				"Content-Type: application/json",
				"Authorization: Basic " . base64_encode(config("payments_gateways.{$this->name}.server_key").":"),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			return json_decode($result);
		}




		public function capture($transaction_id)
		{
			$ch 			= curl_init();
			$api_url 	= "https://api.sandbox.midtrans.com/v2/capture";

			if(config("payments_gateways.{$this->name}.mode") === "live")
				$api_url = "https://api.midtrans.com/v2/capture";
			
			$headers = [
				"Accept: application/json",
				"Content-Type: application/json",
				"Authorization: Basic " . base64_encode(config("payments_gateways.{$this->name}.server_key").":"),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["transaction_id" => $transaction_id]));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			return json_decode($result);
		}



		public function init_payment(array $config)
		{
			extract($config);

      $order = $this->create_order($params);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      if($order->redirect_url ?? null)
      {
      	$this->details['transaction_id'] = $order->token;

        $params['transaction_details'] = $this->details;
        $params['transaction_id'] = $order->token;

        if($return_url && $user)
        {
          Cache::put("payment_{$user->id}", $order->token, now()->addDays(1)); 
        }
        else
        {
          Session::put('payment', $order->token);
        }

        Cache::put($order->token, $params, now()->addDays(1));

        return $order->redirect_url;
      }

      return ['user_message' => __("We couldn't not create a payment link.")];
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

      if(stripos($request->processor, $this->name) !== false && $request->order_id !== null)
      {        
        $status['valid'] = 1;

        $success = in_array(mb_strtolower($request->input('transaction_status')), ['settlement', 'capture']);

        $expected_sig = hash('sha512', $request->order_id . $request->input('status_code') . (string)$request->input('gross_amount') . config("payments_gateways.{$this->name}.server_key"));

        if($success && ($expected_sig == $request->input('signature_key')))
        {
          $order_id = $request->order_id;

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

            $transaction->save();

            $response['status'] = 1;
            $response['transaction'] = $transaction;
          }
        }
      }

      return $response;
    }
	}