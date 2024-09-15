<?php

return [
	"name" => "Omise",
	"url" => "https://omise.com/",
	"class" => "Omise",
	"slug" => "omise",
	"fields" => [
		"enabled" => [
			"type" => "toggler", 
			"validation" => "nullable|in:on", 
			"value" => ""
		],
		"icon" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "/assets/images/payment/omise_icon.png",
    ],
    "description" => [
			"type" => "hidden", 
			"validation" => "nullable|string", 
			"value" => "Credit/Debit card, Installment payments, Internet Banking, PromptPay, TrueMoney Wallet, Rabbit Line Pay, PayNow, OCBC Pay Anyone, Alipay, WeChat Pay, Bill payment, Pay-easy, FPX",
		],
		"order" => [
			"type" => "hidden", 
			"validation" => "nullable|numeric", 
			"value" => null,
		],
		"mode" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:live,sandbox|required_with:gateways.omise.enabled", 
			"value" => "sandbox", 
			"multiple" => 0,
			"options" => ["sandbox" => "Sandbox", "live" => "Live"]
		],
		"public_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.omise.enabled", 
			"value" => null
		],
		"secret_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.omise.enabled", 
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
			"validation" => "nullable|string|max:3", 
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
      "omiseToken" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "omiseSource" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
    ],
    "note" => [],
    "checkout_buttons" => []
  ],
	"methods_icons" => [
		"kbank.png" => "Kasikorn Bank",
		"ktc_card.jpg" => "Krungthai Card",
		"krungsri_first_choice.png" => "krungsri first choice",
		"scb.png" => "Siam Commercial Bank",
	],
	"assets" => [
		["type" => "js", "defer" => 0, "src" => "https://cdn.omise.co/omise.js"],
		["type" => "js_init", "defer" => 0, "code" => "window.omisePublicKey = '[PUBLIC_KEY]';", "replace" => [
				["src" => "config", "search" => "[PUBLIC_KEY]", "value" => "payments_gateways.omise.public_key"],
			]
		]
	],
	"guest_checkout" => 1,
	"async" => 1,
	"supports_recurrent" => 0,
	"webhook_responses" => ["success" => "200 OK", "failed" => "404 Not Found"],
	"payment_link" => 0,
];