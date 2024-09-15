<?php

define('LARAVEL_START', microtime(true));

require_once __DIR__ . '/../app/Helpers/Helpers.php';

if(!file_exists(__DIR__.'/../update/.env'))
{
	exit("Please extract the script version 4.3.0 to your website main directory / update folder (e.g. public_html/example.com/update)");
}

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel')->handle(Illuminate\Http\Request::capture());

if(env('APP_VERSION') === '4.3.0')
{
	exit("Update 4.3.0 already installed");
}

$dirs = [
	__DIR__.'/../update/app' => __DIR__.'/../app',
	__DIR__.'/../update/bootstrap' => __DIR__.'/../bootstrap',
	__DIR__.'/../update/config' => __DIR__.'/../config',
	__DIR__.'/../update/database' => __DIR__.'/../database',
	__DIR__.'/../update/public/assets' => __DIR__.'/../public/assets',
	__DIR__.'/../update/resources' => __DIR__.'/../resources',
	__DIR__.'/../update/routes' => __DIR__.'/../routes',
	__DIR__.'/../update/vendor' => __DIR__.'/../vendor',
];

foreach($dirs as $directory => $destination)
{
	if(!\File::copyDirectory($directory, $destination))
	{
		abort(403, "Failed to copy {$directory} directory to {$destination}");
	}
}

$files = [
	__DIR__.'/../update/public/index.php' => __DIR__.'/index.php',
	__DIR__.'/../update/composer.json' => __DIR__.'/../composer.json',

];

foreach($files as $path => $target)
{	
	if(!\File::copy($path, $target))
	{
		abort(403, "Failed to copy {$path} file to {$target}");
	}
}


$db_update = <<<DB_UPDATE
	SET NAMES 'utf8';
	ALTER TABLE products ADD COLUMN group_buy_price FLOAT DEFAULT NULL;
	ALTER TABLE products ADD COLUMN group_buy_min_buyers INT DEFAULT NULL;
	ALTER TABLE products ADD COLUMN group_buy_expiry INT DEFAULT NULL;
	ALTER TABLE products ADD COLUMN meta_tags TEXT DEFAULT NULL;
	ALTER TABLE products MODIFY created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER meta_tags;
	ALTER TABLE products MODIFY updated_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER created_at;
	ALTER TABLE products MODIFY deleted_at DATETIME DEFAULT NULL AFTER updated_at;
	CREATE TABLE IF NOT EXISTS user_shopping_cart_item (
	  user_ip VARCHAR(255) NOT NULL,
	  user_id BIGINT DEFAULT NULL,
	  item_id BIGINT NOT NULL
	) ENGINE = INNODB, CHARACTER SET utf8mb3, COLLATE utf8mb3_unicode_ci;
	ALTER TABLE user_shopping_cart_item ADD UNIQUE INDEX user_ip_item_id(user_ip, item_id);
	ALTER TABLE users ADD COLUMN two_factor_auth TINYINT(1) DEFAULT 0;
	ALTER TABLE users ADD COLUMN two_factor_auth_expiry INT DEFAULT NULL;
	ALTER TABLE users ADD COLUMN two_factor_auth_secret VARCHAR(255) DEFAULT NULL;
	ALTER TABLE users ADD COLUMN two_factor_auth_ip VARCHAR(255) DEFAULT NULL;
	ALTER TABLE users MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER two_factor_auth_ip;
	ALTER TABLE users MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at;
	ALTER TABLE users MODIFY deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
	CREATE TABLE IF NOT EXISTS personal_access_tokens (
	  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	  tokenable_type VARCHAR(255) NOT NULL,
	  tokenable_id BIGINT UNSIGNED NOT NULL,
	  name VARCHAR(255) NOT NULL,
	  token VARCHAR(64) NOT NULL,
	  abilities TEXT DEFAULT NULL,
	  last_used_at TIMESTAMP NULL DEFAULT NULL,
	  created_at TIMESTAMP NULL DEFAULT NULL,
	  updated_at TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (id)
	) ENGINE = INNODB, CHARACTER SET utf8mb3, COLLATE utf8mb3_unicode_ci;
	ALTER TABLE personal_access_tokens ADD UNIQUE INDEX personal_access_tokens_token_unique(token);
	ALTER TABLE personal_access_tokens ADD INDEX personal_access_tokens_tokenable_type_tokenable_id_index(tokenable_type, tokenable_id);
DB_UPDATE;

// 	1. -------- UPDATE DATABASE -------------
//
DB::unprepared($db_update);
//
// 	----------------------------------------


// 2. ------- UPDATE SETTINGS --------
//
$settings = \App\Models\Setting::first();

$general_settings = json_decode($settings->general, true);
$general_settings['two_factor_authentication'] = 0;
$general_settings['two_factor_authentication'] = 1440;
$settings->general = json_encode($general_settings);

$payment_settings = json_decode($settings->payments, true);
$payment_settings['update_pending_transactions'] = 0;
$settings->payments = json_encode($payment_settings);

$settings->save();

update_env_var("APP_VERSION", "4.3.0");


exit("Update installed successfully, please remove this file from '/public' folder");
