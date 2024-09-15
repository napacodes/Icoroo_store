<?php

	namespace App\Libraries;

  use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use App\Models\Transaction;
  use Illuminate\Http\Request;

	class Offlinepayment 
	{
    public $name = "offlinepayment";
    public $return_url;
    public $cancel_url;
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details = [];
    public $error_msg;
    public static $response = "default";


		public function create_payment(array $params)
		{
			exists_or_abort(config("payments_gateways.{$this->name}"), __("Offline payment is not enabled"));

			$this->currency_code = config("payments.currency_code");
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			prepare_currency($this);

      extract($params);

      $this->details = [
      	"items" => [], 
	      "total_amount" => 0,
	      "discount" => 0,
	      "currency" => $this->currency_code, 
	      "exchange_rate" => $this->exchange_rate,
	      "reference_id" => uuid6(),
	      "custom_amount" => null,
        "reference" => generate_transaction_ref()
	    ];

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


      $total_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if(!$prepaid_credits_pack_id)
      {
        if($vat = config("payments.vat", 0))
        {
          $tax = ($total_amount * $vat) / 100;
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
      }

      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), $this->decimals, false);

      return $this;
		}



		public function getDetails()
		{
			return $this->details;
		}



    public function init_payment(array $config)
    {
      extract($config);

      $this->create_payment($params);
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

        $transaction->status = 'pending';
        $transaction->confirmed = 1;

        $transaction->save();
      
        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
    }
	}