<?php

namespace App\Libraries;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use net\authorize\api\constants\ANetEnvironment;
use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
use Illuminate\Http\Request;
use App\Models\{ Transaction };

class Authorizenet 
{
    public $name = 'authorizenet';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["USD", "CAD", "CHF", "DKK", "EUR", "GBP", "NOK", "PLN", "SEK", "AUD", "NZD"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;
    public static $response = "json";


		public function __construct()
		{
			if(!config("payments_gateways.{$this->name}.enabled"))
			{
				return response()->json(['user_message' => __(':payment_proc is not enabled', ['payment_proc' =>  'Authorizenet'])]);
			}
            
			$this->currency_code = config('payments.currency_code');
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
      $this->cancel_url = config('checkout_cancel_url');
		}



		public function create_order(array $params)
		{			
			extract($params);

			$total_amount = 0;

			$items = [];

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
      }

      if(!$prepaid_credits_pack_id && ($coupon->status ?? null) && !$custom_amount)
      {
        $total_amount -= $coupon->coupon->discount ?? 0;

        $this->details["items"]["discount"] = ["name" => __("Discount"), "value" => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
      }

      $total_amount = $unit_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);

			$breakdown = [];

      $items[] = [
      	'item_name_1' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
      	'amount_1' 	  => $unit_amount,
      	'quantity_1' 	  => 1
      ];


      if(!$prepaid_credits_pack_id && config("payments.vat", 0))
      {
        	$tax = ($unit_amount * config("payments.vat", 0)) / 100;
        	$value = format_amount($tax, false, $this->decimals);

        	$items[] = [
  	      	'item_name_2' => __('Tax'),
  	      	'amount_2' 	  => $value,
  	      	'quantity_2' 	  => 1
  	      ];

  	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

  	      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }

      if(!$prepaid_credits_pack_id && config("payments_gateways.{$this->name}.fee", 0))
      {
          $handling = config("payments_gateways.{$this->name}.fee", 0);
          $value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

          $items[] = [
            'item_name_3' => __('Fee'),
            'amount_3'    => $value,
            'quantity_3'    => 1
          ];

          $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

          $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
      $merchantAuthentication->setName(config("payments_gateways.{$this->name}.api_login_id"));
      $merchantAuthentication->setTransactionKey(config("payments_gateways.{$this->name}.transaction_key"));

      $api_url = config("payments_gateways.{$this->name}.mode") == 'sandbox' ? ANetEnvironment::SANDBOX : ANetEnvironment::PRODUCTION;

      $OpaqueDataType = new AnetAPI\OpaqueDataType();
      $OpaqueDataType->setDataDescriptor($dataDescriptor);
      $OpaqueDataType->setDataValue($dataValue);

      $paymentOne = new AnetAPI\PaymentType();

      $paymentOne->setOpaqueData($OpaqueDataType);
      

      $transactionRequestType = new AnetAPI\TransactionRequestType();
      $transactionRequestType->setTransactionType("authCaptureTransaction"); 
      
      $lineItem = new AnetAPI\LineItemType();
      $lineItem->setItemId(time());
      $lineItem->setName(__('Purchase_from_:app_name', ['app_name' => mb_ucfirst(config('app.name'))]));
      $lineItem->setQuantity(1);
      $lineItem->setUnitPrice($total_amount);
      $lineItem->setTotalAmount($total_amount);

      $transactionRequestType->addToLineItems($lineItem);

      $transactionRequestType->setAmount($total_amount);
      $transactionRequestType->setCurrencyCode($this->currency_code);
      $transactionRequestType->setPayment($paymentOne);

      $request = new AnetAPI\CreateTransactionRequest();
      $request->setMerchantAuthentication($merchantAuthentication);
      $request->setRefId(generate_transaction_ref());
      
      $request->setTransactionRequest($transactionRequestType);

      $controller = new AnetController\CreateTransactionController($request);

      return $controller->executeWithApiResponse($api_url)->getTransactionResponse();
		}



    public function init_payment(array $config)
    {
        extract($config);

        $validator = Validator::make($request->all(), [
          'messages.resultCode' => 'required|string|in:Ok',
          'encryptedCardData.cardNumber' => 'required|string',
          'encryptedCardData.expDate' => 'required|string',
          'encryptedCardData.bin' => 'required|string',
          'customerInformation.firstName' => 'required|string',
          'customerInformation.lastName' => 'required|string',
          'opaqueData.dataDescriptor' => 'required|string',
          'opaqueData.dataValue' => 'required|string',
        ]);

        if($validator->fails())
        {
          return ['user_message' => implode(',', $validator->errors()->all())];
        }

        $params['dataValue']       = $request->input('opaqueData.dataValue');
        $params['dataDescriptor']  = $request->input('opaqueData.dataDescriptor');
        $params['firstName']       = $request->input('customerInformation.firstName');
        $params['lastname']        = $request->input('customerInformation.lastname');

        $authorize_net = new Authorizenet;

        if($authorize_net->error_msg)
        {
          return $authorize_net->error_msg;
        }

        $response = $authorize_net->create_order($params);

        dd($response);

        if($authorize_net->error_msg)
        {
          return $authorize_net->error_msg;
        }

        if($response->getResponseCode() != 1)
        {
          $errors = $response->getErrors();

          foreach($errors as &$error)
          {
            $error = $error->getErrorText();
          }

          return ['user_message' => json_encode($errors)];
        }

        $params['transaction_details'] = $authorize_net->details;
        /*$params['transaction_id']      = $response->getTransId();
        //$params['reference_id']        = $response->getRefTransID();
        $params['reference_id']        = $this->details['reference'];*/

        $params['transaction_id']      = $response->getTransId();
        $params['reference_id']        = $response->getRefTransID();

        dd($params, $response);

        Session::put('payment', $params['transaction_id']);

        Cache::put($params['transaction_id'], $params, now()->addDays(1));

        return ['status' => true, 'redirect_url' => "{$this->return_url}&order_id={$params['reference_id']}"];
    }



    public function complete_payment(Request $request)
    {
      if(stripos($request->get('processor'), $this->name) !== false && $request->get('order_id') !== null)
      {
        $transaction_id = $request->get('order_id');

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
        $transaction->save();
      
        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
    }
}





















