<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
	use App\Models\{ User_Prepaid_Credit, Affiliate_Earning, Transaction };

	class Credits
	{
		public $name = "credits";
		public $return_url;
		public $cancel_url;
		public $supported_currencies = [];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details = [];
		public $error_msg;
		public static $response = "json";


		public function __construct()
		{
			$this->return_url = route("home.checkout.order_completed");
			$this->cancel_url = config("checkout_cancel_url");

			exists_or_abort(config("payments_gateways.{$this->name}"), __(":payment_proc is not enabled", ["payment_proc" =>  mb_ucfirst($this->name)]));

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
				"available_credits" => user_credits(true)
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

      if(!($coupon->status ?? null) && $processor == 'credits')
      {
      	$user_prepaid_credits = $this->details['available_credits']['prepaid_credits'] ?? [];
      	
      	foreach($user_prepaid_credits as $src)
      	{
      		if($src->discount > 0 && $src->credits > 0)
	      	{
	      		$discount = (float)($total_amount * ($src->discount / 100));

	      		$total_amount -= $discount;

	      		$this->details["items"]["discount"] = [
	      			"name" 	=> __("Credits discount"),
	      			"value" => -format_amount($discount * $this->exchange_rate, false, $this->decimals)
	      		];

	      		break;
	      	}
      	}
      }


      $total_amount = $unit_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if($vat = config("payments.vat", 0))
      {
      	$tax = ($unit_amount * $vat) / 100;
      	$value = format_amount($tax, false, $this->decimals);

	      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

	      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config("payments_gateways.{$this->name}.fee", 0))
      {
      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

	      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

	      $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $this->details['transaction_id'] = generate_transaction_ref();
      $this->details['order_id'] 			 = generate_transaction_ref();

      $total_available_credits = $this->details['available_credits']['total_available_credits'] ?? 0;

      if($total_available_credits < $total_amount)
      {
      	$this->error_msg = ['user_message' => __('Your credits balance is not enough to complete this purchase.')];
      }

      return route('home.credits_checkout', ['transaction_id' => $this->details['transaction_id']]);
		}



		public function init_payment(array $config)
		{
			extract($config);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      try
      {
      	$checkout_url = $this->create_order($params);

      	if($this->error_msg)
	      {
	        return $this->error_msg;
	      }

      	return ['status'  => 1, 'checkout_url' => $checkout_url];
      }
      catch(\Exception $e)
      {
      	return ['status' => 0, 'user_message' => $e->getMessage()];
      }
		}



		public function complete_payment(Request $request)
		{
			if(mb_strtolower($request->processor) === 'credits' && $request->query('user_id') && $request->query('transaction_id'))
      {
      	$transaction_id = $request->query('transaction_id');
      	$user_id 				= $request->query('user_id');

      	if($user_id != \Auth::id())
      	{
      		return [
      			'status' => false,
      			'user_message' => __('Wrong request.')
      		];
      	}

      	$transaction = 	Transaction::where(['processor' => $this->name])
								      	->where(function($builder) use($transaction_id)
								    		{
								    			$builder->where(['transaction_id' => $transaction_id])
								    							->orWhere(['order_id' 		=> $transaction_id])
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

        $transaction->status = 'paid';
        $transaction->confirmed = 1;

        $transaction->save();

        extract($this->details['available_credits']);

        $user_credits_sources = explode(',', ($request->user()->credits_sources));
        $user_credits_sources = array_combine(array_values($user_credits_sources), array_fill(0, count($user_credits_sources), 0));

        $credits_sources = [];

        $user_credits_sources['prepaid_credits'] 	 = $this->details['available_credits']['prepaid_credits'] ?? [];
        $user_credits_sources['affiliate_credits'] = $this->details['available_credits']['affiliate_credits'] ?? [];
				
        if(!config('app.prepaid_credits.enabled'))
        {
        	unset($user_credits_sources['prepaid_credits']);	
        }

        if(!config('affiliate.enabled'))
        {
        	unset($user_credits_sources['affiliate_credits']);	
        }

        $transaction_amount = $transaction->amount;

				foreach($user_credits_sources as $credits_sources)
				{
					foreach($credits_sources as $src)
					{
						if(($src->credits - $transaction_amount) >= 0)
						{
							$src->credits -= $transaction_amount; 
							$transaction_amount = 0;
						}
						else
						{
							$transaction_amount -= $src->credits;
							$src->credits = 0;
						}

						if($src->type === 'affiliate')
						{
							$model = Affiliate_Earning::find($src->id);

							$model->commission_value = $src->credits;

							$model->save();
						}
						else
						{
							$model = User_Prepaid_Credit::find($src->id);

							$model->credits = $src->credits;

							$model->save();
						}

						if($transaction_amount == 0)
						{
							break 2;
						}
					}
				}
				      
        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }
		}

	}