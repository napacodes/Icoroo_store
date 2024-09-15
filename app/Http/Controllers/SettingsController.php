<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Setting, Category, Product, Product_Price, License };
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\{ DB, File, Config, Cache, View };
use App\Libraries\{ GoogleDrive, DropBox, YandexDisk, AmazonS3, Wasabi, GoogleCloudStorage };
use League\Csv\Reader;
use League\Csv\Statement;
use Intervention\Image\Facades\Image;
use Google\Client;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      if(strtolower($request->settings_name) === 'translations')
      {
        $langs = File::directories(resource_path("lang/"));
        $langs = array_filter(preg_replace('/.+(\/|\\\)([\w-]+)$/i', '$2', $langs));
        $base = json_decode(File::get(resource_path("lang/en.json")), true);

        foreach($base as $k => $v)
        {
          $base[$k] = preg_replace('/(:[a-z_%]+)/i', '<span class="param">$1</span>', $k);
        }

        $base = array_flip($base);
        $base = array_combine(array_map("base64_encode", array_keys($base)), array_values($base));

        view()->share(compact('base', 'langs'));

        $settings = [];
      }
      elseif(strtolower($request->settings_name) == 'cache')
      {
         $settings = [];
      }
      elseif(strtolower($request->settings_name) == 'bulk_upload')
      {
        $settings = (object)['columns' => config('app.bulk_upload_columns', [])];
      }
      else
      {
        $settings = Setting::select($request->settings_name)->first()->{$request->settings_name};

        $settings = json_decode($settings) ?? (object)[];

        if(strtolower($request->settings_name) === 'general')
        {
          $templates = glob(resource_path('views/front/*', GLOB_ONLYDIR));
          $templates = array_filter($templates, 'is_dir');

          $base_path = resource_path('views/front/');
          $templates = str_ireplace($base_path, '', $templates);

          $langs = File::directories(resource_path("lang/"));
          $langs = array_filter(preg_replace('/.+(\/|\\\)([\w-]+)$/i', '$2', $langs));

          $settings->homepage_items = json_decode($settings->homepage_items ?? null);
          $settings->auto_approve   = json_decode($settings->auto_approve ?? null); 
          $settings->admin_notifications = json_decode($settings->admin_notifications ?? null);
          $settings->realtime_views = json_decode($settings->realtime_views ?? null);
          $settings->cookie         = json_decode($settings->cookie ?? null);
          $settings->subscriptions  = $settings->subscriptions ?? null;
          $settings->subscriptions  = is_object($settings->subscriptions) ? $settings->subscriptions : json_decode($settings->subscriptions);
          $settings->fake_purchases = json_decode($settings->fake_purchases ?? null);
          $settings->prepaid_credits = json_decode($settings->prepaid_credits ?? null);
          $settings->js_css_code     = json_decode($settings->js_css_code ?? null);
          $settings->invoice         = json_decode($settings->invoice ?? null);

          View::share([
            'bots' => (new \Jaybizzle\CrawlerDetect\Fixtures\Crawlers())->getAll(),
            'langs' => $langs, 
            'templates' => $templates,
            'product_card_cover_masks' => array_map('basename', glob(public_path("assets/images/card-mask*"))),
          ]);
        }
        elseif(strtolower($request->settings_name) === 'payments')
        {
          // delete_cached_view("back/settings/payments.blade.php");

          $payments_conf = include(config_path("payments.php"));

          view()->share('currencies', $payments_conf['currencies']);       
        }
      }

      return view("back.settings.index", ['view' => $request->settings_name, 'settings' => $settings]);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
      if(file_exists(app()->getCachedConfigPath()))
        unlink(app()->getCachedConfigPath());
      
      call_user_func("Self::update_{$request->settings_name}", $request);

      $params = ['settings_name' => $request->settings_name];

      if($request->query('tab'))
      {
        $params['tab'] = $request->query('tab');
      }

      return redirect()->route('settings', $params)->withInput()->with(['settings_message' => __('Changes saved')]);
    }



    private static function update_general(Request $request)
    {
      $templates = implode(',', File::glob(resource_path('views/front/*', GLOB_ONLYDIR)));

      $base_path = resource_path('views/front/');

      $templates = str_ireplace($base_path, '', $templates);

      $request->validate([
        'name' => 'nullable|string',
        'title' => 'nullable|string',
        'description' => 'nullable|string',
        'email' => 'nullable|email',
        'keywords' => 'nullable|string',
        'items_per_page' => 'nullable|min:0|integer',
        'html_editor' => 'nullable|string|in:tinymce,summernote,tinymce_bbcode',
        'favicon' => 'nullable|image',
        'logo' => 'nullable|image',
        'cover' => 'nullable|image',
        'watermark' => 'nullable',
        'top_cover' => 'nullable',
        'search_header' => 'nullable|string',
        'masonry_layout' => 'nullable|string|in:0,1',
        'search_subheader' => 'nullable|string',
        'blog.title' => 'nullable|string',
        'blog.description' => 'nullable|string',
        'blog.enabled' => 'nullable|in:0,1',
        'blog.disqus' => 'nullable|in:0,1',
        'products_by_country_city' => 'nullable|in:0,1',
        'subscriptions.enabled' => 'nullable|in:0,1',
        'subscriptions.accumulative' => 'nullable|in:0,1',
        'env' => ['regex:/^(production|local)$/i', 'required'],
        'debug' => 'boolean|required',
        'facebook' => 'nullable|string',
        'tiktok' => 'nullable|string',
        'twitter' => 'nullable|string',
        'pinterest' => 'nullable|string',
        'youtube' => 'nullable|string',
        'tumblr' => 'nullable|string',
        'fb_app_id' => 'nullable|string',
        'cookie' => 'nullable|array',
        'template' => "required|string|in:{$templates}",
        'fullwide' => "nullable|numeric|in:0,1",
        'categories_on_homepage' => "nullable|numeric|in:0,1",
        'timezone' => ['required', \Illuminate\Validation\Rule::in(array_keys(config('app.timezones')))],
        'fonts' => 'nullable|array',
        'top_cover_color' => 'nullable|string',
        'top_cover_mask' => 'nullable',
        'users_notif' => 'nullable|string',
        'recently_viewed_items' => 'nullable|string:in:0,1',
        'admin_notifications' => 'nullable|array',
        'admin_notifications.comments' => 'nullable|string|in:0,1',
        'admin_notifications.reviews' => 'nullable|string|in:0,1',
        'admin_notifications.sales' => 'nullable|string|in:0,1',
        'enable_comments' => 'nullable|string|in:0,1',
        'enable_reviews' => 'nullable|string|in:0,1',
        'enable_reactions_on_comments' => 'nullable|string|in:0,1',
        'enable_subcomments' => 'nullable|string|in:0,1',
        'purchase_code' => 'required|string',
        'email_verification' => 'nullable|in:0,1',
        'auto_approve.*' => 'nullable|array',
        'auto_approve.reviews' => 'nullable|string|in:0,1',
        'auto_approve.support' => 'nullable|string|in:0,1',
        'homepage_items' => 'array|nullable',
        'randomize_homepage_items' => 'nullable|string|in:0,1',
        'fake_purchases.enabled' => 'nullable|string|in:0,1',
        'permalink_url_identifer' => 'nullable|string|max:255|not_in:post,item,page',
        'enable_upload_links' => 'nullable|numeric|in:0,1',
        'enable_data_cache' => 'nullable|numeric|in:0,1',
        'color_cursor' => 'nullable|numeric|in:0,1',
        "authorized_bots" => "nullable|string",
        "user_views_per_minute" => "nullable|numeric|gt:0",
        "available_via_subscriptions_only_message" => 'nullable|string',
        "force_download" => 'nullable|numeric|in:0,1',
        "authentication_required_to_download_free_items" => 'nullable|numeric|in:0,1',
        "show_add_to_cart_button_on_the_product_card" => 'nullable|numeric|in:0,1',
        "show_badges_on_the_product_card" => 'nullable|numeric|in:0,1',
        "generate_download_links_for_missing_files" => 'nullable|numeric|in:0,1',
        "report_errors" => 'nullable|numeric|in:0,1',
        "email_verification_required" => 'nullable|numeric|in:0,1',
        "two_factor_authentication" => 'nullable|numeric|in:0,1',
        'two_factor_authentication_expiry' => 'nullable|numeric|gte:0',
        "product_card_cover_mask" => 'nullable|string',
        "facebook_pixel" => 'nullable|string',
        "langs" => ['nullable', function($attribute, $value, $fail)
                                {
                                  if($langs = array_filter(explode(',', $value)))
                                  {
                                    foreach($langs as $lang)
                                    {
                                      if($lang !== 'en' && !is_file(resource_path("lang/{$lang}.json")))
                                      {
                                        File::put(resource_path("lang/{$lang}.json"), '{}');
                                      }
                                    }
                                  }
                                }],
        "default_lang" => "nullable|string|in:".implode(',', array_keys(config('laravellocalization.supportedLocales'))),
      ]);

      $settings = Setting::first();

      $general_settings = json_decode($settings->general) ?? new \stdClass;

      $general_settings->langs          = $request->post('langs', config('app.locale'));
      $general_settings->default_lang   = $request->input('default_lang', config('app.locale'));
      $general_settings->name           = $request->name;
      $general_settings->title          = $request->title;
      $general_settings->description    = $request->description;
      $general_settings->email          = $request->email;
      $general_settings->keywords       = $request->keywords;
      $general_settings->items_per_page = $request->items_per_page;
      $general_settings->html_editor    = $request->html_editor ?? 'summernote';
      $general_settings->env            = $request->env;
      $general_settings->template       = $request->template;
      $general_settings->fullwide       = $request->fullwide;
      $general_settings->categories_on_homepage = $request->categories_on_homepage;
      $general_settings->js_css_code    = json_encode($request->js_css_code);
      $general_settings->debug          = $request->debug;
      $general_settings->masonry_layout = $request->masonry_layout;
      $general_settings->timezone       = $request->timezone;
      $general_settings->facebook       = $request->facebook;
      $general_settings->tiktok         = $request->tiktok;
      $general_settings->twitter        = $request->twitter;
      $general_settings->pinterest      = $request->pinterest;
      $general_settings->youtube        = $request->youtube;
      $general_settings->tumblr         = $request->tumblr;
      $general_settings->fb_app_id      = $request->fb_app_id;
      $general_settings->facebook_pixel = $request->facebook_pixel;
      $general_settings->search_header    = $request->search_header;
      $general_settings->search_subheader = $request->search_subheader;
      $general_settings->blog             = $request->blog;
      $general_settings->products_by_country_city = $request->products_by_country_city;
      $general_settings->subscriptions    = json_encode($request->subscriptions);
      $general_settings->users_notif      = $request->users_notif ?? '';
      $general_settings->admin_notifications   = json_encode($request->admin_notifications);
      $general_settings->purchase_code         = $request->purchase_code;
      $general_settings->email_verification    = $request->email_verification;
      $general_settings->fonts                 = $request->fonts;
      $general_settings->auto_approve          = json_encode($request->auto_approve);
      $general_settings->homepage_items        = json_encode($request->homepage_items);
      $general_settings->can_delete_own_comments      = $request->can_delete_own_comments;
      $general_settings->default_product_type         = $request->default_product_type ?? '-';
      $general_settings->recently_viewed_items        = $request->recently_viewed_items;
      $general_settings->randomize_homepage_items     = $request->randomize_homepage_items;
      $general_settings->direct_download_links        = $request->direct_download_links;
      $general_settings->show_rating                  = $request->show_rating;
      $general_settings->show_sales                   = $request->show_sales;
      $general_settings->registration_fields          = $request->registration_fields;
      $general_settings->required_registration_fields = $request->required_registration_fields;
      $general_settings->show_streaming_player        = $request->show_streaming_player ?? '0';
      $general_settings->enable_comments              = $request->enable_comments;
      $general_settings->enable_reviews               = $request->enable_reviews;
      $general_settings->enable_reactions_on_comments = $request->enable_reactions_on_comments;
      $general_settings->enable_subcomments     = $request->enable_subcomments;
      $general_settings->realtime_views         = json_encode($request->realtime_views);
      $general_settings->counters               = $request->counters;
      $general_settings->fake_counters          = $request->fake_counters;
      $general_settings->fake_purchases         = json_encode($request->fake_purchases);
      $general_settings->permalink_url_identifer = $request->permalink_url_identifer;
      $general_settings->enable_upload_links  = $request->enable_upload_links;
      $general_settings->invoice              = json_encode($request->invoice);
      $general_settings->enable_data_cache    = $request->enable_data_cache;
      $general_settings->color_cursor         = $request->color_cursor;
      $general_settings->authorized_bots      = $request->authorized_bots;
      $general_settings->allow_download_in_test_mode = $request->allow_download_in_test_mode;
      $general_settings->user_views_per_minute = $request->user_views_per_minute;
      $general_settings->available_via_subscriptions_only_message = $request->available_via_subscriptions_only_message;
      $general_settings->authentication_required_to_download_free_items = $request->authentication_required_to_download_free_items;
      $general_settings->show_add_to_cart_button_on_the_product_card = $request->show_add_to_cart_button_on_the_product_card;
      $general_settings->force_download = $request->force_download;
      $general_settings->generate_download_links_for_missing_files = $request->generate_download_links_for_missing_files;
      $general_settings->show_badges_on_the_product_card = $request->show_badges_on_the_product_card;
      $general_settings->product_card_cover_mask = $request->product_card_cover_mask;
      $general_settings->report_errors = $request->report_errors;
      $general_settings->email_verification_required = $request->email_verification_required;
      $general_settings->two_factor_authentication = $request->two_factor_authentication;
      $general_settings->two_factor_authentication_expiry = $request->two_factor_authentication_expiry;

      $prepaid_credits = $request->prepaid_credits;

      $prepaid_credits['expires_in'] = $request->input("prepaid_credits.expires_in_days");
      
      unset($prepaid_credits['expires_in_days']);

      $general_settings->prepaid_credits = json_encode($prepaid_credits);

      $cookie = [
        'text'       => $request->input('cookie.text'),
        'background' => $request->input('cookie.background.raw') ?? $request->input('cookie.background.picker'),
        'color'      => $request->input('cookie.color.raw') ?? $request->input('cookie.color.picker'),
        'button_bg'  => $request->input('cookie.button_bg.raw') ?? $request->input('cookie.button_bg.picker')
      ];

      $general_settings->cookie = mb_strlen(strip_tags($request->input('cookie.text'))) ? json_encode($cookie) : null;



      if(env('PURCHASE_CODE') !== $request->purchase_code)
      {
        update_env_var('PURCHASE_CODE', wrap_str($request->purchase_code));
      }

      if($favicon = $request->file('favicon'))
      {
        $ext = $favicon->getClientOriginalExtension();
        
        if($favicon = $request->favicon->storeAs('images', "favicon.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/favicon.*')) as $_favicon)
          {
            if(pathinfo($_favicon, PATHINFO_BASENAME) != "favicon.{$ext}")
            {
              @unlink($_favicon);
              break;
            }
          }

          $general_settings->favicon = pathinfo($favicon, PATHINFO_BASENAME);
        }
      }

      if($logo = $request->file('logo'))
      {
        $ext = $logo->getClientOriginalExtension();
        
        if($logo = $logo->storeAs('images', "{$request->template}_logo.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path("storage/images/{$request->template}_logo.*")) as $_logo)
          {
            if(pathinfo($_logo, PATHINFO_BASENAME) != "{$request->template}_logo.{$ext}")
            {
              @unlink($_logo);
              break;
            }
          }

          $general_settings->logo = pathinfo($logo, PATHINFO_BASENAME);
        }
      }



      if($cover = $request->file('cover'))
      {
        $ext = $cover->getClientOriginalExtension();

        if($cover = $request->cover->storeAs('images', "cover.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/cover.*')) as $_cover)
          {
            if(pathinfo($_cover, PATHINFO_BASENAME) != "cover.{$ext}")
            {
              @unlink($_cover);
              break;
            }
          }

          $general_settings->cover = pathinfo($cover, PATHINFO_BASENAME);
        }
      }


      if($watermark = $request->file('watermark'))
      {
        $ext = $watermark->getClientOriginalExtension();

        if($watermark = $watermark->storeAs('images', "watermark.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/watermark.*')) as $_watermark)
          {
            if(pathinfo($_watermark, PATHINFO_BASENAME) != "watermark.{$ext}")
            {
              @unlink($_watermark);
              break;
            }
          }

          $general_settings->watermark = pathinfo($watermark, PATHINFO_BASENAME);
        }
      }
      elseif(!$request->post('watermark'))
      {
        $watermark_path = public_path("storage/images/{$general_settings->watermark}");

        if(is_file($watermark_path))
        {
          File::delete($watermark_path);

          $general_settings->watermark = null;
        }
      }

      if($top_covers = $request->file('top_cover'))
      {        
        foreach($top_covers as $k => $top_cover)
        {
          $ext = $top_cover->getClientOriginalExtension();

          if($top_cover = $top_cover->storeAs('images', "{$k}_top_cover.{$ext}", ['disk' => 'public']))
          {
            foreach(glob(public_path("storage/images/{$k}_top_cover.*")) as $_top_cover)
            {
              $general_settings->{"{$k}_top_cover"} = pathinfo($top_cover, PATHINFO_BASENAME);
              
              if(pathinfo($_top_cover, PATHINFO_BASENAME) != "{$k}_top_cover.{$ext}")
              {
                @unlink($_top_cover);
                break;
              }
            }
          }
        }
      }
      else
      {
        foreach(['tendra', 'axies'] as $name)
        {
          if(!$top_cover = $request->input("top_cover.{$name}"))
          {
            $attr = "{$name}_top_cover";
            $top_cover_path = public_path("storage/images/{$general_settings->$attr}");

            if(is_file($top_cover_path))
            {
              File::delete($top_cover_path);

              $general_settings->$attr = null;
            }
          }
        }
      }

      if($blog_cover = $request->file('blog_cover'))
      {
        $ext = $blog_cover->getClientOriginalExtension();

        if($blog_cover = $blog_cover->storeAs('images', "blog_cover.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/blog_cover.*')) as $_blog_cover)
          {
            if(pathinfo($_blog_cover, PATHINFO_BASENAME) != "blog_cover.{$ext}")
            {
              @unlink($_blog_cover);
              break;
            }
          }

          $general_settings->blog_cover = pathinfo($blog_cover, PATHINFO_BASENAME);
        }
      }

      $settings->general = json_encode($general_settings);

      $settings->save();
    }




    private static function update_mailer(Request $request)
    {
      $request->validate([
        'mailer.mail.username'        => 'string|required|bail',
        'mailer.mail.host'            => 'required',
        'mailer.mail.password'        => 'required',
        'mailer.mail.port'            => 'required',
        'mailer.mail.encryption'      => 'nullable',
        'mailer.mail.reply_to'        => 'nullable|email',
        'mailer.mail.forward_to'      => 'nullable|string',
        'mailer.mail.use_queue'       => 'nullable|in:0,1'
      ]);
      
      $mailer = $request->mailer;

      $mailer['mail']['forward_to'] = preg_replace('/\s+/i', '', $mailer['mail']['forward_to']);
      
      $mailer['mail']['from'] = ['name' => config('app.name'), 'address' => $request->mailer['mail']['username'] ?? 'example@gmail.com'];

      Setting::first()->update(['mailer' => json_encode($mailer)]);
    }



    private static function update_affiliate(Request $request)
    {
      $request->validate([
        'affiliate.enabled' => 'string|nullable|in:0,1',
        'affiliate.commission' => 'numeric|gt:0|nullable|required_if:affiliate.enabled,1',
        'affiliate.expire' => 'numeric|gt:0|nullable|required_if:affiliate.enabled,1',
        'affiliate.cashout_description' => 'string|nullable|required_if:affiliate.enabled,1',
        'affiliate.cashout_methods' => 'string|nullable|required_if:affiliate.enabled,1',
        'affiliate.minimum_cashout.bank_transfer' => 'nullable|numeric',
        'affiliate.minimum_cashout.paypal' => 'nullable|numeric'
      ],
      ['required_if' => __(':attribute is required if affiliate is enabled.')]);

      Setting::first()->update(['affiliate' => json_encode($request->affiliate)]);
    }




    public function check_mailer_connection(Request $request)
    {
      $request->validate([
        'mailer.mail.username'        => 'string|required|bail',
        'mailer.mail.host'            => 'required',
        'mailer.mail.password'        => 'required',
        'mailer.mail.port'            => 'required',
        'mailer.mail.encryption'      => 'nullable'
      ]);

      $config = array_merge(config('mail.mailers.smtp', []), $request->input('mailer.mail'));

      config(['mail.mailers.smtp' => $config]);

      try
      {
          \Mail::raw('Test message', function($message) use($request)
          {
            $message->bcc($request->send_to ?? 'test@gmail.com'); 
          });

          return json(['status' => true, 'message' => __('Success.')]);
      } 
      catch(\Symfony\Component\Mailer\Exception\TransportException $e) 
      {
          return json(['status' => false, 'message' => $e->getMessage()]);
      }
      catch(\Exception $e)
      {
          return json(['status' => false, 'message' => $e->getMessage()]);
      }
    }



    private static function update_payments(Request $request)
    { 
      $rules = [];

      foreach(config('payment_gateways', []) as $gateway_name => $config)
      {
        foreach($config['fields'] as $field_name => $field_config)
        {
          $rules["gateways.{$gateway_name}.{$field_name}"] = $field_config['validation'];   
        }
      }
      
      foreach(config('exchangers', []) as $name => $config)
      {
        foreach($config['fields'] as $field_name => $validation)
        {
          $rules["exchangers.{$name}.{$field_name}"] = $validation;   
        } 
      }

      $exchangers = implode(',', (array_keys(config('exchangers', []))));

      $rules = array_merge($rules, [
        'tos'               => 'numeric|nullable|in:0,1',
        'tos_url'           => 'string|nullable',
        'buyer_note'        => 'numeric|nullable|in:0,1',
        'vat'               => 'numeric|nullable',
        'currency_code'     => 'required|string',
        'currency_symbol'   => 'nullable|string',
        'currency_position' => 'nullable|string|in:left,right',
        'currencies'        => 'nullable|string',
        'allow_foreign_currencies' => 'nullable|numeric|in:0,1',
        'guest_checkout'  => 'nullable|in:0,1',
        'pay_what_you_want.enabled' => 'nullable|string|in:0,1',
        'pay_what_you_want.for' => 'nullable|required_if:pay_what_you_want.enabled,1',
        'currency_by_country' => 'nullable|string|in:0,1',
        'enable_add_to_cart' => 'nullable|string|in:0,1',
        'show_prices_in_k_format' => 'nullable|string|in:0,1',
        'delete_pending_orders' => 'nullable|numeric|gt:0',
        'exchanger' => "nullable|in:{$exchangers}",
        'enable_webhooks' => 'nullable|string|in:0,1',
        "update_pending_transactions" => 'nullable|numeric|in:0,1',
      ]);

      \Validator::make($request->all(), $rules)->validate();

      $request->currencies    = explode(',', $request->currencies);
      $request->currencies[]  = $request->currency_code;
      $request->currencies    = array_unique($request->currencies);
      $request->currencies    = implode(',', $request->currencies);

      $exchangers = [];

      foreach(config('exchangers', []) as $name => $config)
      {
        $exchangers[$name] = ['api_key' => $request->input("exchangers.{$name}.api_key")]; 
      }

      $data = [
        "gateways"                    => $request->gateways,
        "tos"                         => $request->tos,
        "tos_url"                     => $request->tos_url,
        "vat"                         => $request->vat,
        "buyer_note"                  => $request->buyer_note,
        "currency_code"               => $request->currency_code,
        "currency_symbol"             => $request->currency_symbol,
        "currency_position"           => $request->currency_position,
        "exchange_rate"               => 1,
        "currencies"                  => $request->currencies,
        "allow_foreign_currencies"    => $request->allow_foreign_currencies,
        "exchanger"                   => $request->exchanger,
        "currencyscoop_api_key"       => $request->currencyscoop_api_key,
        "exchangeratesapi_io_key"     => $request->exchangeratesapi_io_key,
        "coinmarketcap_api_key"       => $request->coinmarketcap_api_key,
        "guest_checkout"              => $request->guest_checkout,
        "pay_what_you_want"           => $request->pay_what_you_want,
        "currency_by_country"         => $request->currency_by_country,
        'enable_add_to_cart'          => $request->enable_add_to_cart,
        "show_prices_in_k_format"     => $request->show_prices_in_k_format,
        'delete_pending_orders'       => $request->delete_pending_orders,
        'exchangers'                  => $exchangers,
        'enable_webhooks'             => $request->enable_webhooks,
        'update_pending_transactions' => $request->update_pending_transactions,
      ];

      Setting::first()->update(['payments' => json_encode($data)]);
        
      Cache::forget('paypal_access_token');
    }



    private static function update_maintenance(Request $request)
    {
      $request->validate([
        'maintenance.enabled' => 'nullable|in:0,1',
        'maintenance.expires_at' => 'nullable',
        'maintenance.title' => 'nullable|string',
        'maintenance.header' => 'nullable|string',
        'maintenance.subheader' => 'nullable|string',
        'maintenance.text' => 'nullable|string',
        'maintenance.bg_color' => 'nullable|string',
        'maintenance.auto_disable' => 'nullable|string|in:0,1',
      ]);

      $settings = Setting::first();

      $settings->maintenance = json_encode($request->maintenance);

      $settings->save();
    }



    private static function update_search_engines(Request $request)
    {      
      $request->validate([
        'json_ld' => 'nullable|in:0,1',
        'site_verification'  => 'string|nullable',
        'analytics_code' => 'string|nullable',
        'robots'  => ['regex:/^(follow, index|follow, noindex|nofollow, index|nofollow, noindex)$/i'],
        "indexnow_key" => "nullable|string|max:255",
      ]);

      $settings = Setting::first();

      $search_engines_settings = json_decode($settings->search_engines) ?? new \stdClass;

      $search_engines_settings->site_verification  = $request->site_verification;
      $search_engines_settings->analytics_code = $request->analytics_code;
      $search_engines_settings->robots  = $request->robots;
      $search_engines_settings->json_ld = $request->json_ld;
      $search_engines_settings->indexnow_key = $request->indexnow_key;

      if($search_engines_settings->indexnow_key && !file_exists(public_path("{$search_engines_settings->indexnow_key}.txt")))
      {
        file_put_contents(public_path("{$search_engines_settings->indexnow_key}.txt"), $search_engines_settings->indexnow_key);

        update_env_var("INDEXNOW_KEY", $search_engines_settings->indexnow_key);
      }
      
      $settings->search_engines = json_encode($search_engines_settings);

      $settings->save();
    }



    private static function update_adverts(Request $request)
    {
      $request->validate([
        'responsive_ad' => 'string|nullable',
        'auto_ad'       => 'string|nullable',
        'in_feed_ad'    => 'string|nullable',
        'link_ad'       => 'string|nullable',
        'ad_728x90'     => 'string|nullable',
        'ad_468x60'     => 'string|nullable',
        'ad_250x250'    => 'string|nullable',
        'ad_320x100'    => 'string|nullable'
      ]);


      $settings = Setting::first();

      $advers_settings = json_decode($settings->adverts) ?? new \stdClass;

      $advers_settings->responsive_ad = $request->responsive_ad;
      $advers_settings->auto_ad       = $request->auto_ad;
      $advers_settings->ad_728x90     = $request->ad_728x90;
      $advers_settings->ad_468x60     = $request->ad_468x60;
      $advers_settings->ad_300x250    = $request->ad_300x250;
      $advers_settings->ad_320x100    = $request->ad_320x100;
      $advers_settings->popup_ad      = $request->popup_ad;

      $settings->adverts = json_encode($advers_settings);

      $settings->save();
    }



    private static function update_files_host(Request $request)
    {
      $request->validate([
        'google_drive.enabled'        => 'nullable|in:on',
        'google_drive.api_key'        => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.client_id'      => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.secret_id'      => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.refresh_token'  => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.chunk_size'     => 'numeric|nullable|gte:1',
        'google_drive.folder_id'      => 'nullable|string',
                  #...........................
        'google_cloud_storage.enabled' => 'nullable|in:on',
        'google_cloud_storage.project_id' => 'string|nullable|required_with:google_cloud_storage.enabled',
        'google_cloud_storage.private_key_id' => 'string|nullable|required_with:google_cloud_storage.enabled',
        'google_cloud_storage.private_key' => 'string|nullable|required_with:google_cloud_storage.enabled',
        'google_cloud_storage.client_email' => 'string|nullable|required_with:google_cloud_storage.enabled',
        'google_cloud_storage.client_id' => 'string|nullable|required_with:google_cloud_storage.enabled',
        'google_cloud_storage.auth_provider_x509_cert_url' => 'string|nullable|required_with:google_cloud_storage.enabled',
        'google_cloud_storage.client_x509_cert_url' => 'string|nullable|required_with:google_cloud_storage.enabled',
        'google_cloud_storage.bucket' => 'string|nullable|required_with:google_cloud_storage.enabled',
                  #...........................
        'dropbox.enabled'             => 'nullable|in:on',
        'dropbox.app_key'             => 'string|nullable|required_with:dropbox.enabled',
        'dropbox.app_secret'          => 'string|nullable|required_with:dropbox.enabled',
        'dropbox.access_token'        => 'string|nullable|required_with:dropbox.enabled',
        'dropbox.folder_path'         => 'nullable|regex:/^\/(.+)$/i',
                 #...........................-
        'yandex.enabled'              => 'nullable|in:on',
        'yandex.client_id'            => 'string|nullable|required_with:yandex.enabled',
        'yandex.secret_id'            => 'string|nullable|required_with:yandex.enabled',
        'yandex.refresh_token'        => 'string|nullable|required_with:yandex.enabled',
        'yandex.folder_path'          => 'nullable|string',
                #...........................-
        'amazon_s3.enabled'           => 'nullable|in:on',
        'amazon_s3.access_key_id'     => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.secret_key'        => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.bucket'            => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.region'            => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.version'           => 'string|nullable|required_with:amazon_s3.enabled',
                #...........................-
        'wasabi.enabled'              => 'nullable|in:on',
        'wasabi.access_key'           => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.secret_key'           => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.bucket'               => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.region'               => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.version'              => 'string|nullable|required_with:wasabi.enabled',
                #...........................-
        'working_with'                => ['string', 'regex:/^(files|folders)$/i'],
        'remote_files'                => 'nullable|array',
        'remote_files.headers'        => 'nullable|string',
        'remote_files.body'           => 'nullable|string',
      ]);

      $remote_files = ['headers' => null, 'body' => null];

      if($headers = array_filter(explode(PHP_EOL, $request->input('remote_files.headers'))))
      {
        foreach($headers as &$header)
        {
          $arr = explode(":", $header, 2);
          
          $remote_files['headers'][trim($arr[0])] = trim($arr[1] ?? '');
        }
      }

      if($body = array_filter(explode(PHP_EOL, $request->input('remote_files.body'))))
      {
        foreach($body as &$val)
        {
          $arr = explode(":", $val, 2);
          
          $remote_files['body'][trim($arr[0])] = trim($arr[1] ?? '');
        }
      }

      $data = [
                'google_drive'  => $request->google_drive,
                'google_cloud_storage' => $request->google_cloud_storage,
                'dropbox'       => $request->dropbox,
                'yandex'        => $request->yandex,
                'amazon_s3'     => $request->amazon_s3,
                'wasabi'        => $request->wasabi,
                'working_with'  => $request->working_with,
                'remote_files'  => $remote_files,
              ];

      if(strtolower($request->working_with) === 'folders')
      {
        unset($data['yandex']['enabled'], $data['amazon_s3']['enabled'], $data['wasabi']['enabled']);
      }

      Setting::first()->update(['files_host' => json_encode($data)]);
    }



    private static function update_social_login(Request $request)
    {
      $request->validate([
        'google.enabled'        => 'nullable|in:on',
        'google.client_id'      => 'string|nullable|required_with:google.enabled',
        'google.secret_id'  => 'string|nullable|required_with:google.enabled',
                #...........................
        'github.enabled'        => 'nullable|in:on',
        'github.client_id'      => 'string|nullable|required_with:github.enabled',
        'github.secret_id'  => 'string|nullable|required_with:github.enabled',
                #...........................
        'linkedin.enabled'        => 'nullable|in:on',
        'linkedin.client_id'      => 'string|nullable|required_with:linkedin.enabled',
        'linkedin.secret_id'  => 'string|nullable|required_with:linkedin.enabled',
                #...........................
        'facebook.enabled'        => 'nullable|in:on',
        'facebook.client_id'      => 'string|nullable|required_with:facebook.enabled',
        'facebook.secret_id'  => 'string|nullable|required_with:facebook.enabled',
                #...........................
        'vkontakte.enabled'       => 'nullable|in:on',
        'vkontakte.client_id'     => 'string|nullable|required_with:vkontakte.enabled',
        'vkontakte.secret_id' => 'string|nullable|required_with:vkontakte.enabled',
                #...........................
        'twitter.enabled'         => 'nullable|in:on',
        'twitter.client_id'       => 'string|nullable|required_with:twitter.enabled',
        'twitter.secret_id'   => 'string|nullable|required_with:twitter.enabled',
                #...........................
        'dribbble.enabled'         => 'nullable|in:on',
        'dribbble.client_id'       => 'string|nullable|required_with:dribbble.enabled',
        'dribbble.secret_id'       => 'string|nullable|required_with:dribbble.enabled',
                #...........................
        /*'tiktok.enabled'         => 'nullable|in:on',
        'tiktok.client_id'       => 'string|nullable|required_with:tiktok.enabled',
        'tiktok.secret_id'       => 'string|nullable|required_with:tiktok.enabled',
                #...........................
        'reddit.enabled'         => 'nullable|in:on',
        'reddit.client_id'       => 'string|nullable|required_with:reddit.enabled',
        'reddit.secret_id'       => 'string|nullable|required_with:reddit.enabled',*/
      ]);

      Setting::first()->update(['social_login' => json_encode([
        "google"    => array_merge($request->google, ['redirect' => route('social_account.callback', ['provider' => 'google'])]),
        "github"    => array_merge($request->github, ['redirect' => route('social_account.callback', ['provider' => 'github'])]),
        "linkedin"  => array_merge($request->linkedin, ['redirect' => route('social_account.callback', ['provider' => 'linkedin'])]),
        "facebook"  => array_merge($request->facebook, ['redirect' => route('social_account.callback', ['provider' => 'facebook'])]),
        "vkontakte" => array_merge($request->vkontakte, ['redirect' => route('social_account.callback', ['provider' => 'vkontakte'])]),
        "twitter"   => array_merge($request->twitter, ['redirect' => route('social_account.callback', ['provider' => 'twitter'])]),
        "dribbble"  => array_merge($request->dribbble, ['redirect' => route('social_account.callback', ['provider' => 'dribbble'])]),
        /*"tiktok"    => array_merge($request->tiktok, ['redirect' => route('social_account.callback', ['provider' => 'tiktok'])]),
        "reddit"    => array_merge($request->reddit, ['redirect' => route('social_account.callback', ['provider' => 'reddit'])]),*/
      ])]);
    }



    private static function update_chat(Request $request)
    {
      $request->validate([
        'chat.enabled'  => 'nullable|in:on',
        'chat.code'     => 'string|nullable|required_with:other.enabled' 
      ]);

      Setting::first()->update(['chat' => json_encode($request->post('chat'))]);
    }


    private static function update_translations(Request $request)
    {
      $langs = File::directories(resource_path("lang/"));
      $langs = array_filter(preg_replace('/.+(\/|\\\)([\w-]+)$/i', '$2', $langs));

      $request->validate([
        'translation' => 'required|array',
        'new' => 'nullable|array',
        '__lang__' => ['required', 'string', 'in:'.implode(',', $langs), function($attribute, $value, $fail) use($request)
        {
            if (!File::exists(resource_path("lang/{$request->__lang__}.json"))) 
            {
              $fail('Missing language file.');
            }
        }]
      ]);

      $new_translation = [];

      if(array_filter($request->new ?? []))
      {
        if(count($request->new['key'] ?? []) === count($request->new['value'] ?? []))
        {
          $new_translation = array_combine($request->new['key'] ?? [], $request->new['value'] ?? []);
        }
      }

      $lang = json_decode(File::get(resource_path("lang/{$request->__lang__}.json")), true);
      
      $translation = $request->translation;
      $translation = array_combine(array_map("base64_decode", array_keys($translation)), array_values($translation));

      $lang = array_merge($lang, $translation, $new_translation);

      ksort($lang);

      File::put(resource_path("lang/{$request->__lang__}.json"), json_encode($lang, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }


    private static function update_captcha(Request $request)
    {      
      $request->validate([
        'captcha.enable_on' => 'required_with:captcha.google.enabled|required_with:captcha.mewebstudio.enabled|nullable',
        'captcha.google.enabled'      => 'nullable|in:on',
        'captcha.google.secret' => 'nullable|string|required_with:captcha.google.enabled',
        'captcha.google.sitekey'  => 'nullable|string|required_with:captcha.google.enabled',
        'captcha.google.attributes.data-theme'  => 'nullable|string|required_with:captcha.google.enabled',
        'captcha.google.attributes.data-size'  => 'nullable|string|required_with:captcha.google.enabled|in:compact,normal',
        'captcha.mewebstudio.enabled' => 'nullable|in:on',
        'captcha.mewebstudio.length' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled',
        'captcha.mewebstudio.width' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled',
        'captcha.mewebstudio.height' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled',
        'captcha.mewebstudio.quality' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled'
      ]);

      $captcha = $request->captcha;

      $captcha['mewebstudio']['math'] = $captcha['mewebstudio']['math'] === "true";

      Setting::first()->update(['captcha' => json_encode($captcha)]);
    }



    private static function update_database(Request $request)
    {      
      $request->validate([
        'database.host'             => 'required|string',
        'database.database'         => 'required|string',
        'database.username'         => 'required|string',
        'database.password'         => 'required|string',
        'database.charset'          => 'required|string',
        'database.collation'        => 'required|string',
        'database.port'             => 'required|numeric',
        'database.sort_buffer_size' => 'required|numeric|gte:0',
        'database.timezone'         => 'required|string',
        'database.sql_mode'         => 'required|string'
      ]);

      $new_config = [];

      foreach($request->post('database') as $k => $v)
      {
        $k = "DB_".strtoupper($k);
        $new_config[$k] = wrap_str($v);
      }

      update_env_var($new_config);

      Setting::first()->update(['database' => json_encode($request->post('database'))]);
    }



    public function update_bulk_upload(Request $request)
    {
      $request->validate([
        'data_file'    => 'required|file',
        'main_files.*' => 'nullable|file|mimes:zip,rar,7z',
        'covers.*'     => 'nullable|image'
      ]);


      $csv        = $request->file('data_file');
      $main_files = $request->file('main_files');
      $covers     = $request->file('covers');

      $cols = $request->post('columns');

      if(!ini_get("auto_detect_line_endings"))
      {
        ini_set("auto_detect_line_endings", '1');
      }

      $columns = [];
      $csv = \League\Csv\Reader::createFromPath($csv->getRealPath(), 'r');
      $csv->setHeaderOffset(0);
      $csv->setDelimiter(';');

      $header  = $csv->getHeader();
      $records = iterator_to_array($csv->getRecords());
      $data    = [];

      if($request->async)
      {
        $columns =  array_reduce($header, function($carry, $column)
                    {
                      $carry[] = ['name' => str_replace('_', ' ', mb_ucfirst($column)), 'value' => $column];
                      return $carry;
                    }, []);

        header('Content-Type: application/json');

        echo json_encode($columns);

        exit;
      }

      $_cols = array_combine($cols['original'], $cols['imported']);

      foreach($records as $record)
      { 
        $entry = [];

        foreach($_cols as $original => $imported)
        {
          $entry[$original] = $record[$imported] ?? null; 
        }

        $data[] = $entry;
      }

      foreach($data as $item)
      {
        $product_id = get_auto_increment('products');

        unset($item['id']);

        $regular_price = $item['regular_price'] ?? 0;

        unset($item['regular_price']);

        $category = $item['category'] ?? null;
        $new_category = null;

        if(isset($category))
        {
          if(!filter_var($category, FILTER_VALIDATE_INT))
          {
            if($existing_category = Category::where('name', $category)->first())
            {
              $new_category = $existing_category;
            }
            else
            {
              $new_category = new Category;

              $new_category->name = $category;
              $new_category->slug = slug($category);

              $new_category->save();
            }
          }
          else
          {
            $new_category = (object)['id' => $category]; 
          }
        }

        $product = new Product;

        foreach($item as $key => $val)
        {
          $product->$key = $val;          
        }


        $product->type   = in_array($item['type'] ?? null, ['-', 'audio', 'video', 'graphic', 'ebook']) ? $item['type'] : config('app.default_product_type', '-');
        
        $product->is_dir = in_array((string)($item['is_dir'] ?? null), ['0', '1']) ? $item['is_dir'] : 0;

        foreach($main_files ?? [] as $main_file)
        {
          if($product->file_name == $main_file->getClientOriginalName())
          {
            $extension = $main_file->getClientOriginalExtension();

            $main_file->storeAs("downloads", "{$product_id}.{$extension}", []);

            $product->file_name = "{$product_id}.{$extension}";
          }
        }

        foreach($covers ?? [] as $cover)
        {
          if($product->cover == $cover->getClientOriginalName())
          {
            $extension = $cover->getClientOriginalExtension();

            $cover->storeAs("covers", "{$product_id}.{$extension}", ['disk' => 'public']);

            $product->cover = "{$product_id}.{$extension}";
          }
        }

        if($new_category)
        {
          $product->category = $new_category->id;
        }

        $product->slug      = slug($product->name);
        $product->file_host = $item['file_host'] ?? 'local';
        $product->active    = $product->active ?? 1;

        $product->save();

        if(!$license = License::where('item_type', $product->type)->where('regular', 1)->first())
        {
          $license = new License;
          
          $license->name      = 'Regular License';
          $license->item_type = $product->type;
          $license->regular   = 1;

          $license->save();
        }

        Product_Price::where('product_id', $product_id)->delete();

        Product_Price::insert(['license_id' => $license->id, 'product_id' => $product_id, 'price' => $regular_price, 'promo_price' => null]);
      }
    } 



    public static function remove_top_cover()
    {
      foreach(glob(public_path('storage/images/top_cover.*')) ?? [] as $top_cover)
      {
        @unlink($top_cover);
      }

      $settings = Setting::first();

      $general_settings = json_decode($settings->general) ?? (object)[];
      
      $general_settings->top_cover =  '';

      $settings->general = json_encode($general_settings);
      
      $settings->save();

      return json(['success' => true]);
    }
    


    public function google_storage_connect(Request $request)
    {
      if($request->input('init'))
      {
        $api = $request->input("api");

        Cache::forget("google_response");
        
        $client = new Client();

        $config = [
          "client_id"     => $request->input('client_id'),
          "client_secret" => $request->input('client_secret'),
          "redirect_uris" => [$api === "google_drive" ? secure_url("/gd_callback") : secure_url("/gcs_callback")]
        ];

        $client->setAuthConfig($config);
          
        $client->setState(encrypt(json_encode($config), false));

        if($api === "google_drive")
        {
          $client->addScope("https://www.googleapis.com/auth/drive.readonly email");
        }
        elseif($api === "google_cloud_storage")
        {
          $client->addScope("https://www.googleapis.com/auth/devstorage.full_control email");
        }
        
        $client->setAccessType('offline');

        $client->setPrompt('select_account consent');

        $client->setIncludeGrantedScopes(true);

        $auth_url = $client->createAuthUrl();

        return json(compact('auth_url'));
      }
      else
      { 
        $code = $request->query('code');
        $state = $request->query('state');

        if($code && $state)
        {
          $state = json_decode(decrypt($state, false));

          $client = new Client();

          $client->setAuthConfig([
            "client_id"     => $state->client_id,
            "client_secret" => $state->client_secret,
            "redirect_uris" => $state->redirect_uris
          ]);

          $response = $client->fetchAccessTokenWithAuthCode($code);

          if(isset($response['error']))
          {
            exists_or_abort(null, "Error : {$response['error_description']}");
          }

          $user = GoogleDrive::get_current_user($response['id_token']);

          $response['email'] = $user['email'] ?? null;

          Cache::put("google_response", $response);
        }
        elseif(auth_is_admin())
        {
          if($response = Cache::pull("google_response"))
          {
            return json(['status' => true, 'response' => $response]);
          }
          else
          {
            return json(['status' => false, 'response' => null]); 
          }
        }
      }
    }
    
    

    
    public function dropbox_get_current_user(Request $request)
    {
        return DropBox::get_current_user($request);
    }



    public function yandex_disk_get_refresh_token(Request $request)
    {
      return YandexDisk::code_to_refresh_token($request);
    }


    public function test_amazon_s3_connection(Request $request)
    {
      return json(['status' => AmazonS3::test_connexion($request) ? __('Success') : __('Failed')]);
    }


    public function test_wasabi_connection(Request $request)
    {
      return json(['status' => Wasabi::test_connexion($request) ? __('Success') : __('Failed')]);
    }


    public function test_google_cloud_storage_connection(Request $request)
    {
      return json(['status' => GoogleCloudStorage::test_connexion($request)]);
    }

    


    public function test_database_connection(Request $request)
    {
      if(config('app.installed') === true)
      {
        $this->middleware('auth');

        auth_is_admin() ?? abort(404);
      }

      $error_message = null;

      if(!$request->installation)
      {
        $mysql_config  = config('database.connections.mysql');
        $mysql_config  = array_merge($request->database, $mysql_config);

        Config::set("database.connections.mysql", $mysql_config);
        
        try 
        {
          DB::connection()->getPdo();
        }
        catch (\Exception $e)
        {
          $error_message = $e->getMessage();
        }
      }
      else
      {
        $db_config = array_values($request->input('database'));

        try 
        {
          $mysqli = new \mysqli(...$db_config);

          if($mysqli->connect_error) 
          {
            $error_message = $mysqli->connect_error;
          }
        }
        catch(\Exception $e)
        {
          $error_message = $e->getMessage();
        }
      }

      return json(['status' => $error_message ?? __('Success')]);
    }


    public function get_translation(Request $request)
    {
      $lang = $request->lang ?? abort(404);

      $lang = json_decode(File::get(resource_path("lang/{$lang}.json")), true);
      $base = json_decode(File::get(resource_path("lang/en.json")), true);
      
      $lang = array_combine(array_map("base64_encode", array_keys($lang)), array_values($lang));
      $base = array_combine(array_map("base64_encode", array_keys($base)), array_values($base));

      return json(compact('lang', 'base'));
    }


    public function clear_cache(Request $request)
    {
        $name = strtolower($request->post('name'));

        \Artisan::call("{$name}:clear");
    }


    public function generate_fake_profiles(Request $request)
    {
      $gender  = $request->input('gender');
      $country = $request->input('country', 'random');
      $count   = $request->input('count', 50);

      $gender  = $gender == '-' ? null : $gender;
      $genders = ['male', 'female'];

      $picture_url = "https://codemayer.net/api/ai-faces?gender={$gender}";

      $result = [];

      for($i = 0; $i < (int)$count; $i++)
      {
        $fake_name   = fake()->lastName($gender).' '.fake()->firstName($gender);

        if(!$gender)
        {
          $gender      = $genders[rand(0, 1)];
          $picture_url = "https://codemayer.net/api/ai-faces?gender={$gender}";
          $fake_name   = fake()->lastName($gender).' '.fake()->firstName($gender);
        }
        
        $country = $country == 'random' ? (explode('-', $matches['country'] ?? null, 2)[1] ?? null) : $country;
        
        $profile_img = file_get_contents($picture_url);
        $profile_img = json_decode($profile_img, JSON_UNESCAPED_UNICODE);
        $profile_img = $profile_img['image_url'];

        $id = md5($fake_name);

        Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
        
        $img = Image::make($profile_img)->encode('webp');

        $img->resize(128, 128);

        if($img->save(public_path("storage/profiles/{$id}.webp")))
        {
          $result[$id] = [
            "name"    => $fake_name,
            "avatar"  => "{$id}.webp",
            "country" => $country
          ];
        }
      }

      $settings = Setting::first();

      $general_settings = json_decode($settings->general);

      if($fake_profiles = ($general_settings->fake_profiles ?? null))
      {
        $fake_profiles = obj2arr($fake_profiles);

        $result = array_merge($fake_profiles, $result);
      }

      $general_settings->fake_profiles = $result;

      $settings->general = json_encode($general_settings, JSON_UNESCAPED_UNICODE);

      $settings->save();

      return json(['message' => __('Fake profiles have been generated and saved successfully.')]);
    }


    public function list_fake_profiles(Request $request)
    {
      $settings = Setting::first();

      $general_settings = json_decode($settings->general);

      return json(['profiles' => $general_settings->fake_profiles ?? []]);
    }


    public function delete_fake_profiles(Request $request)
    {
      $id = $request->id;

      $settings = Setting::first();

      $general_settings = json_decode($settings->general, true, 512, JSON_UNESCAPED_UNICODE);

      $profiles = $general_settings['fake_profiles'] ?? [];

      unset($profiles[$id]);

      if(file_exists(public_path("storage/profiles/{$id}.jpg")))
      {
        File::delete(public_path("storage/profiles/{$id}.jpg"));
      }

      $general_settings['fake_profiles'] = $profiles;

      $settings->general = json_encode($general_settings, JSON_UNESCAPED_UNICODE);

      $settings->save();

      return json(compact('profiles')); 
    }


    public function execute_db_query(Request $request)
    {
      $request->validate([
        'query'  => 'required|string',
        'action' => 'required|in:Select,Update,Insert,Delete,Statement'
      ]);

      $action = $request->post('action');
      $query  = $request->post('query');

      $response = DB::$action($query);

      return json(['response' => $response]);
    }


    public function update_products_extension(Request $request)
    {      
      $products = Product::where('file_name', '!=', null)->where(['file_extension' => null, 'direct_download_link' => null])->get();

      foreach($products as &$product)
      {
        if(preg_match("/^(local|amazon_s3|wasabi|gcs)$/i", $product->file_host))
        {
          $url = parse_url($product->file_name, PHP_URL_PATH);
    
          $product->file_extension = pathinfo($url, PATHINFO_EXTENSION);
        }
        elseif($product->file_host === "google")
        {
          $product->file_extension = GoogleDrive::get_file_extension($product->file_name);
        }
        elseif($product->file_host === "dropbox")
        {
          $product->file_extension = DropBox::get_file_extension($product->file_name);
        }

        if($product->preview)
        {
          $url = parse_url($product->preview, PHP_URL_PATH);
    
          $product->preview_extension = pathinfo($url, PATHINFO_EXTENSION);

          if(preg_match("/^zip|rar|7z$/i", $product->preview_extension))
          {
            $product->preview_type = "archive";
          }
          elseif(preg_match("/^mp4$/i", $product->preview_extension))
          {
            $product->preview_type = "video";
          }
          elseif(preg_match("/^mp3|ogg$/i", $product->preview_extension))
          {
            $product->preview_type = "audio";
          }
          elseif(preg_match("/pdf|doc|docx|ppt|excel/i", $product->preview_extension))
          {
            $product->preview_type = "document";
          }
          else
          {
            $product->preview_type = "other";
          }
        }

        $product->save();
      }
    }
}
