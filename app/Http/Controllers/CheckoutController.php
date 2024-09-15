<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\{ Stripe, Paypal, Skrill, Razorpay, Iyzicolib, Coingate, Midtrans, Paymentwall, Authorizenet,
                    Paystack, Adyen, Instamojo, Offlinepayment, Payhere, Coinpayments, Spankpay, Omise, Sslcommerz, Flutterwave };
use App\Models\{ Transaction, Coupon, Product, Skrill_Transaction, Pricing_Table, User_Subscription, Key, Affiliate_Earning, 
                  Transaction_Note, User, Prepaid_Credit, User_Prepaid_Credit, License };
use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
use Ramsey\Uuid;


class CheckoutController extends Controller
{
    public $transaction_details = [];
    public $transaction_params = [];
    public $payment_link = null;
    public $pending_transaction;



    public function payment(Request $request, $return_url = false, $user = null)
    {
      $processor   = strtolower($request->processor);
      $tos         = filter_var($request->post('tos'), FILTER_VALIDATE_BOOLEAN);
      $user_notes  = filter_var($request->post('notes'), FILTER_SANITIZE_STRING);
      $guest_email = $request->post('guest_email');
      $guest_token = ($user ?? Auth::check()) ? null : uuid6();

      if(config('payments.tos') && !$tos)
      {
        $message = ['user_message' => __('Please agree to our terms and conditions.')];

        if(config("payment_gateways.{$processor}.async"))
        {
          return json($message);
        }

        return back()->withInput()->with($message);
      }

      foreach(config("payment_gateways.{$processor}.form.inputs", []) as $key => $input)
      {
        if(isset($input['attributes']) && count($input['attributes']))
        {          
          $result = [];

          parse_str($key, $arr);

          array_keys_recursive($arr, $result);
  
          $input_name = implode('.', $result);

          if(in_array('required', $input['attributes']) && !$request->input($input_name))
          {
            return back()->withInput()->with(['user_message' => __(":name field is required", ['name' => __($input['label'])])]);
          }
        }
      }

      if(!\Auth::check() && !filter_var($guest_email, FILTER_VALIDATE_EMAIL))
      {
        $message = ['user_message' => __('Wrong or missing guest email address.')];

        if(config("payment_gateways.{$processor}.async"))
        {
          return json($message);
        }

        return back()->withInput()->with($message);
      }

      $invalid_user_notes = Validator::make($request->all(), ['notes' => "nullable|string|max:5000"])->fails();

      if(config('payments.buyer_note') && !$request->subscription_id && $invalid_user_notes)
      {
        $message = ['user_message' => __('There is something wrong with the notes field.')];

        if(config("payment_gateways.{$processor}.async"))
        {
          return json($message);
        }

        return back()->with($message); 
      }

      $minimum_custom_amount = config("payments_gateways.{$processor}.minimum", 0);

      $validator =  Validator::make($request->all(), [
                        'custom_amount' => "nullable|numeric|digits_between:0,25|gte:{$minimum_custom_amount}",
                    ]);

      if($validator->fails())
      {
        return back()->with(['user_message' => implode(',', $validator->errors()->all())]);
      }


      if($request->subscription_id)
      {
        $subscription = Pricing_Table::find($request->subscription_id) ?? abort(404);

        config(['checkout_cancel_url' => route('home.checkout', ['id' => $subscription->id, 'slug' => $subscription->slug, 'type' => 'subscription'])]);

        $cart = [(object)[
                  'id'        => $subscription->id,
                  'quantity'  => 1,
                  'name'      =>   $subscription->name,
                  'category'  => __('Subscription'),
                  'price'     => $subscription->price,
                ]];

        $request->merge(['products' => json_encode($cart)]);
      }
      elseif($request->prepaid_credits_pack_id)
      {
        $prepaid_credits = Prepaid_Credit::find($request->prepaid_credits_pack_id) ?? abort(404);

        config(['checkout_cancel_url' => route('home.add_prepaid_credits')]);

        $cart = [(object)[
                  'id'        => $prepaid_credits->id,
                  'quantity'  => 1,
                  'name'      =>   $prepaid_credits->name,
                  'category'  => __('Prepaid credits'),
                  'price'     => $prepaid_credits->amount,
                ]];

        $request->merge(['products' => json_encode($cart)]);
      }
      else
      {
        config(['checkout_cancel_url' => route('home.checkout')]);

        if(!$cart = $this->validate_request($request))
        {
          return back();
        }

        $licenses_ids = array_column($cart, 'license_id');

        $request->merge(['products' => json_encode($cart)]);
      }


      $products_ids = array_column($cart, 'id');

      $coupon = $this->validate_coupon($request, $request->subscription_id ? 'subscription' : 'products', false, $user)->getData();

      if($this->cart_has_only_free_items($cart, $coupon, $processor, $request->custom_amount, $request->subscription_id ? 'subscriptions' : 'products'))
      {        
        $transaction_details = [];

        $items = array_reduce($cart, function($ac, $item)
        {
          $ac[] = [
                    'name' => $item->name,
                    'value' => $item->price
                  ];

          return $ac; 
        }, []);

        $items = array_merge($items, ['fee' => [
                                        'name' => __('Handling fee'),
                                        'value' => price(0, false, true, 2, null)
                                      ],
                                      'tax' => [
                                        'name' => __('Tax'),
                                        'value' => price(0, false, true, 2, null)
                                      ],
                                      'discount' => [
                                        'name' => __('Discount'),
                                        'value' => price($coupon->coupon->discount ?? 0, false, true, 2, null)
                                      ]]);

        $transaction_details['exchange_rate'] = config('payments.exchange_rate');
        $transaction_details['items']         = $items;
        $transaction_details['total_amount']  = format_amount(0, true);
        $transaction_details['custom_amount'] = $request->custom_amount;
        $transaction_details['currency']      = session('currency', config('payments.currency_code'));
        $transaction_details['guest_email']   = $guest_email;
        $transaction_details['guest_token']   = $guest_token;

        $this->transaction_details = $transaction_details;

        return $this->proceed_free_purchase($cart, $processor, $coupon, $transaction_details, $subscription ?? null);
      }

      $params = [
        'processor'         => $processor,
        'cart'              => $cart,
        'subscription_id'   => null,
        'subscription_name' => null,
        'prepaid_credits_pack_id' => null,
        'subscription_days' => null,
        'subscription_reccurent' => false, 
        'coupon'            => $coupon,
        'products_ids'      => null,
        'licenses_ids'      => null,
        'buyer'             => $request->input('buyer'),
        'user'              => $request->user(),
        'custom_amount'     => $request->custom_amount,
        'user_email'        => $user->email ?? (Auth::check() ? $request->user()->email : $request->input('buyer.email')),
        'user_id'           => $user->id ?? (Auth::check() ? Auth::id() : null),
        'fee'               => config("payments.{$processor}.fee"),
        'guest_token'       => $guest_token,
        'guest_email'       => $guest_email,
        'user_notes'        => $user_notes,
      ];

      if($request->subscription_id)
      {
        $params = array_merge($params, [
          'subscription_name'      => $subscription->name,
          'subscription_id'        => $subscription->id,
          'subscription_days'      => $subscription->days,
          'products_ids'           => $subscription->id,
          'subscription_reccurent' => $subscription->recurrent
        ]);
      }
      elseif($request->prepaid_credits_pack_id)
      {
        $params['prepaid_credits_pack_id'] = $request->prepaid_credits_pack_id;
      }
      else
      {
        $params = array_merge($params, [
          'products_ids' => $products_ids,
          'licenses_ids' => $licenses_ids,
        ]);
      }


      $this->transaction_params = $params;

      $payment_config = [
        'return_url' => $return_url,
        'user'       => $user,
        'request'    => $request,
        'params'     => $params
      ];


      if(config("payments_gateways.{$processor}"))
      {
        $payment_class = config("payment_gateways.{$processor}.class");
        $payment_class = "\App\Libraries\\$payment_class";
        $payment_class = new $payment_class;

        $response = $payment_class->init_payment($payment_config);

        if($payment_class->error_msg ?? null)
        {
          if($return_url)
          {
            return $payment_class::$response === 'json' ? response()->json($payment_class->error_msg) : $payment_class->error_msg;
          }
          else
          {
            return $payment_class::$response === 'json' ? response()->json($payment_class->error_msg) : back()->with($payment_class->error_msg);
          }
        }

        $details = array_merge($payment_class->details, $params);

        $products_ids = $details['subscription_id']
                        ? wrap_str($details['subscription_id'])
                        : ($details['prepaid_credits_pack_id']
                          ? wrap_str($details['prepaid_credits_pack_id'])
                          : implode(',', array_map('wrap_str', array_column(obj2arr($details['cart']), 'id'))));
        
        $products_ids = explode(',', str_replace("'", '', $products_ids));
        $licenses     = null;

        if(!$details['prepaid_credits_pack_id'] && !$details['subscription_id'])
        {
          $licensed_products_ids = Product::useIndex('primary', 'enable_license')->select('id')
                                   ->whereIn('id', $products_ids)->where('enable_license', 1)
                                   ->get()->pluck('id')->toArray();

          if($licensed_products_ids)
          {
            $licenses = [];

            foreach(array_intersect($products_ids, $licensed_products_ids) as $licensed_product_id)
            {
              $licenses[$licensed_product_id] = uuid6();
            }

            $licenses = json_encode($licenses);
          }
        }

        $transaction = new Transaction;

        $transaction->processor    = $details['processor'];
        $transaction->products_ids = implode(',', array_map('wrap_str', $products_ids));
        $transaction->user_id      = $details['user_id'];
        $transaction->coupon_id    = $details['coupon']->status ? $details['coupon']->coupon->id : null;
        $transaction->reference_id = $details['reference'] ?? null;
        $transaction->order_id     = $details['order_id'] ?? null;
        $transaction->transaction_id = $details['transaction_id'] ?? null;
        $transaction->amount         = $details['total_amount'];
        $transaction->discount       = $details['coupon']->coupon->discount ?? 0;
        $transaction->refunded       = 0;
        $transaction->refund         = 0;
        $transaction->items_count    = $details['subscription_id'] ? 1 : count($details['cart']);
        $transaction->is_subscription = $details['subscription_id'] ? 1 : 0;
        $transaction->guest_token     = Auth::check() ? null : uuid6();
        $transaction->guest_email     = $guest_email;
        $transaction->status          = "pending";
        $transaction->confirmed       = preg_match("/^offlinepayment|credits$/i", $processor) ? 0 : 1;
        $transaction->exchange_rate   = $details['exchange_rate'];
        $transaction->details         = json_encode($details);
        $transaction->licenses        = $licenses;
        $transaction->licenses_ids =  ($details['subscription_id'] || $details['prepaid_credits_pack_id']) 
                                      ? null 
                                      : implode(',', array_map('wrap_str', $details['licenses_ids']));
        $transaction->custom_amount = $details['custom_amount'];
        $transaction->payment_url   = urldecode(Session::pull('short_link'));
        $transaction->read_by_admin = 0;
        $transaction->referrer_id   = config('referrer_id');
        $transaction->type          = $details['subscription_id'] ? 'subscription' : ($details['prepaid_credits_pack_id'] ? 'credits' : 'product');
        $transaction->sandbox       = config("payments_gateways.{$processor}.mode") == 'sandbox' ? 1 : 0;

        
        if(($details['currency'] != config('payments.currency_code')) && $details['exchange_rate'] != 1)
        {
          $transaction->amount = format_amount($details['total_amount'] / $details['exchange_rate'], true);
          $transaction->custom_amount = format_amount($details['custom_amount'] / $details['exchange_rate'], true);
        }

        if($details['subscription_id'])
        {
          // For Subscription transaction
          $subscription = Pricing_Table::find($details['subscription_id']) ?? abort(404);

          DB::transaction(function() use($transaction, $subscription)
          {
            $transaction->save();

            User_Subscription::insert([
              'user_id'         => Auth::id(),
              'subscription_id' => $subscription->id,
              'transaction_id'  => $transaction->id,
              'ends_at'         => is_numeric($subscription->days) && $subscription->days > 0
                                   ? date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " + {$subscription->days} days"))
                                   : null,
              'daily_downloads' => 0,
              'daily_downloads_date' => $subscription->limit_downloads_per_day ? date('Y-m-d') : null
            ]);
          });
        }

        if($details['prepaid_credits_pack_id'])
        {
          // For Prepaid credits transaction
          $prepaid_credits = Prepaid_Credit::find($details['prepaid_credits_pack_id']) ?? abort(404);

          DB::transaction(function() use($transaction, $prepaid_credits)
          {
            $transaction->save();

            User_Prepaid_Credit::insert([
              'user_id'            => Auth::id(),
              'prepaid_credits_id' => $prepaid_credits->id,
              'transaction_id'     => $transaction->id,
              'credits'            => $prepaid_credits->amount,
            ]);
          });
        }
        else
        {
          // For Products transaction
          $transaction->save();
        }

        if($transaction->coupon_id && $transaction->user_id)
        {
          DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", 
            ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$details['coupon']->coupon->code]);
        }

        $this->update_keys($products_ids, $transaction);

        if($user_notes)
        {
          $transaction_note = new Transaction_Note;
          
          $transaction_note->user_id        = $transaction->user_id;
          $transaction_note->transaction_id = $transaction->id;
          $transaction_note->content        = $user_notes;

          $transaction_note->save();
        }

        if($transaction->processor === "offlinepayment")
        {
          Session::flash('transaction_status', 'success');
          Session::flash('transaction', $transaction);

          $offline_request = new Request([], ['offlinepayment' => true]);

          return $this->success($offline_request);
        }
        else
        {
          if($return_url)
          {
            return $payment_class::$response === 'json' ? response()->json($response) : $response;
          }
          else
          {
            if(is_array($response))
            {
              return $payment_class::$response === 'json' ? response()->json($response) : back()->with($response);
            }
            else
            {
              if($payment_class::$response === 'json')
              {
                return $request->prepare ? response()->json($response) : redirect()->away($response);
              }
              else
              {
                return redirect()->away($response);
              }
            }
          }
        }
      }
    }


    private function validate_request(Request $request)
    {            
      $cart   = json_decode(base64_decode($request->cart));
      $ids    = array_column($cart, 'id');

      $i = [];

      foreach($cart as $k => &$item)
      {
          $product = Product::useIndex('primary, active')
                      ->selectRaw('products.id, products.name, products.stock, products.slug, products.cover, categories.name as category_name, 
                        (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id AND key_s.user_id IS NULL) as `remaining_keys`,
                        (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id) as has_keys,
                        group_buy_price, group_buy_min_buyers, group_buy_expiry, 
                        licenses.id as license_id, licenses.name as license_name, products.minimum_price,
                              CASE
                                WHEN product_price.`promo_price` IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10)))
                                THEN product_price.promo_price
                                ELSE
                                NULL
                              END AS `promotional_price`,
                              IF(product_price.`promo_price` IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10), promotional_price_time, null) AS promotional_price_time,
                              product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) AS free_item,
                              IF(product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) = 1, 0, product_price.price) AS price')
                     ->join('categories', 'categories.id', '=', 'products.category')
                     ->join('licenses', 'licenses.id', '=', DB::raw($item->license_id ?? null))
                     ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')
                                 ->on('product_price.product_id', '=', 'products.id');
                          })
                     ->where(['products.active' => 1, 'products.id' => $item->id, 'products.for_subscriptions' => 0])
                     ->first() ?? abort(404);
                     $i[] = $product;

          if(out_of_stock($product))
          {
            unset($cart[$k]);
            continue;
          }

          unset($item->url, $item->thumbnail, $item->screenshots);

          if($product->minimum_price && ($item->custom_price ?? null))
          {
            $product->price = ($item->custom_price >= $product->minimum_price) ? $item->custom_price : $product->minimum_price;
          }
          else
          {
            $product->price = $product->promotional_price ? $product->promotional_price : $product->price;
          }

          $item->price       = $product->promotional_price ? $product->promotional_price : $product->price;
          $item->promo_price = $product->promotional_price;
          $item->category    = $product->category_name;
          $item->name        = $product->name;
          $item->cover       = $product->cover;
          $item->slug        = $product->slug;
          $item->free        = $item->price == 0;

          if(productHasGroupBuy($item, false))
          {
            $item->price = $product->group_buy_price;
          }
      }

      return $cart;
    }



    public function validate_coupon(Request $request, $for = null, $async = true, $user = null)
    {
      $discount = 0;

      $regular_license = config('licenses')->where('regular', 1)->first();

      if(!$coupon = $request->post('coupon'))
      {
        return response()->json(['status' => false, 'msg' => __('Invalid coupon')]);
      }

      $user_id = $user->id ?? Auth::id();

      if(!$coupon = Coupon::where('code', $coupon)->get()->first())
      {
        return response()->json(['status' => false, 'msg' => __('Coupon unavailable')]);
      }

      if($request->post('for', $for) === 'products')
      {
          if($coupon->for !== 'products')
          {
            return response()->json(['status' => false, 'msg' => __('Invalid coupon')]);
          }


          if(!$products = is_iterable($request->products) ? $request->products : json_decode($request->products, true))
          {
            return response()->json(['status' => false, 'msg' => __('Missing/Invalid parameter')]);
          }


          if($coupon->products_ids)
          {
            $products_ids = array_column($products, 'id');

            foreach($products_ids as $product_id)
            {
              if(!is_numeric($product_id))
              {
                return response()->json(['status' => false, 'msg' => __('Misformatted request')]);
              }
            }

            $_coupon_products_ids = array_filter(explode(',', str_ireplace("'", "", $coupon->products_ids)));

            if(!array_intersect($_coupon_products_ids, $products_ids))
            {
              return response()->json(['status' => false, 'msg' => __('This coupon is not for the selected product(s)')]);
            }

            foreach($products as $k => $product)
            {
              settype($product, 'object');

              $regular_license_only = $coupon->regular_license_only ? ($product->license_id == $regular_license->id) : true;

              if(in_array($product->id, $_coupon_products_ids) && $regular_license_only)
              {
                if($coupon->is_percentage)
                {
                  $coupon_value = $product->price * $coupon->value / 100;
                }
                else
                {
                  $coupon_value = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
                }

                $discount += $coupon_value > $product->price ? $product->price : $coupon_value;
              }
            }
          }
          else 
          {
            if(!$coupon->regular_license_only)
            {
              $total_amount = array_sum(array_column($products, 'price'));

              if($coupon->is_percentage)
              {
                $discount = $total_amount * $coupon->value / 100;
              }
              else
              {
                $discount = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
              }

              $discount = $discount > $total_amount ? $total_amount : $discount;
            }
            else
            {
              $total_amount = 0;

              foreach($products as $product)
              {
                if($product->license_id == $regular_license->id)
                {
                  $total_amount += $product['price'];
                }
              }

              if($coupon->is_percentage)
              {
                $discount = $total_amount * $coupon->value / 100;
              }
              else
              {
                $discount = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
              }

              $discount = $discount > $total_amount ? $total_amount : $discount;
            }
          }
      }
      elseif($request->post('for', $for) === 'subscription')
      {
        if($coupon->for !== 'subscriptions')
        {
          return response()->json(['status' => false, 'msg' => __('Invalid coupon')]);
        }

        if(!$subscription_id = $request->subscription_id)
        {
          return response()->json(['status' => false, 'msg' => __('Missing/Invalid parameter (subscription_id).')]);
        }

        if(!$subscription = Pricing_Table::find($subscription_id))
        {
          return response()->json(['status' => false, 'msg' => __('Subscription currently not available')]);
        }

        if($coupon->subscriptions_ids)
        {
          if(!is_numeric($subscription_id))
          {
            return response()->json(['status' => false, 'msg' => __('Misformatted request')]);
          }

          if(!in_array($subscription_id, array_filter(explode(',', $coupon->subscriptions_ids))))
          {
            return response()->json(['status' => false, 'msg' => __('This coupon is not for the selected subscription.')]);
          }
        }

        if($coupon->is_percentage)
        {
          $discount = $subscription->price * $coupon->value / 100;
        }
        else
        {
          $discount = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
        }

        $discount = $discount > $subscription->price ? $subscription->price : $discount;
      }
      else
      {
        return;
      }
      
      if($coupon->expires_at < date('Y-m-d H:i:s'))
      {
        return response()->json(['status' => false, 'msg' => __('Coupon expired')]);
      }


      if($coupon->starts_at >= date('Y-m-d H:i:s'))
      {
        return response()->json(['status' => false, 'msg' => __('Coupon not available yet')]);
      }


      if($coupon->users_ids && $user_id)
      {
        if(!in_array($user_id, array_filter(explode(',', str_replace("'", '', $coupon->users_ids)))))
        {
          return response()->json(['status' => false, 'msg' => __('You are not allowed to use this coupon')]);
        }
      }

      if($coupon->used_by && $coupon->once && $user_id)
      {
        if(in_array($user_id, array_filter(explode(',', str_replace("'", '', $coupon->used_by)))))
        {
          return response()->json(['status' => false, 'msg' => __('Coupon already used')]);
        }
      }

      return response()->json([
                          'status' => true, 
                          'msg'    => 'Coupon applied',
                          'coupon' => [
                                       'value' => $coupon->value,
                                       'is_percentage' => $coupon->is_percentage ? true : false,
                                       'expires_at' => $coupon->expires_at,
                                       'code' => $coupon->code,
                                       'discount' => $discount ?? 0,
                                       'id' => $coupon->id
                                     ]
                        ]);
    }



    private function cart_has_only_free_items($cart, $coupon, $processor, $custom_amount, $for = 'products')
    {
      $total_amount = array_reduce($cart, function($ac, $item)
                      {
                        $ac += (float)$item->price;

                        return $ac;
                      }, 0);

      $pay_what_you_want = config('pay_what_you_want.enabled') && config("pay_what_you_want.for.{$for}");

      if($pay_what_you_want && (config("payments.{$processor}.minimum") === '0') && $custom_amount === '0')
      {
        return true;
      }

      if(!$total_amount)
      {
        return true;
      }

      if($coupon->status)
      {
        return $coupon->coupon->discount >= $total_amount;
      }
    }



    private function proceed_free_purchase($cart, $processor, $coupon, $transaction_details, $subscription = null, $async = false)
    {
        $transaction = new Transaction;

        $transaction->amount            = 0;
        $transaction->user_id           = Auth::check() ? Auth::id() : null;
        $transaction->processor         = $processor;
        $transaction->guest_token       = $transaction_details['guest_token'] ?? null;
        $transaction->guest_email       = $transaction_details['guest_email'] ?? null;
        $transaction->updated_at        = date('Y-m-d H:i:s');
        $transaction->products_ids      = $subscription
                                          ? wrap_str($subscription->id)
                                          : implode(',', array_map('wrap_str', array_column($cart, 'id')));
        $transaction->licenses_ids      = $subscription
                                          ? null
                                          : implode(',', array_map('wrap_str', array_column($cart, 'license_id')));
        $transaction->is_subscription   = $subscription ? 1 : 0;
        $transaction->items_count       = $subscription ? 1 : count($cart);
        $transaction->details           = json_encode($transaction_details, JSON_UNESCAPED_UNICODE);
        $transaction->amount            = $transaction_details['total_amount'];
        $transaction->discount          = $coupon->coupon->discount ?? 0;
        $transaction->exchange_rate     = $transaction_details['exchange_rate'] ?? null;
        $transaction->reference_id      = generate_transaction_ref();
        $transaction->coupon_id         = $coupon->coupon->id ?? null;
        $transaction->referrer_id       = config('referrer_id');
        $transaction->status            = 'paid';
        $transaction->type              = $subscription ? 'subscription' : 'product';
        $transaction->sandbox           = config("payments_gateways.{$processor}.mode") == 'sandbox' ? 1 : 0;


        if(!$subscription)
        {
          $products_ids = array_column($cart, 'id');
          $licenses = null;
          $licensed_products_ids = Product::useIndex('primary', 'enable_license')->select('id')
                                   ->whereIn('id', $products_ids)->where('enable_license', 1)
                                   ->get()->pluck('id')->toArray();

          if($licensed_products_ids)
          {
            $licenses = [];

            foreach(array_intersect($products_ids, $licensed_products_ids) as $licensed_product_id)
            {
              $licenses[$licensed_product_id] = uuid6();
            }

            $transaction->licenses = json_encode($licenses);
          }

          $this->update_keys($products_ids, $transaction);
        }
        
        if(isset($subscription))
        {
          DB::transaction(function() use($transaction, $subscription, $coupon)
          {
            $transaction->save();

            User_Subscription::insert([
              'user_id'         => Auth::id(),
              'subscription_id' => $subscription->id,
              'transaction_id'  => $transaction->id,
              'ends_at'         => is_numeric($subscription->days) && $subscription->days > 0
                                   ? date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " + {$subscription->days} days"))
                                   : null,
              'daily_downloads' => 0,
              'daily_downloads_date' => $subscription->limit_downloads_per_day ? date('Y-m-d') : null
            ]);
          });

          if($coupon->status)
          {
            DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$coupon->coupon->code]);
          }
        }
        else
        {
          DB::transaction(function() use($transaction, $coupon)
          {
            $transaction->save();

            if($coupon->status && $transaction->user_id)
            {
              DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$coupon->coupon->code]);
            }
          });
        }

        if($transaction->type == 'product')
        {
          $this->order_download_links($transaction, 1);
        }

        $fash_data = [
          "transaction" => $transaction,
          "transaction_status" => "success",
          "transaction_response" => "done"
        ];

        if($async)
        {
          foreach($fash_data as $key => $value)
          {
            Session::flash($key, $value);
          }

          return response()->json(['status' => true, 'redirect' => route('home.checkout.success')]);
        }

        return redirect()->route('home.checkout.success')->with($fash_data);
    }



    public function success(Request $request)
    {
        if(session('transaction_status') !== 'success')
        {
          return redirect()->route('home');
        }

        config([
          "meta_data.name"        => config('app.name'),
          "meta_data.title"       => __('Transaction completed'),
          "meta_data.description" => config('app.description'),
          "meta_data.url"         => url()->current(),
          "meta_data.fb_app_id"   => config('app.fb_app_id'),
          "meta_data.image"       => asset('storage/images/'.(config('app.cover') ?? 'cover.jpg'))
        ]);

        $transaction = Session::pull('transaction');

        $transaction->details = json_decode($transaction->details);

        if($transaction->user_id)
        {
          $transaction->setAttribute('user_email', User::find($transaction->user_id)->email);
        }

        if($request->post('offlinepayment') === true)
        {
          return view_('checkout.offline', ['transaction' => $transaction]);          
        }

        $items = $this->order_download_links($transaction, 0);

        return view_('checkout.success', ['transaction' => $transaction, 'items' => $items]);
    }



    // WEBHOOK
    public function webhook(Request $request)
    {
      $success     = 0;
      $processor   = mb_strtolower($request->processor);

      $class = config("payment_gateways.{$processor}.class") ?? abort(404);

      $class = "App\Libraries\\$class";
      
      if(!class_exists($class))
      {
        http_response_code($success ? 200 : 400);
        exit;
      }

      $class = new $class;

      if(method_exists($class, "handle_webhook_notif"))
      {
        $response = $class->handle_webhook_notif($request);

        if($response['valid'] ?? null)
        {
          if($response['status'])
          {
            $transaction = $response['transaction'];

            if($transaction->type != 'credits')
            {
              $this->update_affiliate_earnings($transaction);
            }

            if($transaction->status == 'paid')
            {
              $success = 1;

              $transaction->updated_at = now();

              $transaction->save();

              if($transaction->type == 'subscription')
              {
                $this->update_user_subscription_dates($transaction->id);
              }
              elseif($transaction->type == 'credits')
              {
                $this->update_user_prepaid_credits_dates(unwrap_str($transaction->products_ids));
              }

              $this->payment_confirmed_mail_notif($transaction);
            }
          }
        }
      }

      $wh_response = config("payment_gateways.{$processor}.webhook_responses.".($success ? 'success' : 'failed'));

      http_response_code($success ? 200 : 400);

      if(is_array($wh_response))
      {
        return response(json_encode($wh_response), $success ? 200 : 400)->header('Content-Type', 'application/json');
      }
      elseif(is_string($wh_response))
      {
        exit($wh_response);
      }
    }



    // ORDER COMPLETED
    public function order_completed(Request $request)
    {
      if($name = mb_strtolower($request->processor))
      {
          $class       = config("payment_gateways.{$name}.class") ?? abort(404);
          $class       = "App\Libraries\\$class";
          $class       = new $class;  

          if(method_exists($class, "complete_payment"))
          {
            $response = $class->complete_payment($request);

            $transaction = $response['transaction'] ?? abort(404);

            if(!is_null($response))
            {
              if($response['status'])
              {
                if(!is_null(config("payment_gateways.{$name}.webhook_responses")) && config('payments.enable_webhooks') === '0')
                {
                  $transaction->status = 'paid';                  
                }

                if($transaction->type != 'credits')
                {
                  $this->update_affiliate_earnings($transaction);
                }

                if($transaction->status == 'paid')
                {
                  $transaction->updated_at = now();

                  $transaction->save();

                  if($transaction->type == 'subscription')
                  {
                    $this->update_user_subscription_dates($transaction->id);
                  }
                  elseif($transaction->type == 'credits')
                  {
                    $this->update_user_prepaid_credits_dates(unwrap_str($transaction->products_ids));
                  }

                  $this->payment_confirmed_mail_notif($transaction);
                }

                if($transaction->type == 'product')
                {
                  $this->order_download_links($transaction, 1);
                }

                return redirect()->route('home.checkout.success')->with([
                  "transaction" => $transaction,
                  "transaction_status" => "success",
                  "transaction_response" => "done"
                ]);
              }
              else
              { 
                return redirect('/')->with(['user_message' => str_ireplace("-", ' ', slug($response['user_message']))]);
              }
            }
          }
      }

      return redirect('/');
    }



    public function credits_checkout(Request $request)
    {
      $transaction =  Transaction::where('transaction_id', $request->transaction_id)->where('status', '!=', 'paid')
                      ->where('confirmed', '=', 0)->first() ?? abort(404);

      if($transaction->user_id != Auth::id())
      {
        $transaction->delete();
        
        return redirect('/');
      }

      $transaction->details = json_decode($transaction->details, false, 512, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

      if($request->isMethod("get"))
      {
        return json($transaction->details);  
      }

      $query = ['transaction_id' => $transaction->transaction_id, 'user_id' => Auth::id(), 'processor' => 'credits'];

      return redirect()->route('home.checkout.order_completed', $query);
    }



    // NOTIFY BUYER ABOUT THE PAYMENT ONCE IT'S CONFIRMED
    public function payment_confirmed_mail_notif($transaction)
    {
      try
      {
        $buyer_email = $transaction->guest_email ?? User::find($transaction->user_id)->email;

        $transaction_details = json_decode($transaction->details, true);

        $order        = array_merge($transaction->getAttributes(), $transaction_details);
        $products_ids = explode(',', str_replace("'", "", $transaction->products_ids));

        $order_id = $order['order_id'] ?? $order['transaction_id'] ?? $order['reference_id'] ?? null;

        $mail_props = [
          'data'    => $order,
          'action'  => 'send',
          'view'    => 'mail.order',
          'to'      => $buyer_email,
          'subject' => __('Order :number. is completed. Your payment has been confirmed', ['number' => $order_id])
        ];

        sendEmailMessage($mail_props, config('mail.mailers.smtp.use_queue'));

        if(!$transaction->is_subscription)
        {
          Product::whereIn('id', $products_ids)->where('stock', '>', 0)->decrement('stock', 1);
        }

        if(config('app.admin_notifications.sales'))
        {
          $message = [];

          foreach($transaction_details['items'] as $item)
          {
            $message[] = "- {$item['name']}";              
          }

          $message[] = "\n\n<strong>".__('You earned :amount', ['amount' => price($transaction_details['total_amount'], false)])."</strong>";

          $mail_props = [
            'data'    => [
              'text' => implode("\n", $message), 
              'subject' => __('A new sale has been completed by :buyer_email', ['buyer_email' => $buyer_email]),
              'user_email' => $buyer_email
            ],
            'action'  => 'send',
            'view'    => 'mail.message',
            'to'      => config('app.email'),
            'subject' => __('A new sale has been completed by :buyer_email', ['buyer_email' => $buyer_email])
          ];

          sendEmailMessage($mail_props, config('mail.mailers.smtp.use_queue'));
        }
      }
      catch(\Exception $e){}
    }
    


    public function order_download_links($transaction, $send_email = 1)
    {
      try
      {
          $licenses_      = json_decode($transaction->licenses, true) ?? [];
          $licenses_ids   = array_filter(explode(',', str_replace("'", "", $transaction->licenses_ids)));
          $licenses_names = License::whereIn('id', $licenses_ids)->get()->pluck('name', 'id')->toArray();
          $products_ids   = array_filter(explode(',', str_ireplace("'", '', $transaction->products_ids)));
          $licenses       = [];

          if(count($products_ids) === count($licenses_ids))
          {
            $licenses = array_combine($products_ids, $licenses_ids);
          }

          $products = Product::whereIn('id', $products_ids)->get();
          
          $keys = Key::whereIn('product_id', $products_ids)->where('user_id', $transaction->user_id ?? $transaction->guest_token)->get();

          $download_params = [
            'type'     => null, 
            'order_id' => $transaction->id, 
            'user_id'  => $transaction->user_id ?? $transaction->guest_token, 
            'item_id'  => null
          ];

          $items = [];

          foreach($products as $k => $product)
          {
            $download_params['item_id'] = $product->id;

            $items[$k] = (object)[
              'id'      => $product->id,
              'name'    => $product->name,
              'url'     => item_url($product),
              'license' => null,
              'file'    => null,
              'key'     => null,
              'files'   => 0,
            ];

            if($product->file_name || $product->direct_download_link || config('app.generate_download_links_for_missing_files'))
            {
              $items[$k]->file = route('home.download', array_merge($download_params, ['type' => 'file']));
              $items[$k]->files += 1;
            }

            if($product->enable_license && isset($licenses_names[$licenses[$product->id]]))
            {
              $enc_license = encrypt(json_encode([
                'name' => $licenses_names[$licenses[$product->id]], 
                'license' => $licenses_[$product->id]
              ]), false);

              $items[$k]->license = route('home.download', array_merge($download_params, ['type' => 'license', 'content' => $enc_license]));
              $items[$k]->files += 1;
            }

            if($key = $keys->where('product_id', $product->id)->first())
            {
              $items[$k]->key = route('home.download', array_merge($download_params, ['type' => 'key', 'content' => encrypt($key->code, false)]));
              $items[$k]->files += 1;
            }
          }

          foreach($items as $k => $item)
          {
            if($item->files === 0)
            {
              unset($items[$k]);
            }
          }

          if($send_email)
          {
            if(!count($items))
            {
              return [];
            }

            $order_id     = $transaction->reference_id;
            $buyer_email  = $transaction->guest_email ?? User::find($transaction->user_id)->email;

            $mail_props = [
              'data'    => ['items' => $items, 'order_id' => $order_id],
              'action'  => 'send',
              'view'    => 'mail.download_links',
              'to'      => $buyer_email,
              'subject' => __(':app_name - Download links for order number :order_id', ['app_name' => config('app.name'), 'order_id' => $order_id])
            ];

            sendEmailMessage($mail_props, config('mail.mailers.smtp.use_queue'));
          }

          return $items;
      }
      catch(\Exception $e){}
    }


    
    public function update_keys($products_ids, $transaction)
    {
        $products_ids = array_filter($products_ids);
        
        foreach($products_ids as $product_id)
        {
            if($key = Key::useIndex('product_id')->where('product_id', $product_id)->where('user_id', null)->first())
            {
                DB::update("UPDATE key_s SET user_id = ?, purchased_at = ? WHERE id = ?", [$transaction->user_id ?? $transaction->guest_token, now()->format('Y-m-d H:i:s'), $key->id]);
            }
        }
    }



    public function update_affiliate_earnings($transaction)
    { 
        if($transaction->referrer_id && $transaction->type != "credits")
        {
          Affiliate_Earning::insert([
            'referrer_id'         => $transaction->referrer_id,
            'referee_id'          => $transaction->user_id ?? $transaction->guest_token,
            'transaction_id'      => $transaction->id,
            'commission_value'    => format_amount($transaction->amount * config('affiliate.commission', 0) / 100),
            'commission_percent'  => config('affiliate.commission', 0),
            'amount'              => format_amount($transaction->amount * config('affiliate.commission', 0) / 100),
            'paid'                => 0,
          ]);
        }
    }



    public function update_user_subscription_dates(int $transaction_id) 
    {
      DB::update("UPDATE user_subscription 
                    JOIN transactions ON user_subscription.transaction_id = transactions.id
                    SET ends_at = DATE_ADD(ends_at, INTERVAL TIMESTAMPDIFF(SECOND, transactions.created_at, NOW()) SECOND)
                    WHERE user_subscription.transaction_id = ? AND user_subscription.ends_at IS NOT NULL", [$transaction_id]);

      DB::update("UPDATE user_subscription 
                    JOIN transactions ON user_subscription.transaction_id = transactions.id
                    SET daily_downloads_date = DATE_ADD(ends_at, INTERVAL TIMESTAMPDIFF(DAY, transactions.created_at, NOW()) DAY) 
                    WHERE user_subscription.transaction_id = ? AND user_subscription.daily_downloads_date IS NOT NULL", [$transaction_id]);
    }



    public function update_user_prepaid_credits_dates(int $prepaid_credits_id)
    {
      DB::update("UPDATE user_prepaid_credits SET updated_at = DATE_ADD(updated_at, INTERVAL TIMESTAMPDIFF(SECOND, updated_at, NOW()) SECOND) WHERE id = ?", 
        [$prepaid_credits_id]);
    }


    public function update_pending_transactions()
    {
      if(config('payments.update_pending_transactions') === 0)
      {
        abort(404);
      }

      $transactions = Transaction::where(['status' => 'pending', 'refunded' => 0, 'confirmed' => 1])->get();
      
      foreach($transactions as $transaction)
      {
        try
        {
          if(config("payments_gateways.{$transaction->processor}"))
          {
            $payment_class = config("payment_gateways.{$transaction->processor}.class");
            $payment_class = "\App\Libraries\\$payment_class";
            $payment_class = new $payment_class;

            if(!method_exists($payment_class, "handle_webhook_notif") && method_exists($payment_class, 'verify_payment'))
            {
              $response = $payment_class->verify_payment($transaction);

              if($response['status'] ?? null === true)
              {
                $transaction->status = 'paid';

                $transaction->save();
              }
            }
          }
        }
        catch(\Throwable $t)
        {

        }
      } 
    }
}
