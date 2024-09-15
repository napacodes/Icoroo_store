<?php

	namespace App\Libraries;

	use YouCan\Pay\YouCanPay;
	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
	use Illuminate\Http\Request;
	use App\Models\{ Transaction };

	class YCPay
	{
		public $name = "youcanpay";
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ["AUD", "BRL", "CAD", "CNY", "CZK", "DKK", "EUR", "HKD", "HUF", "INR", "ILS", "JPY", "MYR", "MXN", "TWD", "NZD", "NOK", "PHP", "PLN", "GBP", "RUB", "SGD", "SEK", "CHF", "THB", "USD"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $error_msg;
		public static $response = "default";


		public function __construct()
		{
			$this->return_url = route("home.checkout.order_completed", ['processor' => $this->name]);
			$this->cancel_url = config("checkout_cancel_url");

			exists_or_abort(config("payments_gateways.{$this->name}.enabled"), __(":payment_proc is not enabled", ["payment_proc" =>  "Paypal"]));

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

	      	$breakdown["tax_total"] = ["currency_code" => $this->currency_code,"value" => $value];

		      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

		      $total_amount += format_amount($tax ?? 0, false, $this->decimals);	
      	}
				
				if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$handling = config("payments_gateways.{$this->name}.fee", 0);
	      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

	      	$breakdown["handling"] = ["currency_code" => $this->currency_code,"value" => $value];

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

		      $total_amount += format_amount($value, false, $this->decimals);
	      }      	
      }


      $this->details["total_amoun"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      $total_amount = (int)ceil(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));
      
      YouCanPay::setIsSandboxMode(config('payments.gateways.youcanpay.mode') == 'sandbox');

      $youCanPay = YouCanPay::instance()->useKeys(config('payments.gateways.youcanpay.private_key'), config('payments.gateways.youcanpay.public_key'));

      $user_info = [];

      $metadata = [
      	"reference" => $this->details['reference']
      ];

      $token = $youCanPay->token->create(
          $this->details['reference'],
          $total_amount,
          $this->currency_code,
          request()->ip(),
          $this->return_url,
          $this->cancel_url,
          $user_info,
          $metadata
      );

      return $token->getPaymentURL(config('app.locale', 'en'));
		}



		public function init_payment(array $config)
		{
			extract($config);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $payment_url = $this->create_order($params) ?? abort(404);

      return $payment_url;
		}



		public function complete_payment(Request $request)
		{
			if(stripos($request->processor, $this->name) !== false && $request->order_id)
      {
      	if(!$transaction_id = $request->order_id)
      	{
      		return [
      			'status' => false, 
      			'user_message' => __('Missing transaction order_id.')
      		];
      	}

      	$transaction = 	Transaction::where(['processor' => $this->name])
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

        $transaction->status = 'pending';
        $transaction->save();
      
        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
		}



		public function handle_webhook_notif(Request $request)
		{
			$response = ['status' => 0, 'transaction' => null, 'valid' => 0];

			$signature = $request->header('X-Youcanpay-Signature');

			if($signature && (stripos($request->header('User-Agent'), 'YouCanPay/1.0') !== false))
			{
				$status['valid'] = 1;
				
        $youcanPay = YouCanPay::instance()->useKeys(config('payments.gateways.youcanpay.private_key'), 
        																						config('payments.gateways.youcanpay.public_key'));

        if($result = $youcanPay->verifyWebhookSignature($signature, $request->post()))
        {
        	if($request->input('payload.transaction.status') == 1)
        	{
        		$order_id = $request->input('payload.transaction.order_id');

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
			}

			return $response;
		}

}