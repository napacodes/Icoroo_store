<?php

return [
  "name" => "Authorize Net",
  "url" => "https://authorize.net/",
  "class" => "Authorizenet",
  "slug" => "authorizenet",
  "fields" => [
    "enabled" => [
      "type" => "toggler", 
      "validation" => "nullable|in:on", 
      "value" => ""
    ],
    "icon" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "/assets/images/payment/authorizenet_icon.png",
    ],
    "description" => [
      "type" => "hidden", 
      "validation" => "nullable|string", 
      "value" => "Debit cards, Credit cards, Apple Pay, PayPal ...",
    ],
    "order" => [
      "type" => "hidden", 
      "validation" => "nullable|numeric", 
      "value" => null,
    ],
    "mode" => [
      "type" => "dropdown", 
      "validation" => "nullable|string|in:live,sandbox|required_with:gateways.authorizenet.enabled", 
      "value" => "sandbox", 
      "multiple" => 0,
      "options" => ["sandbox" => "Sandbox", "live" => "Live"]
    ],
    "api_login_id" => [
      "type" => "string", 
      "validation" => "nullable|string|max:255|required_with:gateways.authorizenet.enabled", 
      "value" => null
    ],
    "client_key" => [
      "type" => "string", 
      "validation" => "nullable|string|max:255|required_with:gateways.authorizenet.enabled", 
      "value" => null
    ],
    "transaction_key" => [
      "type" => "string", 
      "validation" => "nullable|string|max:255|required_with:gateways.authorizenet.enabled", 
      "value" => null
    ],
    "signature_key" => [
      "type" => "string", 
      "validation" => "nullable|string|max:255|required_with:gateways.authorizenet.enabled", 
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
      "dataValue" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "dataDescriptor" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "_token" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
      "checkout_token" => ["type" => "hidden", "label" => null, "value" => "", "class" => "d-none", "replace" => []],
    ],
    "note" => [],
    "checkout_buttons" => [
      [
        "html" => <<<BTN
          <button type="button" id="AcceptUIBtn" class="AcceptUI d-none" data-billingAddressOptions='{"show":true, "required":false}' data-apiLoginID="[API_LOGIN_ID]" data-clientKey="[CLIENT_KEY]" data-acceptUIFormBtnTxt="Submit" data-acceptUIFormHeaderTxt="Card Information" data-paymentOptions='{"showCreditCard": true, "showBankAccount": true}' data-responseHandler="authorizeNetResponseHandler">[CHECKOUT]</button>
         BTN,
        'replace' =>   [
          ["search" => "[API_LOGIN_ID]", "src" => "config", "value" => "payments_gateways.authorizenet.api_login_id"],
          ["search" => "[CLIENT_KEY]", "src" => "config", "value" => "payments_gateways.authorizenet.client_key"],
          ["search" => "[CHECKOUT]", "src" => "__", "value" => "Checkout"]
        ]
      ]
    ]
  ],
  "methods_icons" => [
    "visa-curved-64px.png" => null,
    "mastercard-curved-64px.png" => null,
    "american-express-curved-64px.png" => null,
    "paypal-curved-64px.png" => null,
  ],
  "assets" => [
    ["type" => "js", "defer" => 0, "src" => "https://js[MODE].authorize.net/v3/AcceptUI.js", "replace" => 
      [
        ["search" => "[MODE]", "src" => "config", "value" => "payments_gateways.authorizenet.mode"],
        ["search" => "sandbox", "src" => null, "value" => "test"],
        ["search" => "live", "src" => null, "value" => ""]
      ]
    ],
    ["type" => "js", "defer" => 0, "src" => "https://js[MODE].authorize.net/v1/Accept.js", "replace" => 
      [
        ["search" => "[MODE]", "src" => "config", "value" => "payments_gateways.authorizenet.mode"],
        ["search" => "sandbox", "src" => null, "value" => "test"],
        ["search" => "live", "src" => null, "value" => ""]
      ]
    ],
  ],
  "guest_checkout" => 1,
  "async" => 1,
  "supports_recurrent" => 0,
  "webhook_responses" => ["success" => "200 OK", "failed" => "400"],
  "payment_link" => 0,
];