<?php

	namespace App\Libraries;

  use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };

	class Adyen 
	{
    public $name = 'adyen';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["AED", "AUD", "BGN", "BHD", "BRL", "CAD", "CHF", "CNY", "CZK", "DKK", "EUR", "GBP", "HKD", "HRK", "HUF", "ISK", "ILS", "INR", "JOD", "JPY", "KRW", "KWD", "MYR", "NOK", "NZD", "OMR", "PLN", "QAR", "RON", "RUB", "SAR", "SEK", "SGD", "THB", "TWD", "USD", "ZAR"];
    public $supported_locales = ["zh-CN", "zh-TW", "da-DK", "nl-NL", "en-US", "fi-FI", "fr-FR", "de-DE", "it-IT", "ja-JP", "ko-KR", "no-NO", "pl-PL", "pt-BR", "ru-RU", "es-ES", "sv-SE"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;
    public $api_key;
    public $locale = 'en-US';
    public static $response = "default";
    public $version = "53";


    public function __construct(string $locale = null)
    {
      exists_or_abort(config('payments_gateways.adyen'), __(':payment_proc is not enabled', ['payment_proc' =>  $this->name]));

      $this->api_key  = config("payments_gateways.{$this->name}.api_key");
      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

      prepare_currency($this);

      if($locale && in_array(str_replace('_', '-', $locale), $this->supported_locales))
      {
        $this->locale = str_replace('_', '-', $locale);
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

      $this->return_url = route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
      $this->cancel_url = config('checkout_cancel_url');
    }



		public function create_payment_link(array $params)
		{
			/* DOC : https://docs.adyen.com/checkout/pay-by-link#create-a-payment-link
			--------------------------------------------------------------
        curl https://checkout-test.adyen.com/v68/paymentLinks \
        -H "x-API-key: YOUR_X-API-KEY" \
        -H "content-type: application/json" \
        -d '{
          "reference": "YOUR_PAYMENT_REFERENCE",
          "amount": {
            "value": 4200,
            "currency": "EUR"
          },
          "shopperReference": "UNIQUE_SHOPPER_ID_6728",
          "description": "Blue Bag - ModelM671",
          "countryCode": "NL",
          "merchantAccount": "YOUR_MERCHANT_ACCOUNT",
          "shopperLocale": "nl-NL"
        }'
      */

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
        if(config("pay_what_you_want.enabled") && config('pay_what_you_want.for.'.($subscription_id ? 'subscriptions': 'products')) && $custom_amount)
        {
          $total_amount = $custom_amount;

          $this->details['custom_amount'] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
        }

        if(($coupon->status ?? null) && !$custom_amount)
        {
          $total_amount -= $coupon->coupon->discount ?? 0;

          $this->details['items']['discount'] = ['name' => __('Discount'), 'value' => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];   
        }
      }


      $total_amount = (int)ceil(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));


      if(!$prepaid_credits_pack_id)
      {
        if(config('payments.vat', 0))
        {
          $value = (int)ceil(($total_amount * config('payments.vat', 0)) / 100);

          $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

          $total_amount += $value ?? 0;
        }

        if(config("payments_gateways.{$this->name}.fee", 0))
        {
          $value = (int)ceil(format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

          $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

          $total_amount += $value;
        }
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);


			$payload = [
				"reference" => $this->details['reference'],
        "amount" => [
          "value" => $total_amount,
          "currency" => $this->currency_code
        ],
        "returnUrl" => $this->return_url,
        //"shopperReference" => "SHOPPER_30",
        "merchantAccount" => config("payments_gateways.{$this->name}.merchant_account"),
        "shopperLocale" => $this->locale
      ];

      $ch      = curl_init();
      $api_url = "https://checkout-test.adyen.com/v{$this->version}/paymentLinks";

      if(config("payments_gateways.{$this->name}.mode") === 'live')
      {
        $api_url = "https://checkout.adyen.com/v{$this->version}/paymentLinks";
      }

      curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "x-API-key: {$this->api_key}"]);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);
      $error  = curl_error($ch);

      curl_close($ch);

			if(curl_errno($ch) || !json_decode($result))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				$this->error_msg = ['user_message' => $error_msg];

        return;
      }
      
      curl_close($ch);

      $result = json_decode($result);

      if(stripos(($result->status ?? null), '40') !== false)
      {
        $this->error_msg = ['user_message' => "{$result->errorCode} - {$result->message}"];

        return;
      }

			return $result;
		}



    public function verify_payment($transaction)
    {      
      $api_url = "https://checkout-test.adyen.com/v{$this->version}/paymentLinks/{$transaction->transaction_id}";

      if(config("payments_gateways.{$this->name}.mode") === 'live')
      {
        $api_url = "https://checkout.adyen.com/v{$this->version}/paymentLinks/{$transaction->transaction_id}";
      }

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_HTTPGET, 1);
      curl_setopt($ch, CURLOPT_URL, $api_url); 
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "x-API-key: {$this->api_key}"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      $result = curl_exec($ch);
      
      if(curl_errno($ch) || !json_decode($result))
      {
        $error_msg = curl_error($ch);

        curl_close($ch);

        $this->error_msg = ['user_message' => $error_msg];

        return;
      }
      
      curl_close($ch);

      $result = json_decode($result);

      if(isset($result->errorCode))
      {
        $this->error_msg = ['user_message' => "{$result->errorCode} - {$result->message}"];

        return;
      }

      return $result;
    }



    public function init_payment(array $config)
    {
      extract($config);

      $response = $this->create_payment_link($params);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $url = $response->url ?? abort(404);

      $params['transaction_details'] = $this->details;
      $params['reference_id']        = $response->reference;
      $params['payment_id']          = $response->id;

      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $params['reference_id'], now()->addDays(1)); 
      }
      else
      {
        Session::put('payment', $params['reference_id']);
      }

      Cache::put($params['reference_id'], $params, now()->addDays(1));

      return "{$url}?reference={$params['reference_id']}";
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
          $transaction->confirmed = $request->get('success');

          $transaction->save();
        }

        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
    }

    

    public function handle_webhook_notif(Request $request)
    {
      $response = ['status' => 0, 'transaction' => null, 'valid' => 0];

      if(stripos($request->processor, $this->name) !== false && $request->has(['notificationItems']))
      {
        $status['valid'] = 1;
        
        $notificationItems = $request->input('notificationItems');
        $notificationItems = array_shift($notificationItems) ?? [];

        if(($notificationItems['NotificationRequestItem']['success'] ?? null) === 'true')
        {
          $NotificationRequestItem = $notificationItems['NotificationRequestItem'];

          $pspReference         = $NotificationRequestItem['pspReference'];
          $originalReference    = $NotificationRequestItem['originalReference'] ?? '';
          $merchantAccountCode  = $NotificationRequestItem['merchantAccountCode'];
          $merchantReference    = $NotificationRequestItem['merchantReference'];
          $value                = $NotificationRequestItem['amount']['value'];
          $currency             = $NotificationRequestItem['amount']['currency'];
          $eventCode            = 'AUTHORISATION';
          $success              = $NotificationRequestItem['success'];

          $expected_hmacSignature = base64_encode(hash_hmac('sha256', "{$pspReference}:{$originalReference}:{$merchantAccountCode}:{$merchantReference}:{$value}:{$currency}:{$eventCode}:{$success}", pack("H*", config("payments.{$this->name}.hmac_key")), true));

          $adyen_hmacSignature = $NotificationRequestItem['additionalData']['hmacSignature'] ?? null;

          if($expected_hmacSignature == $adyen_hmacSignature)
          {
            $order_id = $NotificationRequestItem['merchantReference'];

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
              $transaction->transaction_id = $NotificationRequestItem['additionalData']['paymentLinkId'];

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