<?php

return [
	"name" => "Flutterwave",
	"url" => "https://www.flutterwave.com",
	"class" => "Flutterwave",
	"slug" => "flutterwave",
	"fields" => [
		"enabled" => [
			"type" => "toggler", 
			"validation" => "nullable|in:on", 
			"value" => ""			
		],
		"icon" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "/assets/images/payment/flutterwave_icon.png",
    ],
    "description" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "Credit/Debit card, Bank transfer, Mobile money, M-Pesa, QR payment, USSD, Barter",
    ],
    "order" => [
			"type" => "hidden", 
			"validation" => "nullable|numeric", 
			"value" => null,
		],
		"mode" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:live,sandbox|required_with:gateways.flutterwave.enabled", 
			"value" => "sandbox", 
			"multiple" => 0,
			"options" => ["sandbox" => "Sandbox", "live" => "Live"]
		],
		"public_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.flutterwave.enabled", 
			"value" => null
		],
		"secret_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.flutterwave.enabled", 
			"value" => null
		],
		"encryption_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.flutterwave.enabled", 
			"value" => null
		],
		"secret_hash" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.flutterwave.enabled", 
			"value" => null
		],
		"methods" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|required_with:gateways.flutterwave.enabled", 
			"value" => null,
			"multiple" => 1,
			"options" => [
				"banktransfer" => "Bank transfer", 
				"mpesa" => "Mpesa", 
				"mobilemoneyrwanda" => "Mobile money rwanda", 
				"card" => "Card", 
				"qr" => "QR", 
				"mobilemoneyuganda" => "Mobile money uganda", 
				"ussd" => "USSD", 
				"credit" => "Credit", 
				"barter" => "Barter", 
				"mobilemoneyfranco" => "Mobile money franco", 
				"paga" => "Paga", 
				"payattitude" => "Payattitude", 
				"mobilemoneyghana" => "Mobile money ghana", 
				"mobilemoneyzambia" => "Mobile money zambia", 
				"account" => "Account", 
				"mobilemoneytanzania" => "Mmobile money tanzania", 
				"1voucher" => "Ivoucher",
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
      "cart" => ["type" => "hidden", "value" => "", "class" => "d-none", "replace" => []],
      "subscription_id" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "prepaid_credits_pack_id" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "processor" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "coupon" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "locale" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "_token" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "checkout_token" => ["type" => "hidden", "label" => null, "hidden", "value" => "", "class" => "d-none", "replace" => []],
			"buyer[firstname]" => ["type" => "text", "label" => "First name", "value" => "[USER_FIRSTNAME]", "class" => "", "replace" => [
				["search" => "[USER_FIRSTNAME]", "src" => "user", "value" => "firstname"]
			]],
			"buyer[lastname]" => ["type" => "text", "label" => "Last name", "value" => "[USER_LASTNAME]", "class" => "", "replace" => [
				["search" => "[USER_LASTNAME]", "src" => "user", "value" => "lastname"]
			]],
			"buyer[phone]" => ["type" => "text", "label" => "Phone", "value" => "[USER_PHONE]", "class" => "", "replace" => [
				["search" => "[USER_PHONE]", "src" => "user", "value" => "phone"]
			]],
			"buyer[email]" => ["type" => "email", "label" => "Email", "value" => "[USER_EMAIL]", "class" => "", "replace" => [
				["search" => "[USER_EMAIL]", "src" => "user", "value" => "email"]
			]],
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
	"webhook_responses" => ["success" => "200 OK", "failed" => "400"],
	"payment_link" => 1,
];