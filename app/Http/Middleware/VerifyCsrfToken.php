<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Encryption\Encrypter;


class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'install',
        'logout',
        'admin/*',
        'set_locale',
        'downloads/*/download',
        'downloads/dropbox_preview_url',
        'notifications/read',
        'item/*',
        'payment/token/*',
        'add_to_cart_async',
        'remove_from_cart_async',
        'item/product_folder',
        'update_price',
        'guest/*',
        'checkout/*',
        'user_notifs',
        'guest/download',
        'download',
        'send_email_verification_link',
        'save_reaction',
        'get_reactions',
        'items/live_search',
        'get_extension_from_mimetype',
        'credits_checkout/*',
        'test_form',
        'checkout/webhook',
        'file-manager/*',
        'check_database_connection',
        'live_sales',
        'checkout_form',
    ];


    public function __construct(Application $app, Encrypter $encrypter)
    {
        $this->except = array_merge($this->except, config('app.csrf_except', []));
        
        $this->app = $app;
        $this->encrypter = $encrypter;
    }
}
