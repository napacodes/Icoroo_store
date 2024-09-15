<?php

return [
	"name" => "Iyzico",
	"url" => "https://www.iyzico.com/",
	"class" => "IyzicoLib",
	"slug" => "iyzico",
	"fields" => [
		"enabled" => [
			"type" => "toggler", 
			"validation" => "nullable|in:on", 
			"value" => ""			
		],
		"icon" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "/assets/images/payment/iyzico_icon.png",
    ],
    "description" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "MasterCard, MasterCard Debit, American Express, Visa Electron, Visa Debit ...",
    ],
    "order" => [
			"type" => "hidden", 
			"validation" => "nullable|numeric", 
			"value" => null,
		],
		"mode" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:live,sandbox|required_with:gateways.iyzico.enabled", 
			"value" => "sandbox", 
			"multiple" => 0,
			"options" => ["sandbox" => "Sandbox", "live" => "Live"]
		],
		"client_id" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.iyzico.enabled", 
			"value" => null
		],
		"secret_id" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.iyzico.enabled", 
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
			"buyer[id_number]" => ["type" => "text", "label" => "ID Number", "value" => "[USER_ID_NUMBER]", "class" => "", "replace" => [
				["search" => "[USER_ID_NUMBER]", "src" => "user", "value" => "id_number"]
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
	"payment_link" => 1,
];