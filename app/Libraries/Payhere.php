<?php

namespace App\Libraries;

use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
use Illuminate\Http\Request;
use App\Models\{ Transaction };

class Payhere 
{
    public $name = "payhere";
    public $return_url;
    public $cancel_url;
    public $notify_url;
    public $supported_currencies = ["LKR", "USD", "GBP", "EUR", "AUD"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $default_currency;
    public $details  = [];
    public $error_msg;
    public static $response = "json";


		public function __construct()
		{
			if(!config("payments_gateways.{$this->name}"))
			{
				return response()->json(["user_message" => __(":payment_proc is not enabled", ["payment_proc" =>  "payhere"])]);
			}
            
			$this->currency_code = config("payments.currency_code");
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			prepare_currency($this);

      $this->details = [
      	"items" => [],
	      "total_amount" => 0,
	      "currency" => $this->currency_code,
	      "exchange_rate" => $this->exchange_rate,
	      "custom_amount" => null,
	      "reference" => generate_transaction_ref()
	    ];

	    $this->return_url = route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
			$this->cancel_url = config("checkout_cancel_url");
			$this->notify_url = route('home.checkout.webhook', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
		}



		public function create_order(array $params, object $buyerInf)
		{			
			extract($params);

			$ch 			= curl_init();
			$api_url 	= "https://sandbox.payhere.lk/pay/checkout";

			if(config("payments_gateways.{$this->name}.mode") === "live")
				$api_url = "https://www.payhere.lk/pay/checkout";

			$total_amount = 0;

			$items = [];

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

			$breakdown = [];

      $items[] = [
      	"item_name_1" => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
      	"amount_1" 	  => $unit_amount,
      	"quantity_1" 	  => 1
      ];


      if(!$prepaid_credits_pack_id)
      {
	      if(config("payments.vat", 0))
	      {
	      	$tax = ($unit_amount * config("payments.vat", 0)) / 100;
	      	$value = format_amount($tax, false, $this->decimals);

	      	$items[] = [
		      	"item_name_2" => __("Tax"),
		      	"amount_2" 	  => $value,
		      	"quantity_2" 	  => 1
		      ];

		      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

		      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
	      }


	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$value = format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals);

	      	$items[] = [
		      	"item_name_3" => __("Fee"),
		      	"amount_3" 	  => $value,
		      	"quantity_3" 	  => 1
		      ];

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

		      $total_amount += format_amount($value, false, $this->decimals);
	      }
      }

      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      session(["transaction_details" => $this->details]);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $payload = [
      	"merchant_id" => config("payments_gateways.{$this->name}.merchant_id"),
      	"return_url" => $this->return_url,
      	"cancel_url" => $this->cancel_url,
      	"notify_url" => $this->notify_url,
      	"first_name" => $buyerInf->firstname,
      	"last_name" => $buyerInf->lastname,
      	"email" => $buyerInf->email,
				"address" => $buyerInf->address,
				"city" => $buyerInf->city,
				"country" => $buyerInf->country,
				"order_id" => $this->details['reference'],
				"items" => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
				"currency" => $this->currency_code,
				"amount" => $total_amount
      ];

      return array_merge($payload, ...$items);
		}



		public function init_payment(array $config)
		{
      extract($config);

			$request->validate([
              'buyer.firstname' => 'string|required',
              'buyer.lastname'  => 'string|required',
              'buyer.city'      => 'string|required',
              'buyer.country'   => 'string|required',
              'buyer.address'   => 'string|required',
              'buyer.email'     => 'email|required'
            ]);

      $buyer = (object) $request->input('buyer');

      $buyer->ip_address = $request->ip();

      $payhere = new Payhere();

      if($payhere->error_msg)
      {
        return $payhere->error_msg;
      }

      $payload = $payhere->create_order($params, $buyer) ?? abort(404);

      if($payhere->error_msg)
      {
        return $payhere->error_msg;
      }

      $params['transaction_details'] = $payhere->details;
      $params['order_id'] = $payload['order_id'];

      Session::put('payment', $payload['order_id']);

      Cache::put($payload['order_id'], $params, now()->addDays(1));

      return compact('payload');
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

      if(stripos($request->processor, $this->name) !== false && $request->input('data.id') !== null)
      {        
        $status['valid'] = 1;

        $paid				= $request->input('data.paid') === true;
        $authorized	= $request->input('data.authorized') === true;
        $capture		= $request->input('data.capture') === true;

        if($paid && $authorized && $capture)
        {
          $order_id = $request->input('data.id');

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