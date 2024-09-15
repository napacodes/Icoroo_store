<?php

return [
	"name" => "Skrill",
	"url" => "https://www.skrill.com/",
	"class" => "Skrill",
	"slug" => "skrill",
	"fields" => [
		"enabled" => [
			"type" => "toggler", 
			"validation" => "nullable|in:on", 
			"value" => ""			
		],
		"icon" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "/assets/images/payment/skrill_icon.png",
    ],
    "description" => [
			"type" => "hidden", 
			"validation" => "nullable|string", 
			"value" => "iDEAL, Maestro, Neteller, Skrill Digital Wallet, Credit or Debit Card",
		],
		"order" => [
			"type" => "hidden", 
			"validation" => "nullable|numeric", 
			"value" => null,
		],
		"mode" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:live,sandbox|required_with:gateways.skrill.enabled", 
			"value" => "sandbox", 
			"multiple" => 0,
			"options" => ["sandbox" => "Sandbox", "live" => "Live"]
		],
		"merchant_account" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.skrill.enabled", 
			"value" => null
		],
		"mqiapi_secret_word" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.skrill.enabled", 
			"value" => null
		],
		"mqiapi_password" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.skrill.enabled", 
			"value" => null
		],
		"methods" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|required_with:gateways.skrill.enabled", 
			"value" => null,
			"multiple" => 1,
			"options" => [
				"ACC" => "All card types available in the customer’s country",
				"ACH" => "iACH",
				"ACI" => "Astropay - Cash (Invoice)",
				"ADB" => "Astropay - Online bank transfer (Direct Bank Transfer)",
				"ALI" => "Alipay",
				"AOB" => "Astropay - Offline bank transfer",
				"CSI" => "CartaSi",
				"DNK" => "Dankort",
				"GCB" => "Carte Bleue",
				"GCI" => "iDEAL GCI",
				"GLU" => "Trustly",
				"IDL" => "iDEAL IDL",
				"MAE" => "Maestro",
				"MSC" => "Mastercard",
				"NTL" => "Neteller",
				"PCH" => "Paysafecash",
				"PSC" => "Paysafecard",
				"PSP" => "PostePay",
				"PWY" => "Przelewy24",
				"VSA" => "Visa",
				"VSE" => "Visa Electron",
				"WLT" => "Skrill Digital Wallet"
			]
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
    ],
    "note" => [],
    "checkout_buttons" => []
  ],
	"methods_icons" => [
		"mastercard-curved-64px.png" => null,
		"visa-curved-64px.png" => null,
		"american-express-curved-64px.png" => null,
		"discover-curved-64px.png" => null,
	],
	"assets" => [],
	"guest_checkout" => 1,
	"async" => 0,
	"supports_recurrent" => 0,
	"webhook_responses" => ["success" => "200 OK", "failed" => "404 Not Found"],
	"payment_link" => 0,
];