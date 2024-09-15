<?php

	namespace App\Libraries;

  use App\User;
  use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
	use Illuminate\Http\Request;
  use App\Models\{ Transaction };

	class Paystack 
	{
		public $name = "paystack";
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ["USD", "GHS", "NGN"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $channels = ["card", "bank", "ussd", "qr", "mobile_money", "bank_transfer"];
		public $error_msg;
		public static $response = "default";


		public function __construct()
		{
			exists_or_abort(config("payments_gateways.{$this->name}.enabled"), __(":payment_proc is not enabled", ["payment_proc" =>  "Paystack"]));
            
			$this->currency_code = config("payments.currency_code");
			$this->channels = array_filter(explode(",", config("payments_gateways.{$this->name}.channels"))) ?? $this->channels;
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			prepare_currency($this);

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

	    $this->return_url  = route("home.checkout.order_completed", ['processor' => $this->name, 'order_id' => $this->details['reference']]);
			$this->cancel_url  = config('checkout_cancel_url');
		}


    
		public function create_transaction(array $params, string $user_email)
		{
			/* DOC : https://paystack.com/docs/api/#transaction-initialize
			--------------------------------------------------------------
				curl https://api.paystack.co/transaction/initialize
				-H "Authorization: Bearer YOUR_SECRET_KEY"
				-H "Content-Type: application/json"
				-d "{ email: "customer@email.com", amount: "20000" }"
				-X POST*/

			extract($params);

			$total_amount = 0;

			foreach($cart as $item)
			{
				$total_amount += $item->price;

				$this->details["items"][] = [
					"name" => $item->name, 
					"id" => $item->id ?? null,
					"value" => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
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


      $total_amount = (int)ceil(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));

			$line_items = [];

      $line_items[] = [
        "name" => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
        "amount" => $total_amount
      ];


      if(!$prepaid_credits_pack_id)
      {
	      if(config("payments.vat", 0))
	      {
	      	$value = (int)ceil(($total_amount * config("payments.vat", 0)) / 100);

	      	$line_items[] = [
	      		"name" => __("Tax"),
	          "description" => config("payments.vat")."%",
	          "amount" => $value
		      ];

		      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

		      $total_amount += $value ?? 0;
	      }


	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$value = (int)ceil(format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

	      	$line_items[] = [
	      		"name" => __("Fee"),
	          "description" => __("Handling fee"),
	          "amount" => $value
		      ];

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

		      $total_amount += $value;
	      }
      }

      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);


      session(["transaction_details" => $this->details]);


			$payload = [
				"email" 			 => $user_email,
        "amount" 			 => ceil($total_amount),
        "currency" 		 => $this->currency_code,
        "callback_url" => $this->return_url,
        "channels" 		 => $this->channels,
        "metadata" 		 => ["cancel_action" => $this->cancel_url, "line_items" => $line_items]
      ];
      
      $ch 		 = curl_init();
			$api_url = "https://api.paystack.co/transaction/initialize";

      $secret_key  = config("payments_gateways.{$this->name}.secret_key");

      curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "Authorization: Bearer {$secret_key}"]);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
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

      if(!$result->status)
      {
      	$this->error_msg = ["user_message" => $result->message];

				return;
      }

			return $result;
		}



		public function verify_transaction($reference)
		{

			/* DOC : https://paystack.com/docs/api/#transaction-verify
			----------------------------------------------------------
			curl https://api.paystack.co/transaction/verify/:reference
			-H "Authorization: Bearer YOUR_SECRET_KEY"
			-X GET
			*/

			$ch 		 = curl_init();
			$api_url = "https://api.paystack.co/transaction/verify/{$reference}";

      $secret_key  = config("payments_gateways.{$this->name}.secret_key");


      curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "Authorization: Bearer {$secret_key}"]);
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

	    if(!$result->status)
	    {
	      $this->error_msg = ["user_message" => $result->message];

				return;
	    }

			return $result;
		}



		public function fetch_transaction($transaction_id)
		{
			/* DOC : https://paystack.com/docs/api/#transaction-fetch
			---------------------------------------------------------
			curl https://api.paystack.co/transaction/:id
			-H "Authorization: Bearer YOUR_SECRET_KEY"
			-X GET
			*/

			$ch 		 = curl_init();
			$api_url = "https://api.paystack.co/transaction/{$transaction_id}";

      $secret_key  = config("payments_gateways.{$this->name}.secret_key");

      curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "Authorization: Bearer {$secret_key}"]);
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

      if(!$result->status)
      {
        $this->error_msg = ["user_message" => $result->message];

				return;
      }

			return $result;
		}



		public function init_payment(array $config)
		{
			extract($config);

      $user_email = $params['user_email'] ?? null;

      if(!$user_email)
      {
        $request->validate(['email' => 'email|required']);

        $user_email = $request->post('email');
      }

      $response = $this->create_transaction($params, $user_email);
      
      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $this->details['transaction_id'] = $response->data->reference;
      $this->details['order_id'] 			 = $response->data->access_code;

      $params['transaction_details'] = $this->details;
      $params['reference_id']        = $response->data->reference;
      $params['order_id']            = $response->data->access_code;

      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $params['reference_id'], now()->addDays(1)); 
      }
      else
      {
        Session::put('payment', $params['reference_id']);
      }

      Cache::put($params['reference_id'], $params, now()->addDays(1));

      return $response->data->authorization_url;
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
    	$notif    = json_decode(file_get_contents("php://input"));
      $response = ['status' => 0, 'transaction' => null, 'valid' => 0];
      $event    = $notif->event ?? null;

      if(stripos($request->processor, $this->name) !== false && $request->input('event') === 'charge.success' && $request->input('data.reference'))
      {        
        $status['valid'] = 1;

        $expected_sig   = $request->header('X-Paystack-Signature');
        $calculated_sig = hash_hmac('sha512', file_get_contents("php://input"), config("payments_gateways.{$this->name}.secret_key"));
        $success        = strtolower($request->input('data.status')) === 'success';

        if($success && ($calculated_sig === $expected_sig))
        {
          $order_id = $request->input('data.reference');

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