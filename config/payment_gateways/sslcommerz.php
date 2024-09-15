<?php

return [
	"name" => "Sslcommerz",
	"url" => "https://sslcommerz.com/",
	"class" => "Sslcommerz",
	"slug" => "sslcommerz",
	"fields" => [
		"enabled" => [
			"type" => "toggler", 
			"validation" => "nullable|in:on", 
			"value" => ""
		],
		"icon" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "/assets/images/payment/sslcommerz_icon.png",
    ],
    "description" => [
			"type" => "hidden", 
			"validation" => "nullable|string", 
			"value" => "Credit / Debit Cards, Mobile Banking, Internet Banking, E-Wallets, Easy Monthly Installments",
		],
		"order" => [
			"type" => "hidden", 
			"validation" => "nullable|numeric", 
			"value" => null,
		],
		"mode" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:live,sandbox|required_with:gateways.sslcommerz.enabled", 
			"value" => "sandbox", 
			"multiple" => 0,
			"options" => ["sandbox" => "Sandbox", "live" => "Live"]
		],
		"store_id" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.sslcommerz.enabled", 
			"value" => null
		],
		"store_passwd" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.sslcommerz.enabled", 
			"value" => null
		],
		"use_ipn" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:0,1|required_with:gateways.sslcommerz.enabled", 
			"value" => "1", 
			"multiple" => 0,
			"options" => ["0" => "No", "1" => "Yes"]
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
			"buyer[city]" => ["type" => "email", "label" => "City", "value" => "[USER_CITY]", "class" => "", "replace" => [
				["search" => "[USER_CITY]", "src" => "user", "value" => "city"]
			]],
			"buyer[country]" => ["type" => "email", "label" => "Country", "value" => "[USER_COUNTRY]", "class" => "", "replace" => [
				["search" => "[USER_COUNTRY]", "src" => "user", "value" => "country"]
			]],
			"buyer[zip_code]" => ["type" => "email", "label" => "Zip", "value" => "[USER_ZIP_CODE]", "class" => "", "replace" => [
				["search" => "[USER_ZIP_CODE]", "src" => "user", "value" => "country"]
			]],
			"buyer[state]" => ["type" => "email", "label" => "State", "value" => "[USER_STATE]", "class" => "", "replace" => [
				["search" => "[USER_STATE]", "src" => "user", "value" => "country"]
			]],
			"buyer[address]" => ["type" => "email", "label" => "Address", "value" => "[USER_ADDRESS]", "class" => "", "replace" => [
				["search" => "[USER_ADDRESS]", "src" => "user", "value" => "address"]
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