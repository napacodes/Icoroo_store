<?php

return [
	"name" => "Tazapay",
	"url" => "https://www.tazapay.com/",
	"class" => "Tazapay",
	"slug" => "tazapay",
	"fields" => [
		"enabled" => [
			"type" => "toggler", 
			"validation" => "nullable|in:on", 
			"value" => ""
		],
		"icon" => [
			"type" => "hidden", 
			"validation" => "nullable|string", 
			"value" => "/assets/images/payment/tazapay_icon.JPG",
		],
		"description" => [
			"type" => "hidden", 
			"validation" => "nullable|string", 
			"value" => "PayPal, SEPA, iDEAL, VISA, Klarna, MasterCard, Przelewy24, EPS, Sepa Direct Debit, Maestro ...",
		],
		"order" => [
			"type" => "hidden", 
			"validation" => "nullable|numeric", 
			"value" => null,
		],
		"mode" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:live,sandbox|required_with:gateways.tazapay.enabled", 
			"value" => "sandbox", 
			"multiple" => 0,
			"options" => ["sandbox" => "Sandbox", "live" => "Live"]
		],
		"public_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.tazapay.enabled", 
			"value" => null
		],
		"api_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.tazapay.enabled", 
			"value" => null
		],
		"secret_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.tazapay.enabled", 
			"value" => null
		],
		"method" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|required_with:gateways.tazapay.enabled", 
			"value" => null,
			"multiple" => 1,
			"options" => [
				"card" => "Card",
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
      "buyer[firstname]" => ["type" => "text", "label" => "First name", "value" => "[USER_FIRSTNAME]", "attributes" => ["required"], "class" => "", "replace" => [
				["search" => "[USER_FIRSTNAME]", "src" => "user", "value" => "firstname"]
			]],
			"buyer[lastname]" => ["type" => "text", "label" => "Last name", "value" => "[USER_LASTNAME]", "attributes" => ["required"], "class" => "", "replace" => [
				["search" => "[USER_LASTNAME]", "src" => "user", "value" => "lastname"]
			]],
			"buyer[email]" => ["type" => "email", "label" => "Email", "value" => "[USER_EMAIL]", "attributes" => ["required"], "class" => "w-100 mb-2", "replace" => [
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
	"webhook_responses" => ["success" => "200 OK", "failed" => "404 Not Found"],
	"payment_link" => 0, 
];