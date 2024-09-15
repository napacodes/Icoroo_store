<?php

return [
	"website" => [
	  '@context' => 'http://schema.org',
	  '@type' => 'WebSite',
	  'name' => '[WEBSITE_NAME]',
	  'url' => '[WEBSITE_URL]',
	  'potentialAction' => [
	    '@type' => 'SearchAction',
	    'target' => '[SEARCH_URL_ACTION]?&q={query}', // https://valexa.codemayer.net/items/search?q=test
	    'query' => 'required'
	  ]
	],
	

	"blog" => [
	  '@context' => 'http://schema.org',
	  '@type' => 'Blog',
	  '@id' => '[BLOG_URL]',
	  'name' => '[BLOG_NAME]',
	  'url' => '[BLOG_URL]',
	  'image' => '[BLOG_IMAGE]',
	  'description' => '[BLOG_SHORT_DESCRIPTION]',
	],
	

	"post" => [
	  '@context' => 'https://schema.org/',
	  '@type' => 'BlogPosting',
	  '@id' => '[POST_URL]',
	  'mainEntityOfPage' => '[POST_URL]',
	  'headline' => '[POST_NAME]',
	  'name' => '[POST_NAME]',
	  'description' => '[POST_SHORT_DESCRIPTION]',
	  'datePublished' => '[POST_CREATION_DATE]',
	  'dateModified' => '[POST_UPDATE_DATE]',
	  'image' => [
	    '@type' => 'ImageObject',
	    '@id' => '[POST_IMAGE_URL]',
	    'url' => '[POST_IMAGE_URL]',
	    'height' => '[POST_IMAGE_HEIGHT]',
	    'width' => '[POST_IMAGE_WIDTH]',
	  ],
	  'url' => '[POST_URL]',
	  'wordCount' => '[POST_WORD_COUNT]',
	  'keywords' => '[ARRAY_OF_KEYWORDS]',
	],

	
	"product" => [
	  "@context" => "https://schema.org/",
	  "@type" => "Product",
	  "name" => "[PRODUCT_NAME]",
	  "image" => "[PRODUCT_THUMBNAIL]",
	  "description" => "[PRODUCT_SHORT_DESCRIPTION]",
	  "aggregateRating" => [
	    "@type" => "AggregateRating",
	    "ratingValue" => "[PRODUCT_AGGREGATE_RATING]",
	    "reviewCount" => "[PRODUCT_COUNT_REVIEWS]",
	  ],
	  "offers" => [
	    "@type" => "Offer",
	    "availability" => "https://schema.org/[PRODUCT_STOCK_STATUS]", // InStock or OutOfStock
	    "price" => "[PRICE]",
	    "priceCurrency" => "[CURRENCY_CODE]",
	  ],
	  "review" => [
	    0 => [
	      "@type" => "Review",
	      "author" => "[REVIEWER_NAME]",
	      "datePublished" => "[REVIEW_DATE]",
	      "reviewBody" => "[REVIEW_COMMENT]",
	      "name" => "[REVIEW_NAME]",
	      "reviewRating" => [
	        "@type" => "Rating",
	        "bestRating" => "[MAX_ALLOWED_RATING]",
	        "ratingValue" => "[USER_RATING]",
	        "worstRating" => "[MIN_ALLOWED_RATING]",
	      ],
	    ],
	  ],
	],
	

	"page" => [
	  '@context' => 'http://schema.org',
	  '@type' => 'WebPage',
	  'name' => '[PAGE_NAME]',
	  'description' => '[PAGE_SHORT_DESCRIPTION]',
	],
	

	"search" => [
		'@context' => 'https://schema.org',
	  '@type' => 'WebSite',
	  'url' => '[WEBSITE_URL]', // https://valexa.codemayer.net/items/
	  'potentialAction' => [
	    '@type' => 'SearchAction',
	    'target' => '[SEARCH_URL_ACTION]?&q={query}', // https://valexa.codemayer.net/items/search?q=test
	    'query' => 'required'
	  ]
	]
];