<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\{ DB, Route, Blade, File, View };
use App\Models\{ Setting, Page, Category, License, Product, Statistic };
use App\Http\Controllers\AdminNotifsController;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if(config('app.installed') === true && !app()->runningInConsole())
        {
          Blade::directive('cards', function($args) 
          {
            return "<?php cards({$args}); ?>";
          });

          Paginator::defaultView('vendor.pagination.semantic-ui');

          Paginator::defaultSimpleView('vendor.pagination.simple-semantic-ui');

          config(['mmdb_reader' => new \GeoIp2\Database\Reader(base_path('maxmind/GeoLite2-Country.mmdb'))]);

          DB::statement("SET sort_buffer_size = ?", [(int)env('DB_SORT_BUFFER_SIZE', 2) * 1048576]);
          DB::statement("SET sql_mode = ?", [(string)env('DB_SQL_MODE', 'STRICT_TRANS_TABLES')]);

          if(!\Cache::has('reactions_item_type_created'))
          {
            if(!\DB::select("SHOW COLUMNS FROM `reactions` LIKE 'item_type'"))
            {
              \DB::statement("ALTER TABLE `reactions` ADD `item_type` VARCHAR(255) NULL DEFAULT 'comment'");

              \Cache::forever('reactions_item_type_created', 'created');
            }
          }
          
          $settings = Setting::first()->toArray();

          foreach($settings as $k => &$v)
          {
            $v = json_decode($v, true) ? json_decode($v, true) : $v;

            if(is_array($v))
            {
              foreach($v as &$sub_v)
              {
                $sub_v = is_array($sub_v) ? $sub_v : (json_decode($sub_v, true) ? json_decode($sub_v, true) : $sub_v);
              }
            }
          }

          $mail  = collect($settings['mailer']['mail'] ?? []);
          $pages =  Page::useIndex('active')->select('name', 'slug', 'deletable')->where('active', 1)->get()->toArray();
          $pages =  array_combine(array_column($pages, 'slug'), $pages);

          $settings['social_login'] = json_decode(str_ireplace('secret_id', 'client_secret', json_encode($settings['social_login'] ?? [])), TRUE);

          if(isset($settings['captcha']))
          {
            $captcha = array_merge(config('captcha'), $settings['captcha']['google']);
            
            $captcha['default'] = $settings['captcha']['mewebstudio'];
            $captcha['enable_on'] = array_filter(explode(',', $settings['captcha']['enable_on']));

            config(['captcha' => $captcha]);
          }

          if($currencies = array_filter(explode(',', ($settings['payments']['currencies'] ?? null))))
          {
            $_currencies = array_filter(config('payments.currencies', []), function($v, $k) use ($currencies)
                                                {
                                                  return in_array(mb_strtolower($k), $currencies);
                                                }, ARRAY_FILTER_USE_BOTH);

            ksort($_currencies);

            $settings['payments']['currencies'] = $_currencies;
          }

          parse_str(request()->getQueryString(), $url_params);

          $settings['general']['url_params'] = $url_params;

          $maintenance = $settings['maintenance'] ?? [];

          config([
            'services'   => array_merge(config('services', []), $settings['social_login']  ?? []),
            'payments'   => array_merge(config('payments', []), $settings['payments']  ?? []),
            'affiliate'  => $settings['affiliate'] ?? [],
            'adverts'    => $settings['adverts'] ?? [],
            'chat'       => $settings['chat'] ?? [],
            'mail.mailers.smtp'   => array_merge(config('mail.mailers.smtp'), $mail->except('from')->toArray()),
            'mail.from'           => $mail->only('from')->values()->first() ?? [],
            'mail.reply_to'       => $mail->only('reply_to')->values()->first(),
            'mail.forward_to'     => $mail->only('forward_to')->values()->first(),
            'app'        => array_merge(config('app', []), $settings['general'] ?? [], $settings['search_engines'] ?? [], ['version' => env('APP_VERSION', '4.0.0'), 'maintenance' => $maintenance]),
            'filehosts'  => array_merge(config('filehosts', []), $settings['files_host'] ?? []),
            'categories' => Category::products(),
            'pages'      => $pages,
            'popular_categories' => Category::popular(),
            'licenses'   => License::select('id', 'name', 'regular')->get(),
            "extensions" => json_decode(@file_get_contents(base_path("extensions/keys.json")), true) ?? [],
          ]);

          foreach(config('exchangers', []) as $name => $config)
          {
            config([
              "exchangers.{$name}.api_key" => config("payments.exchangers.{$name}.api_key", null),
              "exchangers.{$name}.enabled" => config("payments.exchanger") == $name
            ]);
          }

          $counters = array_filter(explode(',', config('app.counters')));
          $counters = array_combine(array_values($counters), array_fill(0, count($counters), 0));

          $cashout_methods = explode(',', config('affiliate.cashout_methods'));
          $cashout_methods = array_combine(array_values($cashout_methods), array_values($cashout_methods));
          
          $template = config('app.template');

          $langs = explode(',', $settings['general']['langs'] ?? config('app.locale'));

          $supportedLocales = config('laravellocalization.supportedLocales');

          foreach($supportedLocales as $locale => $props)
          {
            if(!in_array($locale, $langs))
              unset($supportedLocales[$locale]);
          }

          $payment_procs = collect(config('payments.gateways', []))->where('enabled', '===', 'on')->toArray();

          $pay_what_you_want = config('payments.pay_what_you_want');

          $pay_what_you_want['for'] = explode(',', $pay_what_you_want['for']);
          $pay_what_you_want['for'] = array_combine(array_values($pay_what_you_want['for']), array_values($pay_what_you_want['for']));
          
          $minimum_payments = array_column($payment_procs, 'minimum');
          $minimum_payments = (empty($minimum_payments) || count($minimum_payments) < count($payment_procs)) ? array_fill(0, count($payment_procs), 0) : $minimum_payments;
  

          $show_rating = array_filter(explode(',', config('app.show_rating')));
          $show_rating = count($show_rating) >= 1 
                         ? array_combine(array_values($show_rating), array_fill(0, count($show_rating), 1)) 
                         : [];

          $show_sales = array_filter(explode(',', config('app.show_sales')));
          $show_sales = count($show_sales) >= 1 
                        ? array_combine(array_values($show_sales), array_fill(0, count($show_sales), 1)) 
                        : [];

          $fake_purchases = array_filter(explode(',', config('app.fake_purchases.pages', '')));
          $fake_purchases = array_combine($fake_purchases, $fake_purchases);
          $fake_purchases_interval = explode(',', config('app.fake_purchases.interval', '300,900'), 2);

          config([
            'payments_gateways' => array_merge($payment_procs, ['n-a' => ['enabled' => 1, 'fee' => 0, "auto_exchange_to" => null, "minimum" => null, "name" => "n-a"]]),
            'fees' => array_combine(array_keys($payment_procs), array_column($payment_procs, 'fee')),
            'mimimum_payments' => array_combine(array_keys($payment_procs), $minimum_payments),
            'pay_what_you_want' => $pay_what_you_want,
            'app.show_rating' => $show_rating,
            'app.show_sales' => $show_sales,
            'app.fake_purchases.pages' => $fake_purchases,
            'app.fake_purchases.interval' => ['min' => $fake_purchases_interval[0] ?? '300', 'max' => $fake_purchases_interval[1] ?? '900'],
            'laravellocalization.supportedLocales' => $supportedLocales, 
            'langs' => $langs,
            'app.locale' => config('app.default_lang', $langs[0]),
            'app.top_cover' => config("app.{$template}_top_cover"),
            'app.top_cover_mask' => config("app.{$template}_top_cover_mask"),
            'affiliate.cashout_methods' => $cashout_methods,
            'app.counters' => $counters,
            'app.permalink_url_identifer' => config('app.permalink_url_identifer', 'permalink'),
          ]);

          if(!preg_match('/^home\..+/i', Route::currentRouteName()))
          {
            $admin_notifications = AdminNotifsController::latest();

            View::share(compact('admin_notifications'));
          }

          if($timezone = config('app.timezone'))
          {            
            date_default_timezone_set($timezone);
            ini_set('date.timezone', $timezone);
          }

          preg_match('/^\(GMT(?P<offset>.+\d+:\d+)\) \w+$/i', config("app.timezones.{$timezone}"), $matches);
          
          $timezone = $matches['offset'] ?? '+00:00';
          
          DB::statement('SET time_zone = ?', [(string)env('DB_TIMEZONE', $timezone)]);

          foreach(config('payments.gateways', []) as $name => $config)
          {
            config(["payment_gateways.{$name}.fields.order.value" => $config['order'] ?? 0]);  
          }

          config(["payment_gateways" => collect(config('payment_gateways'))->sortBy('fields.order.value')->where('name', '!=', null)->toArray()]);

          $templates = glob(resource_path('views/front/*', GLOB_ONLYDIR));
          $templates = array_filter($templates, 'is_dir');
          $base_path = resource_path('views/front/');
          $templates = str_ireplace($base_path, '', $templates);

          View::share(['templates' => $templates]);
        }
    }
}
