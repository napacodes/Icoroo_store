<?php

return [
	"name" => "Coingate",
	"url" => "https://coingate.com/",
	"class" => "CoingateLib",
	"slug" => "coingate",
	"fields" => [
			"enabled" => [
				"type" => "toggler", 
				"validation" => "nullable|in:on", 
				"value" => ""
			],
			"icon" => [
	      "type" => "hidden", 
	      "validation" => "nullable|string", 
	      "value" => "/assets/images/payment/coingate_icon.png",
	    ],
	    "description" => [
				"type" => "hidden", 
				"validation" => "nullable|string", 
				"value" => "BTC, ETH, USDT, LTC",
			],
			"order" => [
				"type" => "hidden", 
				"validation" => "nullable|numeric", 
				"value" => null,
			],
			"mode" => [
				"type" => "dropdown", 
				"validation" => "nullable|string|in:live,sandbox|required_with:gateways.coingate.enabled", 
				"value" => "sandbox", 
				"multiple" => 0,
				"options" => ["sandbox" => "Sandbox", "live" => "Live"]
			],
			"auth_token" => [
				"type" => "string", 
				"validation" => "nullable|string|max:255|required_with:gateways.coingate.enabled", 
				"value" => null
			],
			"receive_currency" => [
				"type" => "string", 
				"validation" => "nullable|string|max:255|required_with:gateways.coingate.enabled", 
				"value" => null
			],
			"fee" => [
				"type" => "string", 
				"validation" => "nullable|numeric|gte:0|max:255", 
				"value" => null
			],
			"minimum" => [  // The minimum amount to pay to "Pay what you want"
				"type" => "string", 
				"validation" => "nullable|numeric|gte:0|max:255", 
				"value" => null
			],
			"auto_exchange_to" => [ // Auto-exchange Currency to This currency when using multiple currencies
				"type" => "string", 
				"validation" => "nullable|string|max:10", 
				"value" => null
			] 
	],
	"form" => [
    "inputs" => [
      "cart" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "subscription_id" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "prepaid_credits_pack_id" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "processor" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "coupon" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "locale" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "_token" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "checkout_token" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
    ],
    "note" => [],
    "checkout_buttons" => []
  ],
	"methods_icons" => [
		"btc.png" => "BTC",
		"eth.png" => "ETH",
		"usdt.png" => "USDT",
		"ltc.png" => "LTC",
	],
	"assets" => [],
	"guest_checkout" => 1,
	"async" => 0,
	"supports_recurrent" => 0,
	"webhook_responses" => ["success" => "200 OK", "failed" => "400"],
	"payment_link" => 1,
];