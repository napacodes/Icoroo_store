<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
	use Paymentwall_Config;
	use Paymentwall_Product;
	use Paymentwall_Widget;
	use Paymentwall_Base;
	use Paymentwall_Pingback;
	use Illuminate\Http\Request;
	use App\Models\{ Transaction };

	class Paymentwall 
	{
		public $name = "paymentwall";
		public static $static_name = "paymentwall";
		public $pingback_url;
		public $return_url;
		public $cancel_url;
		public $currency_code;
		public $exchange_rate = 1;
		public $default_currency;
		public $decimals;
		public $details  = [];
		public $error_msg;
		public static $response = "default";



		public function __construct()
		{
      exists_or_abort(config("payments_gateways.{$this->name}.enabled"), __(":payment_proc is not enabled", ["payment_proc" =>  "Paymentwall"]));

      $this->currency_code = config("payments.currency_code");
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

      $this->return_url 	= route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
			$this->cancel_url 	= config("checkout_cancel_url");
			$this->pingback_url	= route('home.checkout.webhook', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
		}



		public function create_order(array $params)
		{
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


      $total_amount = $unit_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if(!$prepaid_credits_pack_id)
      {
	      if(config("payments.vat", 0))
	      {
	      	$tax = ($unit_amount * config("payments.vat", 0)) / 100;
	      	$value = format_amount($tax, false, $this->decimals);

		      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

		      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
	      }


	      if(config("payments.spankpay.fee", 0))
	      {
	      	$value = format_amount(config("payments.spankpay.fee", 0) * $this->exchange_rate, false, $this->decimals);

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

		      $total_amount += format_amount($value, false, $this->decimals);
	      }
      }


      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);


      Paymentwall_Config::getInstance()->set([
			  "api_type" 		=> Paymentwall_Config::API_GOODS,
			  "public_key"  => config("payments_gateways.{$this->name}.project_key"),
			  "private_key" => config("payments_gateways.{$this->name}.secret_key")
			]);


      $total_amount = format_amount($total_amount, false, $this->decimals);
      $user_id      = Auth::check() ? Auth::id() : uuid6();
      $extra_params = [
      	"order_id"  		=> $this->details['reference'],
      	"success_url" 	=> $this->return_url,
      	"pingback_url"	=> $this->pingback_url,
      	"failed_url" 		=> $this->cancel_url,
      	"evaluation" 		=> config("payments_gateways.{$this->name}.mode") === "live" ? "0" : "1"
      ];

      $product      = [        
				new Paymentwall_Product(
					__("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
					$total_amount,
					$this->currency_code,
					__("Order :num", ["num" => generate_transaction_ref()]),
					Paymentwall_Product::TYPE_FIXED
				)
		  ];

			$widget = new Paymentwall_Widget($user_id, "pw", $product, $extra_params);

			return $widget->getUrl();
		}



		public function validate_webhook($request)
		{
			$expected_sig = $request->get('sig');

			$params = $_GET;

			unset($params['sig']);

			ksort($params);

			$base_hash = "";

			foreach($params as $k => $v)
			{
			  $base_hash .= "{$k}={$v}";
			}

			$base_hash .= config("payments_gateways.{$this->name}.secret_key");

			$calculated_sig  = md5($base_hash);

			if(hash_equals($expected_sig, $calculated_sig))
			{
				return $request->get('type') === "0";
			}

			return false;
		}



		public function init_payment(array $config)
		{
			extract($config);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $payment_url = $this->create_order($params);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $order_id = get_url_param($payment_url, 'order_id') ?? abort(404);
     
      $this->details['transaction_id'] = $order_id;
      $params['transaction_details'] = $this->details;
      $params['order_id'] = $order_id;


      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $order_id, now()->addDays(1));  
      }
      else
      {
        Session::put('payment', $order_id);
      }

      Cache::put($order_id, $params, now()->addDays(1));
    
      return $payment_url;
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

        if($this->validate_webhook($request))
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
          	$transaction->transaction_id = $request->get('ref');
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