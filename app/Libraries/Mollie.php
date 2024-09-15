<?php

	namespace App\Libraries;

  use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };
  use GuzzleHttp\{ Client };

	class Mollie 
	{		
		public $name = 'mollie';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["EUR", "GBP", "DKK", "NOK", "PLN", "SEK", "CHF", "USD"];
    public $supported_locales = ["en_US", "en_GB", "nl_NL", "nl_BE", "fr_FR", "fr_BE", "de_DE", "de_AT", "de_CH", "es_ES", "ca_ES", "pt_PT", "it_IT", "nb_NO", "sv_SE", "fi_FI", "da_DK", "is_IS", "hu_HU", "pl_PL", "lv_LV", "lt_LT"];
    public $payment_methods = [
			"applepay",
			"bancontact",
			"banktransfer",
			"belfius",
			"creditcard",
			"directdebit",
			"eps",
			"giftcard",
			"giropay",
			"ideal",
			"kbc",
			"mybank",
			"paypal",
			"paysafecard",
			"przelewy24",
			"sofort",
    ];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;
    public $api_key;
    public $locale = 'en_US';
    public static $response = "default";


    public function __construct(string $locale = null)
    {
      exists_or_abort(config("payments_gateways.{$this->name}"), __(':payment_proc is not enabled', ['payment_proc' =>  $this->name]));

      $this->api_key  = config("payments_gateways.{$this->name}.api_key");
      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

      if($payment_methods = array_filter(explode(',', config("payments_gateways.{$this->name}.method"))))
      {
      	$this->payment_methods = $payment_methods;
      }

      prepare_currency($this);

      if($locale && in_array($locale, $this->supported_locales))
      {
        $this->locale = $locale;
      }

      $this->details = [
        'items' => [],
        'total_amount' => 0,
        'currency' => $this->currency_code,
        'exchange_rate' => $this->exchange_rate,
        'custom_amount' => null,
        'reference' => generate_transaction_ref(),
        'transaction_id' => null,
        'order_id' => null,
      ];

      $this->return_url  = route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
      $this->cancel_url  = config('checkout_cancel_url');
      $this->webhook_url = route('home.checkout.webhook', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
    }



		public function create_payment_link(array $params)
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
      	if(config("payments.vat", 0))
      	{
	      	$tax = ($total_amount * config("payments.vat", 0)) / 100;
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


      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $payload = [
      	"amount" => [
      		"currency" => $this->currency_code,
      		"value" => $total_amount,
      	],
      	"description" =>  __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
      	"redirectUrl" => $this->return_url,
      	"cancelUrl" => $this->cancel_url,
      	"webhookUrl" => $this->webhook_url,
      	"locale" => $this->locale,
      	"method" => $this->payment_methods,
      ];

      $payload = array_filter($payload);

      $client = new Client([
      	"verify" => false,
      	"http_errors" => false,
      	"headers" => [
      		"Authorization" => "Bearer {$this->api_key}"
      	]
      ]);

      $response = $client->request("POST", "https://api.mollie.com/v2/payments", ["json" => $payload]);

      if(stripos($response->getStatusCode(), '20') === false)
      {
      		$this->error_msg = ['user_message' => (string)$response->getBody()];

        	return;
      }

      $response = json_decode((string)$response->getBody(), true);

      return $response;
		}



    public function init_payment(array $config)
    {
      extract($config);

      $response = $this->create_payment_link($params);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $url = $response['_links']['checkout']['href'] ?? abort(404);

      $params['transaction_details'] = $this->details;
      $params['reference_id']        = $response['id'];
      $params['payment_id']          = $response['id'];

      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $params['reference_id'], now()->addDays(1)); 
      }
      else
      {
        Session::put('payment', $params['reference_id']);
      }

      Cache::put($params['reference_id'], $params, now()->addDays(1));

      return $url;
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

    
    public function verify_payment($transaction)
    {      
      $api_url = "https://api.mollie.com/v2/payments/{$transaction->transaction_id}";

      $client = new Client([
      	"verify" => false,
      	"http_errors" => false,
      	"headers" => [
      		"Authorization" => "Bearer {$this->api_key}"
      	]
      ]);

      $response = $client->request("GET", $api_url);

      if(stripos($response->getStatusCode(), '20') === false)
      {
      		$this->error_msg = ['user_message' => (string)$response->getBody()];

        	return;
      }

      $response = json_decode((string)$response->getBody(), true);

      $status = strtolower($response['status'] ?? '');

      if($status === "paid")
      {
      	return ['status' => true, 'response' => $response];
      }

     	return ['status' => false];
    }


    public function handle_webhook_notif(Request $request)
    {
      $response = ['status' => 0, 'transaction' => null, 'valid' => 0];

      if(stripos($request->processor, $this->name) !== false && $request->post('id') && $request->query('order_id'))
      {
        $status['valid'] = 1;
        
        $order_id = $request->query('order_id');

        $transaction =  Transaction::where(function($query) use($order_id)
                        {
                          $query->where('order_id', $order_id)
                                ->orWhere('transaction_id', $order_id)
                                ->orWhere('reference_id', $order_id);
                        })
                        ->where(['processor' => $this->name, 'status' => 'pending'])
                        ->first() ?? abort(404);

        $response = $this->verify_payment($transaction);

        if($this->error_msg)
        { 
	        	return $response;
        }

        if($transaction)
        {
          $transaction->status = $response['status'] === true ? 'paid' : 'pending';
          $transaction->transaction_id = $request->post('id');

          $transaction->save();

          $response['status'] = 1;
          $response['transaction'] = $transaction;
        }
      }

      return $response;
    }
  }