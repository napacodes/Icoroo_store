<?php

return [
	"name" => "Credits",
	"url" => "#",
	"class" => "Credits",
	"slug" => "credits",
	"fields" => [
			"enabled" => [
				"type" => "toggler", 
				"validation" => "nullable|in:on", 
				"value" => ""
			],
			"icon" => [
	      "type" => "hidden", 
	      "validation" => "nullable|string", 
	      "value" => "/assets/images/payment/credits_icon.png",
	    ],
	    "description" => [
				"type" => "hidden", 
				"validation" => "nullable|string", 
				"value" => "Pay with your available credits.",
			],
			"order" => [
				"type" => "hidden", 
				"validation" => "nullable|numeric", 
				"value" => null,
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
	"methods_icons" => [],
	"assets" => [],
	"guest_checkout" => 0,
	"async" => 1,
	"supports_recurrent" => 0,
	"webhook_responses" => ["success" => "200 OK", "failed" => "404 Not Found"],
	"payment_link" => 0,
];