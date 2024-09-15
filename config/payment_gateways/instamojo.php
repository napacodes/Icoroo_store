<?php

return [
	"name" => "Instamojo",
	"url" => "https://www.instamojo.com/",
	"class" => "Instamojo",
	"slug" => "instamojo",
	"fields" => [
		"enabled" => [
			"type" => "toggler", 
			"validation" => "nullable|in:on", 
			"value" => ""			
		],
		"icon" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "/assets/images/payment/instamojo_icon.png",
    ],
    "description" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "Bank transfer, Debit/ Credit Cards, Netbanking, Wallets, UPI, EMI",
    ],
    "order" => [
			"type" => "hidden", 
			"validation" => "nullable|numeric", 
			"value" => null,
		],
		"mode" => [
			"type" => "dropdown", 
			"validation" => "nullable|string|in:live,sandbox|required_with:gateways.instamojo.enabled", 
			"value" => "sandbox", 
			"multiple" => 0,
			"options" => ["sandbox" => "Sandbox", "live" => "Live"]
		],
		/*"private_api_key" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.instamojo.enabled", 
			"value" => null
		],
		"private_auth_token" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.instamojo.enabled", 
			"value" => null
		],*/
		"private_salt" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.instamojo.enabled", 
			"value" => null
		],
		"client_id" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.instamojo.enabled", 
			"value" => null
		],
		"secret_id" => [
			"type" => "string", 
			"validation" => "nullable|string|max:255|required_with:gateways.instamojo.enabled", 
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