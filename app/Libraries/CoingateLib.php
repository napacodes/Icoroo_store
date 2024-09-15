<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
	use CoinGate\Merchant\Order;
	use CoinGate\{ Client };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };

	class CoingateLib
	{
		public $name = "coingate";
		public $return_url;
		public $cancel_url;
		public $client;
		public $supported_currencies = ["BTC", "EUR", "GBP", "USD"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details = [];
		public $error_msg;
		public static $response = "default";


		public function __construct()
		{
			exists_or_abort(config("payments_gateways.{$this->name}"), __(":payment_proc is not enabled", ["payment_proc" =>  "CoinGate"]));

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

	    $this->return_url = route("home.checkout.order_completed", ['processor' => $this->name, 'order_id' => $this->details['reference']]);
			$this->cancel_url = config("checkout_cancel_url");

	    $this->client = new Client(config("payments_gateways.{$this->name}.auth_token"), config("payments_gateways.{$this->name}.mode"));
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
					$gross_amount = $custom_amount;

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
      }


      if(!$prepaid_credits_pack_id)
      {
	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$value = format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals);

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

		      $total_amount += format_amount($value, false, $this->decimals);
	      }
      }


      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      session(["transaction_details" => $this->details]);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $callback_url = route("home.checkout.webhook", ['processor' => $this->name, 'order_id' => $this->details['reference'], 't' => time()]);
      								//"https://c713-197-144-181-96.eu.ngrok.io/checkout/webhook?processor={$this->name}&order_id={$this->details['reference']}&t=".time();

			$payload = [
                   "order_id"          => $this->details['reference'],
                   "price_amount"      => $total_amount,
                   "price_currency"    => $this->currency_code,
                   "receive_currency"  => config("payments_gateways.{$this->name}.receive_currency"),
                   "cancel_url"        => $this->cancel_url,
                   "success_url"       => "{$this->return_url}&order_id={$this->details['reference']}",
                   "callback_url"      => $callback_url,
                   "title"             => __("Order"),
                   "description"       => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))])
               	];

      return $this->client->order->create($payload);
		}




		public function get_order(string $order_id)
		{
			return $this->client->order->get($order_id);
		}



		public function init_payment(array $config)
		{
			extract($config);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      if($order = $this->create_order($params))
      {          
        if($order->status === 'new')
        {
          $this->details['transaction_id'] = $order->order_id;
          $this->details['order_id'] = $order->id;

          return $order->payment_url;
        }
        else
        {
          return ['user_message' => __('Order already created.')];
        }
      }
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