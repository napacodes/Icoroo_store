<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers as Ctrls;

Route::match(['get', 'post'], 'install', [Ctrls\HomeController::class, 'install'])
->name('home.install_app');

Route::post('check_database_connection', [Ctrls\SettingsController::class, 'test_database_connection'])
->name('settings.check_database_connection');

Route::get('set_currency', [Ctrls\HomeController::class, 'set_currency'])->name('set_currency');

Route::middleware(['app_installed', 'is_blocked', 'set_locale', 'set_exchange_rate', 'set_template', 'affiliate', 'prepaid_credits'])
->group(function()
{
    Route::get('update_pending_checkouts', [Ctrls\CheckoutController::class, 'update_pending_transactions'])->name('update_pending_transactions');

    Route::get('get_group_buy_buyers', [Ctrls\HomeController::class, 'get_group_buy_buyers'])->name('get_group_buy_buyers');

    Route::middleware(array_filter(["block_crawlers", config('app.user_views_per_minute') ? ("throttle:".config('app.user_views_per_minute', 60).",1") : null]))
    ->group(function()
    {
        if(config('app.installed') === true)
        {
            if($custom_routes = \App\Models\Custom_Route::where('active', 1)->get())
            {
              foreach($custom_routes as $custom_route)
              {
                $method = $custom_route->method;
                
                $route = Route::$method($custom_route->slug, function() use($custom_route)
                {
                  $view = rtrim($custom_route->view, '.blade.php');

                  return view("custom.{$view}");
                });
              }
            }
        }

        Route::post('live_sales', [Ctrls\HomeController::class, 'live_sales'])
        ->name('home.live_sales');

        Route::get('translations.js', [Ctrls\HomeController::class, 'load_translations']);
        Route::get('props.js', [Ctrls\HomeController::class, 'load_js_props']);

        Route::get('update_statistics', [Ctrls\HomeController::class, 'update_statistics']);

        Route::get('realtime_views', [Ctrls\HomeController::class, 'realtime_views']);

        Route::get('{sitemap}{type}', [Ctrls\HomeController::class, 'generate_sitemap'])
        ->name('sitemap')
        ->where('sitemap', '^(sitemap|sitemap\.xml)$')
        ->where('type', '^(|_products\.xml|_posts\.xml|_pages\.xml|_index\.xml)$');


        Route::get('bricks_mask', [Ctrls\HomeController::class, 'bricks_mask'])->name('bricks_mask');


        Route::post('set_locale', [Ctrls\HomeController::class, 'set_locale'])->name('set_locale');


        Route::get('set_template', [Ctrls\HomeController::class, 'set_template'])->name('set_template');

        Route::get('download/{type}/{order_id}_{user_id}_{item_id}', [Ctrls\HomeController::class, 'download'])
        ->where('type', '^file|license|key$')
        ->name('home.download');
        

        Route::get('stream_vid/{id}', [Ctrls\HomeController::class, 'stream_video'])
        ->name('stream_vid');


        Route::get('get_item_data', [Ctrls\HomeController::class, 'get_item_data'])
        ->name('home.get_item_data');


        Route::match(['post', 'get'], 'unsubscribe', [Ctrls\HomeController::class, 'unsubscribe_from_newsletter'])
        ->name('home.unsubscribe_from_newsletter');

        Route::post('checkout_form', [Ctrls\HomeController::class, 'checkout_form'])
        ->name('checkout_form');

        Route::match(['get', 'post'], 'checkout/webhook/{processor?}', [Ctrls\CheckoutController::class, 'webhook'])
        ->name('home.checkout.webhook');

        Auth::routes(['verify' => config('app.email_verification') ? true : false]);

        Route::get('logout', function(Request $request)
        {
          \Auth::logout();

          return redirect($request->server('HTTP_REFERER') ?? '/');
        });

        Route::get('login/{provider}', [Ctrls\Auth\LoginController::class, 'redirectToProvider'])
        ->name('social_account.login');
        //->where('provider', '^(github|facebook|google|twitter|linkedin|vkontakte)$');


        Route::get('login/{provider}/callback', [Ctrls\Auth\LoginController::class, 'handleProviderCallback'])
        ->name('social_account.callback');
        //->where('provider', '^(github|facebook|google|twitter|linkedin|vkontakte)$');

        Route::post('get_temp_url', [Ctrls\ProductsController::class, 'get_temp_url'])
        ->name('products.get_temp_url');


        Route::get('/', [Ctrls\HomeController::class, 'index'])
        ->name('home');

        Route::get('pay/{token}', [Ctrls\HomeController::class, 'proceed_payment_link'])
        ->name('home.proceed_payment_link');        

        Route::post('user_notifs', [Ctrls\HomeController::class, 'init_notifications'])
        ->name('home.user_notifs');


        if(config('app.blog.enabled'))
        {
            Route::prefix('blog')->group(function()
            {
                Route::get('/', [Ctrls\HomeController::class, 'blog'])->name('home.blog');

                Route::get('category/{category}', [Ctrls\HomeController::class, 'blog'])->name('home.blog.category');

                Route::get('tag/{tag}', [Ctrls\HomeController::class, 'blog'])->name('home.blog.tag');

                Route::get('search', [Ctrls\HomeController::class, 'blog'])->name('home.blog.q');

                Route::get('{slug}', [Ctrls\HomeController::class, 'post'])->name('home.post');
            });
        }

        if(config('affiliate.enabled'))
        {
          Route::get('affiliate-program', [Ctrls\HomeController::class, 'affiliate'])
          ->name('home.affiliate');
        }

        Route::get('page/{slug}', [Ctrls\HomeController::class, 'page'])
        ->name('home.page');


        Route::prefix('items')->group(function()
        {
            Route::post('live_search', [Ctrls\HomeController::class, 'live_search']);

            Route::get('filter/{filter}', [Ctrls\HomeController::class, 'products'])
            ->name('home.products.filter')
            ->where('filter', '^(free|newest|flash|featured|trending)$');

            Route::get('category/{category_slug}/{subcategory_slug?}', [Ctrls\HomeController::class, 'products'])
            ->name('home.products.category');

            Route::get('search', [Ctrls\HomeController::class, 'products'])
            ->name('home.products.q');
        });


        Route::get(config('app.permalink_url_identifer').'/{slug}', [Ctrls\HomeController::class, 'product_with_permalink'])->name('product_with_permalink');

        Route::prefix('item')->group(function()
        {
            Route::get('{id}/{slug}', [Ctrls\HomeController::class, 'product'])
            ->name('home.product');

            Route::get('{slug}', [Ctrls\HomeController::class, 'old_product_redirect'])
            ->name('home.old_product');
        });
        
        Route::post('save_reaction', [Ctrls\HomeController::class, 'save_reaction'])
        ->name('home.save_reaction');
        
        Route::post('get_reactions', [Ctrls\HomeController::class, 'get_reactions'])
        ->name('home.get_reactions');

        Route::match(['post', 'get'], 'support', [Ctrls\HomeController::class, 'support'])
        ->name('home.support');


        if(config('app.subscriptions.enabled'))
        {
            Route::get('pricing', [Ctrls\HomeController::class, 'subscriptions'])
            ->name('home.subscriptions');       
        }
        

        Route::post('newsletter', [Ctrls\HomeController::class, 'subscribe_to_newsletter'])
        ->name('home.newsletter');

        Route::get('unsubscribe/{md5_email}', [Ctrls\HomeController::class, 'unsubscribe_from_newsletter'])
        ->name('home.unsubscribe');

        Route::post('add_to_cart_async', [Ctrls\HomeController::class, 'add_to_cart_async'])
        ->name('home.add_to_cart_async');

        Route::post('remove_from_cart_async', [Ctrls\HomeController::class, 'remove_from_cart_async'])
        ->name('home.remove_from_cart_async');

        Route::post('update_price', [Ctrls\HomeController::class, 'update_price']);

        
        Route::get('checkout/failed', [Ctrls\HomeController::class, 'checkout_error'])
        ->name('home.checkout.error');

        
        Route::middleware('guest_checkout_allowed')->group(function()
        {
            // CHECKOUT
            Route::prefix('checkout')->middleware('verified')->group(function()
            {
                Route::get('/', [Ctrls\HomeController::class, 'checkout'])
                ->name('home.checkout')
                ->middleware('auth_if_subscription');

                Route::match(['post', 'get'], 'payment/order_completed/{processor?}', [Ctrls\CheckoutController::class, 'order_completed'])
                ->name('home.checkout.order_completed');

                Route::get('success', [Ctrls\CheckoutController::class, 'success'])
                ->name('home.checkout.success');

                        Route::post('validate_coupon', [Ctrls\CheckoutController::class, 'validate_coupon'])
                ->name('home.checkout.validate_coupon');

                Route::post('payment', [Ctrls\CheckoutController::class, 'payment'])
                ->name('home.checkout.payment')
                ->middleware('valid_checkout_request', 'valid_payment_method');
            });
        });


        Route::post('download_license', [Ctrls\HomeController::class, 'download_license'])
        ->name('home.download_license');


        Route::get('user/favorites', [Ctrls\HomeController::class, 'user_favorites'])
        ->name('home.favorites');
    });
  
    Route::match(['get', 'post'], '2fa', [Ctrls\HomeController::class, 'two_factor_authentication'])->middleware('auth')->name('two_factor_authentication');

    Route::middleware(['auth', 'two_factor_auth'])->group(function()
    {
        Route::get('{google_storage_callback}', [Ctrls\SettingsController::class, 'google_storage_connect'])
        ->where('google_storage_callback', '^(gd_callback|gcs_callback)$');

        Route::match(['post', 'get'], 'credits_checkout/{transaction_id}', [Ctrls\CheckoutController::class, 'credits_checkout'])
        ->name('home.credits_checkout');

        Route::match(['get', 'post'], "prepaid_credits", [Ctrls\HomeController::class, 'prepaid_credits'])
        ->name('home.add_prepaid_credits');

        Route::get('delete_comment/{id}', [Ctrls\HomeController::class, 'delete_comment'])
        ->name('delete_comment');

        Route::get('delete_review/{id}', [Ctrls\HomeController::class, 'delete_review'])
        ->name('delete_review');

        // SUBSCRIPTIONS
        if(config('app.subscriptions.enabled'))
        {
            Route::post('checkout/subscription/payment', [Ctrls\CheckoutController::class, 'payment'])
            ->name('home.subscription.payment');
        }


        // USER 
        Route::middleware('is_not_admin')->group(function()
        {
          Route::prefix('user')->group(function()
          {
            Route::match(['get', 'post'], 'profile', [Ctrls\HomeController::class, 'user_profile'])
            ->name('home.profile');

            Route::get('invoices', [Ctrls\HomeController::class, 'user_invoices'])
            ->name('home.invoices');

            Route::get('subscriptions', [Ctrls\HomeController::class, 'user_subscriptions'])
            ->name('home.user_subscriptions');

            Route::get('purchases', [Ctrls\HomeController::class, 'user_purchases'])
            ->name('home.purchases');

            Route::get('coupons', [Ctrls\HomeController::class, 'user_coupons'])
            ->name('home.user_coupons');

            Route::get('notifications', [Ctrls\HomeController::class, 'user_notifications'])
            ->name('home.notifications');

            Route::get('credits', [Ctrls\HomeController::class, 'user_credits'])
            ->name('home.credits');

            Route::get('prepaid_credits', [Ctrls\HomeController::class, 'user_prepaid_credits'])
            ->name('home.user_prepaid_credits');
          });


          Route::get('user_affiliate_earnings', [Ctrls\HomeController::class, 'user_affiliate_earnings'])
          ->name('home.user_affiliate_earnings');

          Route::post('send_email_verification_link', [Ctrls\HomeController::class, 'send_email_verification_link'])
          ->name('home.send_email_verification_link');

          

          Route::get('invoice', [Ctrls\HomeController::class, 'invoice'])
          ->name('home.invoice');

          Route::post('invoices', [Ctrls\HomeController::class, 'export_invoice'])
          ->name('home.export_invoice');
        });


        Route::post('downloads/dropbox_preview_url', [Ctrls\HomeController::class, 'get_dropbox_preview_url'])
        ->name('home.downloads.dropbox_preview_url');

        

        Route::post('notifications/read', [Ctrls\HomeController::class, 'notifications_read'])
        ->name('home.notifications.read');
        
        Route::get('product_thumb_{cover_name}.jpg', [Ctrls\HomeController::class, 'product_thumb'])
        ->name('home.product_thumb');

        Route::get('image/{size}/{name}.{ext}', [Ctrls\HomeController::class, "resize_image"])
        ->name('resize_image');

        Route::post('item/{id}/{slug}', [Ctrls\HomeController::class, 'product'])
        ->name('home.product');
        

        Route::prefix('admin')->middleware('auth', 'is_admin')->group(function()
        {
            // Admin Dashboard
            Route::get('/', [Ctrls\DashboardController::class, 'index'])
            ->name('admin');

            Route::post('dashboard', [Ctrls\DashboardController::class, 'update_sales_chart'])
            ->name('admin.update_sales_chart');

            // File manager
            Route::get('file-manager', [Ctrls\DashboardController::class, 'file_manager'])
            ->name('file_manager');

            Route::get('show_file_manager', [Ctrls\DashboardController::class, 'show_file_manager'])
            ->name('show_file_manager');

            Route::get('log-viewer', [Ctrls\DashboardController::class, 'log_viewer'])
            ->name('log_viewer');

            // Report errors
            Route::post('report_errors', [Ctrls\DashboardController::class, 'report_errors'])
            ->name('admin.report_errors');

            // Validate Licenses
            Route::post('validate_license', [Ctrls\LicenseValidatorController::class, 'validate_license'])
            ->name('validate_license');


            // Products
            Route::prefix('products')->group(function()
            {
                Route::get('/', [Ctrls\ProductsController::class, 'index'])
                ->name('products');

                Route::get('create', [Ctrls\ProductsController::class, 'create'])
                ->name('products.create');

                Route::post('store', [Ctrls\ProductsController::class, 'store'])
                ->name('products.store');

                Route::get('edit/{id}', [Ctrls\ProductsController::class, 'edit'])
                ->name('products.edit');

                Route::match(['post', 'get'], 'update/{id}', [Ctrls\ProductsController::class, 'update'])
                ->name('products.update');

                Route::get('destroy/{ids}', [Ctrls\ProductsController::class, 'destroy'])
                ->name('products.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\ProductsController::class, 'export'])
                ->name('products.export');

                Route::post('active', [Ctrls\ProductsController::class, 'active'])
                ->name('products.active');

                Route::post('status', [Ctrls\ProductsController::class, 'status'])
                ->name('products.status');

                Route::post('list_files', [Ctrls\ProductsController::class, 'list_files'])
                ->name('products.list_files');

                Route::post('get_stock_files/{id}', [Ctrls\ProductsController::class, 'get_stock_files'])
                ->name('products.get_stock_files');

                Route::post('upload_file_async', [Ctrls\ProductsController::class, 'upload_file_async'])
                ->name('products.upload_file_async');

                Route::post('delete_file_async', [Ctrls\ProductsController::class, 'delete_file_async'])
                ->name('products.delete_file_async');

                Route::post('api', [Ctrls\ProductsController::class, 'api'])
                ->name('products.api');
            });


            // Licenses
            Route::prefix('licenses')->group(function()
            {
                Route::get('/', [Ctrls\LicensesController::class, 'index'])
                ->name('licenses');

                Route::get('create', [Ctrls\LicensesController::class, 'create'])
                ->name('licenses.create');

                Route::post('store', [Ctrls\LicensesController::class, 'store'])
                ->name('licenses.store');

                Route::get('edit/{id}', [Ctrls\LicensesController::class, 'edit'])
                ->name('licenses.edit');

                Route::post('update/{id}', [Ctrls\LicensesController::class, 'update'])
                ->name('licenses.update');

                Route::get('destroy/{ids}', [Ctrls\LicensesController::class, 'destroy'])
                ->name('licenses.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\LicensesController::class, 'export'])
                ->name('licenses.export');

                Route::post('active', [Ctrls\LicensesController::class, 'active'])
                ->name('licenses.active');
            });
            


            // Keys, Accounts ...
            Route::prefix('keys')->group(function()
            {
                Route::get('keys', [Ctrls\KeysController::class, 'index'])
                ->name('keys');

                Route::get('create', [Ctrls\KeysController::class, 'create'])
                ->name('keys.create');

                Route::post('store', [Ctrls\KeysController::class, 'store'])
                ->name('keys.store');

                Route::get('edit/{id}', [Ctrls\KeysController::class, 'edit'])
                ->name('keys.edit');

                Route::post('update/{id}', [Ctrls\KeysController::class, 'update'])
                ->name('keys.update');

                Route::post('update_async', [Ctrls\KeysController::class, 'update_async'])
                ->name('keys.update_async');
                
                Route::post('void_purchase', [Ctrls\KeysController::class, 'void_purchase'])
                ->name('keys.void_purchase');

                Route::get('destroy/{ids}', [Ctrls\KeysController::class, 'destroy'])
                ->name('keys.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\KeysController::class, 'export'])
                ->name('keys.export');
            });
            


            // Pricing table
            Route::prefix('pricing_table')->group(function()
            {
                Route::get('/', [Ctrls\PricingTableController::class, 'index'])
                ->name('pricing_table');

                Route::get('create', [Ctrls\PricingTableController::class, 'create'])
                ->name('pricing_table.create');

                Route::post('store', [Ctrls\PricingTableController::class, 'store'])
                ->name('pricing_table.store');

                Route::get('edit/{id}', [Ctrls\PricingTableController::class, 'edit'])
                ->name('pricing_table.edit');

                Route::post('update/{id}', [Ctrls\PricingTableController::class, 'update'])
                ->name('pricing_table.update');

                Route::get('destroy/{ids}', [Ctrls\PricingTableController::class, 'destroy'])
                ->name('pricing_table.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\PricingTableController::class, 'export'])
                ->name('pricing_table.export');
            });
            



            // Users Subscriptions
            Route::prefix('users_subscriptions')->group(function()
            {
                Route::get('/', [Ctrls\UserSubscriptionsController::class, 'index'])
                ->name('users_subscriptions');

                Route::get('destroy/{ids}', [Ctrls\UserSubscriptionsController::class, 'destroy'])
                ->name('users_subscriptions.destroy');

                Route::post('users_create_send_renewal_payment_link', [Ctrls\UserSubscriptionsController::class, 'create_send_renewal_payment_link'])
                ->name('users_subscriptions.sendRenewalPaymentLink');
            });
            

            // Extensions
            Route::prefix('extensions')->group(function()
            {
                Route::get('/', [Ctrls\ExtensionsController::class, 'index'])
                ->name('extensions');

                Route::get('uninstall', [Ctrls\ExtensionsController::class, 'uninstall'])
                ->name('extensions.uninstall');

                Route::get('install', [Ctrls\ExtensionsController::class, 'install'])
                ->name('extensions.install');

                Route::post('install', [Ctrls\ExtensionsController::class, 'register'])
                ->name('extensions.register');
            });


            // Pages
            Route::prefix('pages')->group(function()
            {
                Route::get('/', [Ctrls\PagesController::class, 'index'])
                ->name('pages');

                Route::get('create', [Ctrls\PagesController::class, 'create'])
                ->name('pages.create');

                Route::post('store', [Ctrls\PagesController::class, 'store'])
                ->name('pages.store');

                Route::get('edit/{id}', [Ctrls\PagesController::class, 'edit'])
                ->name('pages.edit');

                Route::post('update/{id}', [Ctrls\PagesController::class, 'update'])
                ->name('pages.update');

                Route::get('destroy/{ids}', [Ctrls\PagesController::class, 'destroy'])
                ->name('pages.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\PagesController::class, 'export'])
                ->name('pages.export');

                Route::post('active', [Ctrls\PagesController::class, 'status'])
                ->name('pages.status');
            });



            // Support
            Route::prefix('support')->group(function()
            {
                Route::get('/', [Ctrls\SupportController::class, 'index'])
                ->name('support');

                Route::post('reply', [Ctrls\SupportController::class, 'create'])
                ->name('support.create');

                Route::get('destroy/{ids}', [Ctrls\SupportController::class, 'destroy'])
                ->name('support.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\SupportController::class, 'export'])
                ->name('support.export');

                Route::post('read', [Ctrls\SupportController::class, 'status'])
                ->name('support.status');
            });




            // Newsletter
            Route::prefix('subscribers')->group(function()
            {
                Route::get('/', [Ctrls\SubscribersController::class, 'index'])
                ->name('subscribers');

                Route::get('create_newsletter', [Ctrls\SubscribersController::class, 'create'])
                ->name('subscribers.newsletter.create');

                Route::get('get_template', [Ctrls\SubscribersController::class, 'get_template'])
                ->name('subscribers.newsletter.get_template');

                Route::post('send_newsletter', [Ctrls\SubscribersController::class, 'send'])
                ->name('subscribers.newsletter.send');

                Route::get('destroy/{ids}', [Ctrls\SubscribersController::class, 'destroy'])
                ->name('subscribers.destroy');

                Route::post('export', [Ctrls\SubscribersController::class, 'export'])
                ->name('subscribers.export');
            });

            
            

            // Posts
            Route::prefix('posts')->group(function()
            {
                Route::get('/', [Ctrls\PostsController::class, 'index'])
                ->name('posts');

                Route::get('create', [Ctrls\PostsController::class, 'create'])
                ->name('posts.create');

                Route::post('store', [Ctrls\PostsController::class, 'store'])
                ->name('posts.store');

                Route::get('edit/{id}', [Ctrls\PostsController::class, 'edit'])
                ->name('posts.edit');

                Route::post('update/{id}', [Ctrls\PostsController::class, 'update'])
                ->name('posts.update');

                Route::get('destroy/{ids}', [Ctrls\PostsController::class, 'destroy'])
                ->name('posts.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\PostsController::class, 'export'])
                ->name('posts.export');

                Route::post('active', [Ctrls\PostsController::class, 'status'])
                ->name('posts.status');
            });

            



            // Categories
            Route::prefix('categories')->group(function()
            {
                Route::get('/{for?}', [Ctrls\CategoriesController::class, 'index'])
                ->name('categories')
                ->where('for', '^(posts|products)$');

                Route::get('create', [Ctrls\CategoriesController::class, 'create'])
                ->name('categories.create');

                Route::post('store', [Ctrls\CategoriesController::class, 'store'])
                ->name('categories.store');

                Route::get('edit/{id}/{for?}', [Ctrls\CategoriesController::class, 'edit'])
                ->name('categories.edit')
                ->where('for', '^(posts|products)$');

                Route::post('update/{id}/{for?}', [Ctrls\CategoriesController::class, 'update'])
                ->name('categories.update')
                ->where('for', '^(posts|products)$');

                Route::get('destroy/{ids}/{for?}', [Ctrls\CategoriesController::class, 'destroy'])
                ->name('categories.destroy')
                ->where('for', '^(posts|products)$');

                Route::post('feature', [Ctrls\CategoriesController::class, 'feature'])
                ->name('categories.feature');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\CategoriesController::class, 'export'])
                ->name('categories.export');
            });
            



            // Coupons
            Route::prefix('coupons')->group(function()
            {
                Route::get('/', [Ctrls\CouponsController::class, 'index'])
                ->name('coupons');

                Route::get('create', [Ctrls\CouponsController::class, 'create'])
                ->name('coupons.create');

                Route::post('store', [Ctrls\CouponsController::class, 'store'])
                ->name('coupons.store');

                Route::get('edit/{id}', [Ctrls\CouponsController::class, 'edit'])
                ->name('coupons.edit');

                Route::post('update/{id}', [Ctrls\CouponsController::class, 'update'])
                ->name('coupons.update');

                Route::get('destroy/{ids}', [Ctrls\CouponsController::class, 'destroy'])
                ->name('coupons.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\CouponsController::class, 'export'])
                ->name('coupons.export');

                Route::post('generate', [Ctrls\CouponsController::class, 'generate'])
                ->name('coupons.generate');
            });

            



            // Users
            Route::prefix('users')->group(function()
            {
                Route::get('/', [Ctrls\UsersController::class, 'index'])
                ->name('users');

                Route::get('destroy/{ids}', [Ctrls\UsersController::class, 'destroy'])
                ->name('users.destroy');

                Route::post('status', [Ctrls\UsersController::class, 'status'])
                ->name('users.status');

                Route::post('notify', [Ctrls\UsersController::class, 'notify'])
                ->name('users.notify');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\UsersController::class, 'export'])
                ->name('users.export');

                Route::post('update-role', [Ctrls\UsersController::class, 'update_role']);
            });

            
            


            // Payment Links
            Route::prefix('payment_links')->group(function()
            {
                Route::get('/', [Ctrls\PaymentLinksController::class, 'index'])
                ->name('payment_links');

                Route::get('create', [Ctrls\PaymentLinksController::class, 'create'])
                ->name('payment_links.create');

                Route::post('store', [Ctrls\PaymentLinksController::class, 'store'])
                ->name('payment_links.store');

                Route::post('send', [Ctrls\PaymentLinksController::class, 'send'])
                ->name('payment_links.send');

                Route::post('item_licenses', [Ctrls\PaymentLinksController::class, 'item_licenses'])
                ->name('payment_links.item_licenses');

                Route::get('destroy/{ids}', [Ctrls\PaymentLinksController::class, 'destroy'])
                ->name('payment_links.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\PaymentLinksController::class, 'export'])
                ->name('payment_links.export');
            });
            



            // Custom routes
            Route::prefix('custom_routes')->group(function()
            {
                Route::get('/', [Ctrls\CustomRoutesController::class, 'index'])
                ->name('custom_routes');

                Route::get('create', [Ctrls\CustomRoutesController::class, 'create'])
                ->name('custom_routes.create');

                Route::post('store', [Ctrls\CustomRoutesController::class, 'store'])
                ->name('custom_routes.store');

                Route::get('edit/{id}', [Ctrls\CustomRoutesController::class, 'edit'])
                ->name('custom_routes.edit');

                Route::post('update/{id}', [Ctrls\CustomRoutesController::class, 'update'])
                ->name('custom_routes.update');

                Route::get('destroy/{ids}', [Ctrls\CustomRoutesController::class, 'destroy'])
                ->name('custom_routes.destroy');

                Route::post('active', [Ctrls\CustomRoutesController::class, 'status'])
                ->name('custom_routes.status');
            });


            // Faq
            Route::prefix('faq')->group(function()
            {
                Route::get('/', [Ctrls\FaqController::class, 'index'])
                ->name('faq');

                Route::get('create', [Ctrls\FaqController::class, 'create'])
                ->name('faq.create');

                Route::post('store', [Ctrls\FaqController::class, 'store'])
                ->name('faq.store');

                Route::get('edit/{id}', [Ctrls\FaqController::class, 'edit'])
                ->name('faq.edit');

                Route::post('update/{id}', [Ctrls\FaqController::class, 'update'])
                ->name('faq.update');

                Route::get('destroy/{ids}', [Ctrls\FaqController::class, 'destroy'])
                ->name('faq.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\FaqController::class, 'export'])
                ->name('faq.export');

                Route::post('active', [Ctrls\FaqController::class, 'status'])
                ->name('faq.status');
            });

            


            // Prepaid Credits
            Route::prefix('prepaid_credits')->group(function()
            {
                Route::get('/', [Ctrls\PrepaidCreditsController::class, 'index'])
                ->name('prepaid_credits');

                Route::get('create', [Ctrls\PrepaidCreditsController::class, 'create'])
                ->name('prepaid_credits.create');

                Route::post('store', [Ctrls\PrepaidCreditsController::class, 'store'])
                ->name('prepaid_credits.store');

                Route::get('edit/{id}', [Ctrls\PrepaidCreditsController::class, 'edit'])
                ->name('prepaid_credits.edit');

                Route::post('update/{id}', [Ctrls\PrepaidCreditsController::class, 'update'])
                ->name('prepaid_credits.update');

                Route::get('destroy/{ids}', [Ctrls\PrepaidCreditsController::class, 'destroy'])
                ->name('prepaid_credits.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\PrepaidCreditsController::class, 'export'])
                ->name('prepaid_credits.export');

                Route::post('sort', [Ctrls\PrepaidCreditsController::class, 'sort'])
                ->name('prepaid_credits.sort');
            });
            


            // User Prepaid Credits
            Route::prefix('users_prepaid_credits')->group(function()
            {
                Route::get('/', [Ctrls\UsersPrepaidCreditsController::class, 'index'])
                ->name('users_prepaid_credits');

                Route::get('destroy/{ids}', [Ctrls\UsersPrepaidCreditsController::class, 'destroy'])
                ->name('users_prepaid_credits.destroy');

                Route::post('update/{id}', [Ctrls\UsersPrepaidCreditsController::class, 'update'])
                ->name('users_prepaid_credits.update');
            });
            


            // Admin Profile
            Route::prefix('profile')->group(function()
            {
                Route::get('/', [Ctrls\AdminProfileController::class, 'edit'])
                ->name('profile.edit');
 
                Route::post('update', [Ctrls\AdminProfileController::class, 'update'])
                ->name('profile.update');
            });
            


            // Licenses Validation
            Route::get('validate-license', [Ctrls\LicenseValidatorController::class, 'index'])
            ->name('licenses_validation_form');



            // Transactions
            Route::prefix('transactions')->group(function()
            {
                Route::get('/', [Ctrls\TransactionsController::class, 'index'])
                ->name('transactions');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\TransactionsController::class, 'export'])
                ->name('transactions.export');

                # Create transaction for offline payment
                Route::get('create/{for?}', [Ctrls\TransactionsController::class, 'create'])
                ->name('transactions.create')
                ->where('for', '^(|subscriptions)$');

                # Store offline transaction
                Route::post('store/{for?}', [Ctrls\TransactionsController::class, 'store'])
                ->name('transactions.store')
                ->where('for', '^(|subscriptions)$');

                # Store offline transaction
                Route::post('store', [Ctrls\TransactionsController::class, 'store'])
                ->name('transactions.store');

                # Edit offline transaction
                Route::get('edit/{id}', [Ctrls\TransactionsController::class, 'edit'])
                ->name('transactions.edit');

                # Update offline transaction
                Route::post('edit/{id}', [Ctrls\TransactionsController::class, 'update'])
                ->name('transactions.update');

                # Mark offline transaction as refunded
                Route::get('{id}/mark_as_refunded', [Ctrls\TransactionsController::class, 'mark_as_refunded'])
                ->name('transactions.mark_as_refunded');

                # Update transaction Status and Refunded props
                Route::post('update_prop', [Ctrls\TransactionsController::class, 'update_prop'])
                ->name('transactions.update_prop');

                # Show transaction details
                Route::get('show/{id}', [Ctrls\TransactionsController::class, 'show'])
                ->name('transactions.show');

                # Refund transaction
                Route::post('refund', [Ctrls\TransactionsController::class, 'refund'])
                ->name('transactions.refund');

                # Refund Iyzico Transaction
                Route::any('refund/iyzico/{payment_id}', [Ctrls\TransactionsController::class, 'refund_iyzico'])
                ->name('transactions.refund_iyzico');

                # Remove transaction
                Route::get('destroy/{ids}', [Ctrls\TransactionsController::class, 'destroy'])
                ->name('transactions.destroy');
            });

            


            // Transactions notes
            Route::prefix('transaction_notes')->group(function()
            {
                Route::get('/', [Ctrls\TransactionsNotesController::class, 'index'])
                ->name('transaction_notes.index');

                Route::post('/', [Ctrls\TransactionsNotesController::class, 'reply'])
                ->name('transaction_notes.reply');

                Route::post('show/{id}', [Ctrls\TransactionsNotesController::class, 'show'])
                ->name('transaction_notes.show');

                Route::get('destroy/{ids}', [Ctrls\TransactionsNotesController::class, 'destroy'])
                ->name('transaction_notes.destroy');
            });
            


            // Comments
            Route::prefix('comments')->group(function()
            {
                Route::get('/', [Ctrls\CommentsController::class, 'index'])
                ->name('comments');

                Route::post('approve', [Ctrls\CommentsController::class, 'status'])
                ->name('comments.status');

                Route::get('/destroy/{ids}', [Ctrls\CommentsController::class, 'destroy'])
                ->name('comments.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\CommentsController::class, 'export'])
                ->name('comments.export');
            });
            



            // Affiliate
            Route::prefix('affiliate')->group(function()
            {
                Route::get('balances', [Ctrls\CashoutsController::class, 'balances'])
                ->name('affiliate.balances');

                Route::get('balances/destroy/{ids}', [Ctrls\CashoutsController::class, 'destroy_balances'])
                ->name('affiliate.destroy_balances');

                Route::get('cashouts', [Ctrls\CashoutsController::class, 'cashouts'])
                ->name('affiliate.cashouts');

                Route::get('cashouts/destroy/{ids}', [Ctrls\CashoutsController::class, 'destroy_cashouts'])
                ->name('affiliate.destroy_cashouts');

                Route::post('mark_as_paid', [Ctrls\CashoutsController::class, 'mark_as_paid'])
                ->name('affiliate.mark_as_paid');

                Route::post('transfer_to_paypal', [Ctrls\CashoutsController::class, 'transfer_to_paypal'])
                ->name('affiliate.transfer_to_paypal');
            });
            



            // Reviews
            Route::prefix('reviews')->group(function()
            {
                Route::get('/', [Ctrls\ReviewsController::class, 'index'])
                ->name('reviews');

                Route::post('approve', [Ctrls\ReviewsController::class, 'status'])
                ->name('reviews.status');

                Route::get('destroy/{ids}', [Ctrls\ReviewsController::class, 'destroy'])
                ->name('reviews.destroy');

                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\ReviewsController::class, 'export'])
                ->name('reviews.export');
            });
            


            // Reviews
            Route::prefix('searches')->group(function()
            {
                Route::get('/', [Ctrls\SearchesController::class, 'index'])
                ->name('searches');

                Route::get('destroy/{ids}', [Ctrls\SearchesController::class, 'destroy'])
                ->name('searches.destroy');
                
                Route::match(['get', 'post'], 'export/{ids?}', [Ctrls\SearchesController::class, 'export'])
                ->name('searches.export');
            });
            


            // Admin notification
            Route::prefix('admin-notifs')->group(function()
            {
                Route::get('/', [Ctrls\AdminNotifsController::class, 'index'])
                ->name('admin_notifs');
                
                Route::post('mark_as_read', [Ctrls\AdminNotifsController::class, 'mark_as_read'])
                ->name('admin_notifs.mark_as_read');
            });
            




            // Settings
            Route::post('clear_cache', [Ctrls\SettingsController::class, 'clear_cache']);

            Route::post('generate_fake_profiles', [Ctrls\SettingsController::class, 'generate_fake_profiles']);

            Route::get('list_fake_profiles', [Ctrls\SettingsController::class, 'list_fake_profiles']);

            Route::post('delete_fake_profiles', [Ctrls\SettingsController::class, 'delete_fake_profiles']);

            Route::prefix('settings')->group(function()
            {
                Route::get('{settings_name}', [Ctrls\SettingsController::class, 'index'])
                ->where('settings_name', 
                  '^(bulk_upload|affiliate|general|maintenance|cache|payments|adverts|search_engines|mailer|files_host|social_login|adverts|chat|translations|captcha|database)$')
                ->name('settings');

                Route::post('{settings_name}/update', [Ctrls\SettingsController::class, 'update'])
                ->where('settings_name', '^(bulk_upload|affiliate|general|maintenance|payments|adverts|search_engines|mailer|files_host|social_login|adverts|chat|translations|captcha|database)$')
                ->name('settings.update');

                Route::post('check_mailer_connection', [Ctrls\SettingsController::class, 'check_mailer_connection'])
                ->name('settings.check_mailer_connection');

                Route::post('remove_search_cover', [Ctrls\SettingsController::class, 'remove_search_cover'])
                ->name('settings.remove_search_cover');

                Route::post('files_host/dropbox_get_current_user', [Ctrls\SettingsController::class, 'dropbox_get_current_user'])
                ->name('dropbox_get_current_user');

                Route::post('files_host/yandex_disk_get_refresh_token', [Ctrls\SettingsController::class, 'yandex_disk_get_refresh_token'])
                ->name('yandex_disk_get_refresh_token');

                Route::post('files_host/test_amazon_s3_connection', [Ctrls\SettingsController::class, 'test_amazon_s3_connection'])
                ->name('test_amazon_s3_connection');

                Route::post('files_host/test_wasabi_connection', [Ctrls\SettingsController::class, 'test_wasabi_connection'])
                ->name('test_wasabi_connection');

                Route::post('files_host/test_google_cloud_storage_connection', [Ctrls\SettingsController::class, 'test_google_cloud_storage_connection'])
                ->name('test_google_cloud_storage_connection');

                Route::post('translations/get_translation', [Ctrls\SettingsController::class, 'get_translation'])
                ->name('get_translation');

                Route::post('database/execute_db_query', [Ctrls\SettingsController::class, 'execute_db_query'])
                ->name('execute_db_query');

                Route::get('update_products_extension', [Ctrls\SettingsController::class, 'update_products_extension'])
                ->name('update_products_extension');
            });
            


            // Fixes
            Route::prefix('fixes')->group(function()
            {
                Route::get('/', [Ctrls\FixesController::class, 'index'])
                ->name('fixes.index');

                Route::post('install', [Ctrls\FixesController::class, 'install'])
                ->name('fixes.install');  
            });
        });
    });
});