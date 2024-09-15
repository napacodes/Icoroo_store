<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use Iyzipay;
  use Iyzipay\Model\{ PaymentGroup, Currency, Locale, Buyer, Address, BasketItem, BasketItemType, CheckoutFormInitialize };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };


	class Iyzicolib
	{
    public $name = "iyzico";
    public $callback_url;
    public $cancel_url;
    public $supported_currencies = ["TRY", "USD", "EUR", "GBP", "RUB", "CHF", "NOK"];
    public $currency_code;
    public $exchange_rate = 1;
    public $default_currency;
    public $decimals;
    public $details  = [];
    public $error_msg;
    public static $response = "default";


    public function __construct()
    {
      exists_or_abort(config("payments_gateways.{$this->name}"), __(":payment_proc is not enabled", ["payment_proc" =>  "Iyzico"]));

      $this->currency_code = config("payments.currency_code");
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals");
      $this->default_currency = default_currency();

      prepare_currency($this);

      $this->details = [
        "items" => [],
        "total_amount" => 0,
        "currency" => $this->currency_code,
        "exchange_rate" => $this->exchange_rate,
        "custom_amount" => null,
        'reference' => generate_transaction_ref(),
        "transaction_id" => null,
        "order_id" => null,
      ];

      $this->callback_url = route("home.checkout.order_completed", ["processor" => $this->name, "order_id" => $this->details['reference']]);
      $this->cancel_url = config("checkout_cancel_url");
    }



    public function getOptions()
    {
      $options = new Iyzipay\Options();

      $options->setApiKey(config("payments_gateways.{$this->name}.client_id"));
      $options->setSecretKey(config("payments_gateways.{$this->name}.secret_id"));
      
      if(config("payments_gateways.{$this->name}.mode") === "sandbox")
      {
        $options->setBaseUrl("https://sandbox-api.iyzipay.com");
      }
      else
      {
        $options->setBaseUrl("https://api.iyzipay.com");
      }

      return $options;
    }




    public function exec_payment(array $params, object $buyerInf)
    {
      extract($params);

      $basketItems = [];

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


      $basketItems[] =  $this->basketItem([
                            "id"        => "PURCHASE",
                            "name"      => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
                            "category1" => "Default",
                            "itemType"  => BasketItemType::VIRTUAL,
                            "price"     => $total_amount,
                          ]);



      if(!$prepaid_credits_pack_id)
      {
        if(config("payments.vat", 0))
        {
          $tax = ($total_amount * config("payments.vat", 0)) / 100;
          $value = format_amount($tax, false, $this->decimals);

          $basketItems[] =  $this->basketItem([
                              "id"        => "TAX",
                              "name"      => __("VAT :percent%", ["percent" => $value]),
                              "category1" => __("Tax"),
                              "itemType"  => BasketItemType::VIRTUAL,
                              "price"     => $value,
                            ]);

          $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

          $total_amount += format_amount($tax ?? 0, false, $this->decimals);
        }


        if(config("payments_gateways.{$this->name}.fee", 0))
        {
          $value = format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals);

          $basketItems[] =  $this->basketItem([
                              "id"        => "FEE",
                              "name"      => __("Handling fee"),
                              "category1" => __("Fee"),
                              "itemType"  => BasketItemType::VIRTUAL,
                              "price"     => $value,
                            ]);

          $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

          $total_amount += $value;
        }
      }


      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      $total_amount = format_amount($total_amount, false, $this->decimals);


      $request = new Iyzipay\Request\CreateCheckoutFormInitializeRequest();
      $request->setLocale(Locale::EN);
      $request->setPrice($total_amount);
      $request->setPaidPrice($total_amount);
      $request->setCurrency($this->currency_code);
      $request->setPaymentGroup(PaymentGroup::PRODUCT);
      $request->setCallbackUrl($this->callback_url);
      $request->setEnabledInstallments([2, 3, 6, 9]);

      $buyer = new Buyer();
      $buyer->setId($buyerInf->email);
      $buyer->setName($buyerInf->firstname);
      $buyer->setSurname($buyerInf->lastname);
      $buyer->setEmail($buyerInf->email);
      $buyer->setIdentityNumber($buyerInf->id_number);
      $buyer->setRegistrationAddress($buyerInf->address);
      $buyer->setIp($buyerInf->ip_address ?? request()->ip());
      $buyer->setCity($buyerInf->city);
      $buyer->setCountry($buyerInf->country);

      $request->setBuyer($buyer);

      $billingAddress = new Address();
      $billingAddress->setContactName("{$buyerInf->firstname} {$buyerInf->lastname}");
      $billingAddress->setCity($buyerInf->city);
      $billingAddress->setAddress($buyerInf->address);
      $billingAddress->setCountry($buyerInf->country);

      $request->setBillingAddress($billingAddress);

      $request->setBasketItems($basketItems);

      $form = CheckoutFormInitialize::create($request, $this->getOptions());

      if($form->getErrorCode())
      {
        $this->error_msg = ["user_message" => $form->getErrorMessage()];
        
        return;
      }

      return $form;
    }





    public function validate_payment(string $token)
    {
      $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();

      $request->setLocale(Locale::EN);

      $request->setToken($token);

      $checkoutForm = Iyzipay\Model\CheckoutForm::retrieve($request, $this->getOptions());

      return strtoupper($checkoutForm->getPaymentStatus()) == "SUCCESS";
    }


    

    public function basketItem($attributes)
    {
      extract($attributes);

      $basketItem = new BasketItem();

      $basketItem->setId($id ?? null);
      $basketItem->setPrice($price ?? null);
      $basketItem->setName($name ?? null);
      $basketItem->setCategory1($category1 ?? null);
      $basketItem->setCategory2($category2 ?? null);
      $basketItem->setItemType($itemType ?? null);
      $basketItem->setSubMerchantKey($merchantKey ?? null);
      $basketItem->setSubMerchantPrice($MerchantPrice ?? null);

      return $basketItem;
    }



    public function getPaymentRequest($paymentId)
    {
      $request = new \Iyzipay\Request\RetrievePaymentRequest();

      $request->setLocale(Locale::EN);
      $request->setPaymentId($paymentId);

      return \Iyzipay\Model\Payment::retrieve($request, $this->getOptions());
    }



    public function init_payment(array $config)
    {
      extract($config);

      if(!$return_url && !$user)
      {
        $request->validate([
          'buyer.firstname' => 'string|required',
          'buyer.lastname'  => 'string|required',
          'buyer.id_number' => 'string|required',
          'buyer.city'      => 'string|required',
          'buyer.country'   => 'string|required',
          'buyer.address'   => 'string|required',
          'buyer.email'     => 'email|required'
        ]);
      }

      $buyer = $user ?? (object)$request->input('buyer');

      $buyer->ip_address = $request->ip();

      $response = $this->exec_payment($params, $buyer) ?? abort(404);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      if($response->getErrorCode())
      {
        return ['user_message' => $response->getErrorMessage()];
      }

      $paymentPageUrl = $response->getPaymentPageUrl() ?? abort(404);

      $this->details['transaction_id'] = $response->getToken();

      $params['transaction_details'] = $this->details;
      $params['transaction_id']      = $response->getToken();

      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $response->getToken(), now()->addDays(1)); 
      }

      Cache::put('iyzico-'.$response->getToken(), $response->getToken());

      Cache::put($response->getToken(), $params, now()->addDays(1));

      return $paymentPageUrl;
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

      if(stripos($request->processor, $this->name) !== false && $request->token !== null)
      {        
        $status['valid'] = 1;

        //$expected_sig = base64_encode(hash('sha1', config('payments.iyzico.secret_id') . $request->input('iyziEventType') . $request->input('iyziReferenceCode')));
        //$iyzico_sig   = $request->header('x-iyz-signature');

        if(strtolower($request->input('status')) === 'success')
        {
          $order_id = $request->token;

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

