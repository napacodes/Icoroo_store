<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ Product, Key, User_Subscription, Pricing_Table, Page, Post, Setting, Newsletter_Subscriber, Reaction, Search, 
  Subscription_Same_Item_Downloads, Prepaid_Credit, Category, Support_Email, Support, Review, Comment, Faq, Notification, Transaction, User, 
  Product_Price, License, Payment_Link, User_Prepaid_Credit, Custom_Route, Affiliate_Earning, Cashout, Coupon, Temp_Direct_Url, User_Shopping_Cart_Item };
use Illuminate\Support\Facades\{ DB, File, Hash, Validator, Config, Auth, Mail, Cache, Session, View };
use App\Libraries\{ DropBox, GoogleDrive, IyzicoLib, YandexDisk, OneDrive, AmazonS3, Wasabi, Paypal };
use ZipArchive;
use GeoIp2\Database\Reader;
use Intervention\Image\Facades\Image;
use BrowserDetect;
use Illuminate\Support\{ Carbon, Str };
use Intervention\Image\{ ImageManager, Facades\Image as InterventionImage };


class HomeController extends Controller
{
    public function __construct()
    {
      if(config('app.installed') === true)
      {
        config([
          "meta_data.name" => config('app.name'),
          "meta_data.title" => config('app.title'),
          "meta_data.description" => config('app.description'),
          "meta_data.url" => url()->current(),
          "meta_data.fb_app_id" => config('app.fb_app_id'),
          "meta_data.image" => asset('storage/images/'.(config('app.cover') ?? 'cover.jpg'))
        ]);

        $this->middleware('maintenance_mode');

        View::share(['payment_gateways' => array_values(config('payments_gateways', []))]);

        if(!cache('counters'))
        {
            $fake_counters = [
              "orders" => config('app.fake_counters') ? rand(950, 1000) : Transaction::count(),
              "products" => config('app.fake_counters') ? rand(1000, 2000) : Transaction::count(),
              "categories" => config('app.fake_counters') ? rand(100, 200) : Category::count(),
              "affiliate_earnings" => price(config('app.fake_counters') ? rand(5000, 10000) : Affiliate_Earning::sum('amount'), 0, 0),
            ];

            Cache::put("counters", $fake_counters, now()->addDays(1));
        }
      }
    }


    private static $product_columns = ['products.id', 'products.name', 'products.featured', 'products.trending', 'products.views', 'products.preview', 'products.preview_type', 'products.for_subscriptions', 'licenses.id as license_id', 'licenses.name as license_name', 'products.pages', 'products.authors', 'products.language', 'products.country_city', 
      'products.words', 'products.formats', 
     'products.slug', 'products.updated_at', 'products.active', 'products.bpm', 'products.label',
     'products.cover', 'products.last_update', 'products.hidden_content', 'categories.id as category_id', 'products.stock', 'IFNULL(CHAR_LENGTH(GROUP_CONCAT(transactions.products_ids)) - CHAR_LENGTH(REPLACE(GROUP_CONCAT(transactions.products_ids), QUOTE(products.id), SPACE(LENGTH(QUOTE(products.id))-1))), 0) AS sales', 
      'IFNULL((SELECT ROUND(AVG(rating)) FROM reviews WHERE product_id = products.id), 0) AS rating',
      '(SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id AND key_s.user_id IS NULL) as `remaining_keys`',
      '(SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id) as has_keys',
      'products.tags', 'products.short_description',
       "CASE
          WHEN product_price.`promo_price` IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN STR_TO_DATE(SUBSTR(products.promotional_price_time, 10, 10), '%Y-%m-%d') and STR_TO_DATE(SUBSTR(products.promotional_price_time, 28, 10), '%Y-%m-%d')))
            THEN product_price.promo_price
          ELSE
            NULL
        END AS `promotional_price`",
        "IF(DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN STR_TO_DATE(SUBSTR(products.promotional_price_time, 10, 10), '%Y-%m-%d') and STR_TO_DATE(SUBSTR(products.promotional_price_time, 28, 10), '%Y-%m-%d'), products.promotional_price_time, null) AS promotional_time",
        'IF(product_price.price IS NULL || licenses.id IS NULL, NULL, product_price.price = 0 OR (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10))) AS free_item',
        'IF(product_price.price IS NULL || licenses.id IS NULL, NULL, IF(product_price.price = 0 OR (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) = 1, 0, product_price.price)) as price',
       'categories.name as category_name', 'categories.slug as category_slug',
       'GROUP_CONCAT(subcategories.name) AS subcategories'];



    public function index()
    {
        $template = config('app.template', 'axies');

        config([
          'json_ld' => [
            '@context' => 'http://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'potentialAction' => [
              '@type' => 'SearchAction',
              'target' => route("home.products.q").'?&q={query}',
              'query' => 'required'
            ]
          ]
        ]);

        return call_user_func([$this, "{$template}_home"]);
    }


    private function axies_home()
    {
        $request_id = "axies_home";
        $data       = null;

        if(config('app.enable_data_cache'))
        {
          $data = Cache::get($request_id);
        }

        if(!$data || !config('app.enable_data_cache'))
        {
          $posts = Post::useIndex('primary')
                    ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                    ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                    ->where('active', 1)->orderBy('id', 'DESC')->limit(4)->get();

          $featured_products = $this->featured_products(false, 10, null, 0);

          $newest_products = $this->newest_products(false, 10, 0);
          
          $free_products = $this->free_products(false, 10, 0);

          $trending_products = $this->trending_products(false, 10, 0);

          $products = [];

          foreach($featured_products as $featured_product)
          {
            $products[$featured_product->id] = $featured_product;
          }

          foreach($newest_products as $newest_product)
          {
            $products[$newest_product->id] = $newest_product;
          }

          foreach($trending_products as $trending_product)
          {
            $products[$trending_product->id] = $trending_product;
          }

          $subscriptions  = Pricing_Table::useIndex('position')->orderBy('position', 'asc')->get();

          $data = compact('products', 'posts', 'newest_products', 'subscriptions', 'featured_products', 'free_products', 'trending_products');
          
          Cache::put($request_id, $data, now()->addSeconds(3600));
        }

        return view_('home', $data);
    }


    private function tendra_home()
    {
        $posts = Post::useIndex('primary')
                  ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                  ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                  ->where('active', 1)->orderBy('id', 'DESC')->limit(3)->get();

        $featured_products = [];

        foreach(config('categories.category_parents') as $parent_category)
        {
          $featured_products[$parent_category->slug] = $this->featured_products(false, 8, $parent_category->id, 0);
        }

        $featured_products = array_filter($featured_products, function($items, $k)
                              {
                                return $items->count();
                              }, ARRAY_FILTER_USE_BOTH);

        $newest_products = $this->newest_products(false, 10, 0);

        $free_products = $this->free_products(false, 10, 0);

        $products = [];

        foreach($featured_products as $list)
        {
          foreach($list as $featured_product)
          {
            $products[$featured_product->id] = $featured_product;
          }
        }

        foreach($newest_products as $newest_product)
        {
          $products[$newest_product->id] = $newest_product;
        }

        $subscriptions = Pricing_Table::useIndex('position')->orderBy('position', 'asc')->get();

        foreach($subscriptions as &$subscription)
        {
          $subscription->specifications = json_decode($subscription->specifications, false, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?? (object)[];
        }

        return view_('home', compact('products', 'posts', 'newest_products', 'subscriptions', 
                                     'featured_products', 'free_products'));
    }


    public function affiliate()
    {
        return view('front.affiliate');
    }


    // Single page
    public function page($slug)
    {
        if(!$page = Page::useIndex('slug', 'active')->where(['slug' => $slug, 'active' => 1])->first())
          abort(404);

        $page->setTable('pages')->increment('views', 1);

        config([
          "meta_data.name" => config('app.name'),
          "meta_data.title" => $page->name,
          "meta_data.description" => $page->short_description,
          "json_ld" => [
            '@context' => 'http://schema.org',
            '@type' => 'WebPage',
            'name' => $page->name,
            'description' => $page->short_description,
          ]
        ]);

        return view_('page',compact('page'));
    }


    // Products per category
    public function products(Request $request)
    {
      $categories         = config('categories.category_parents', []);
      $subcategories      = config('categories.category_children', []);
      $category           = $active_category = $active_subcategory = (object)[];
      
      if($sort = strtolower($request->query('sort')))
      {
        preg_match('/^(?P<sort>relevance|price|rating|featured|trending|date)_(?P<order>asc|desc)$/i', $sort, $matches) || abort(404);

        extract($matches);
      }
      else
      {
        list($sort, $order) = ['id', 'desc'];
      }

      if($sort === 'date')
        $sort = 'updated_at';
      else
        $sort = 'id';

      $indexes = ['active'];

      if($request->category_slug)
      {
        array_push($indexes, 'category');

        $category_slug = $request->category_slug;

        $active_category =  array_filter($categories, function($category) use ($category_slug)
                            {
                              return $category->slug === strtolower($category_slug);
                            }) ?? abort(404);

        $active_category = array_shift($active_category);
        
        if(!isset($active_category->name)) return back();

        if($subcategory_slug = $request->subcategory_slug)
        {
          if(!isset($subcategories[$active_category->id ?? null])) return back();

          $active_subcategory =   array_filter($subcategories[$active_category->id], 
                                  function($subcategory) use ($subcategory_slug)
                                  {
                                    return $subcategory->slug === strtolower($subcategory_slug);
                                  });

          if(!$active_subcategory = array_shift($active_subcategory)) return back();
        }

        if(!$subcategory_slug)
        {
          $category->name        = $active_category->name;
          $category->description = Category::useIndex('primary')->select('description')
                                                        ->where('id', $active_category->id)->first()->description;

          $products = Product::where(['category' => $active_category->id]);
        }
        else
        {
          array_push($indexes, 'subcategories');

          $category->name        = $active_subcategory->name;
          $category->description = Category::useIndex('primary')->select('description')
                                                        ->where('id', $active_subcategory->id)->first()->description;

          $products = Product::where(['category' => $active_category->id])
                              ->whereRaw("subcategories LIKE '%{$active_subcategory->id}%'");
        }

        config([
          "meta_data.title" => config('app.name').' - '.$category->name,
          "meta_data.description" => $category->description
        ]);
      }

      if($filter = strtolower($request->filter))
      {
        if($filter === 'free')
        {
          $products = Product::where(function ($query)
                            {
                              $query->where('product_price.price', 0)
                                    ->orWhereRaw("CURRENT_DATE between substr(free, 10, 10) and substr(free, 28, 10)");
                            });
        }
        elseif($filter === 'trending')
        {
          $products = Product::havingRaw("active = 1 AND (trending = 1 OR count(transactions.id) > 0)")
                      ->orderByRaw('trending, sales DESC');
        }
        elseif($filter === 'featured')
        {
          $products = Product::where("products.featured", 1);
        }
        elseif($filter === 'flash')
        {
          $products = Product::havingRaw("product_price.promo_price IS NOT NULL");
        }
        elseif($filter === 'newest')
        {
          $products = Product::useIndex("created_at");
        }
      }
   
      if($q = $request->query('q'))
      {
        $search = new Search;
        
        $search->keywords = $q;
        $search->user_id = Auth::id();

        $search->save();

        $products = call_user_func_array([$request->category_slug ? $products : '\App\Models\Product', 'whereRaw'], ["active = 1 AND (
                        products.name LIKE ? OR products.slug LIKE ? OR 
                        products.short_description LIKE ? OR products.overview LIKE ?
                      )", ["%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"]]);

        config([
          "meta_data.title" => config('app.name').' - '.__('Searching for').' '.ucfirst($request->q),
          "meta_data.description" => $category->description ?? config('meta_data.description')
        ]);
      }

      if($tags = $request->query('tags'))
      {
        $tags = implode('|', array_filter(explode(',', $tags)));

        $products = call_user_func_array([($request->category_slug || $q) ? $products : '\App\Models\Product', 'where'], 
                                         ['products.tags', 'REGEXP', $tags]);
      }

      $cities = $country = null;

      if(config('app.products_by_country_city'))
      {
        if($country = $request->query('country'))
        {
          if($cities = urldecode($request->query('cities')))
          {
            if($cities = array_filter(explode(',', $cities)))
            {
              $cities = implode('|', $cities);
              
              $products = call_user_func_array([($request->category_slug || $q || $tags) ? $products : '\App\Models\Product', 'whereRaw'], ["products.country_city REGEXP ?", ['^\{"country":"'. $country .'","city":"'. $cities .'"\}$']]);
            
              $cities = str_ireplace('|', ',', $cities);
            }
            else
            {
              $cities = null;
            }
          }
          else
          {
            $products = call_user_func_array([($request->category_slug || $q || $tags) ? $products : '\App\Models\Product', 'whereRaw'], ["products.country_city REGEXP ?", ['^\{"country":"'. $country .'","city":.*\}$']]);
          } 
        }
      }

      if($price_range = $request->query('price_range'))
      {
        preg_match('/^\d+,\d+$/', $price_range) || abort(404);

        $price_range =  array_filter(explode(',', $price_range), function($price)
                        {
                          return $price >= 0;
                        });

        if($price_range[0] > $price_range[1])
          return back();

        $products = call_user_func_array([($request->category_slug || $q || $tags) ? $products : '\App\Models\Product', 'whereBetween'], ['product_price.price', $price_range]);
      }

      isset($products) || abort(404);

      $products = $products->setModel(Product::useIndex($indexes))
                      ->selectRaw(implode(',', Self::$product_columns))
                      ->leftJoin('categories', 'categories.id', '=', 'products.category')
                      ->leftJoin('categories as subcategories', function($join) use ($active_category)
                      {
                          $join->on('products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                             ->where('subcategories.parent', '=', property_exists($active_category, 'id') ? $active_category->id : null);
                      })
                      ->leftJoin('transactions', 'transactions.products_ids', 'LIKE', \DB::raw("CONCAT(\"%'\", products.id,\"'%\")"))
                      ->leftJoin('reviews', 'reviews.product_id', '=', 'products.id')
                      ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                      ->leftJoin('product_price', function($join)
                      {
                        $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                      })
                      ->where("products.active", 1)
                      ->groupBy('products.id', 'products.name', 'products.views', 'products.preview', 'products.preview_type', 'categories.id',
                       'products.slug', 'products.updated_at', 'products.active', 'products.bpm', 'products.label',
                       'products.cover', 'product_price.price', 'products.hidden_content', 'products.last_update', 'promotional_price_time', 'products.stock', 'products.pages', 'products.authors', 'products.language',
                       'products.words', 'products.formats', 
                       'categories.name', 'categories.slug', 'reviews.rating', 'products.short_description', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.country_city')
                      ->orderBy($sort, $order)
                      ->paginate(config('app.items_per_page', 12));

      $tags  = [];

      foreach($products->items() as &$item)
      {
        $tags = array_merge($tags, array_filter(array_map('trim', explode(',', $item->tags))));
      }

      $tags = array_unique($tags);

      config([
        'json_ld' => [
          '@context' => 'https://schema.org',
          '@type' => 'ItemList',
          'url' => url()->full(),
          'numberOfItems' => $products->total(),
          'itemListElement' => array_reduce($products->items(), function($carry, $item)
          {
            $carry[] = [
              '@type' => 'Product',
              'image' => asset("storage/covers/{$item->cover}"),
              'url'   => item_url($item),
              'name'  => $item->name,
              'offers' => [
                '@type' => 'Offer',
                'price' => $item->price,
              ],
            ];

            return $carry;
          }, [])
        ]
      ]);

      return view_('products', compact('products', 'tags', 'country', 'cities'));
    }



    public function live_search(Request $request)
    {
      $products = [];

      if($q = $request->post('q'))
      {
        $products = DB::select("SELECT id, `name`, slug, cover FROM products WHERE active = 1 AND (`name` LIKE ? OR slug LIKE ?) LIMIT 5", ["%{$q}%", "%{$q}%"]);
      }

      return response()->json(compact('products'));
    }



    // Single product
    public function product(Request $request)
    {
      $user_id = Auth::id();

      $request_id = strtok($request->server('REQUEST_URI'), '?');
      $data       = null;
      $licenses         = collect(config('licenses', []));
      $regular_license  = collect($licenses)->where('regular', 1)->first();

      if(config('app.enable_data_cache'))
      {
        $data = Cache::get($request_id);
      }

      if(!$data || !config('app.enable_data_cache'))
      {
        $product =  $request->via_permalink 
                    ? (Product::where('permalink', $request->slug)->first() ?? abort(404))
                    : (Product::find($request->id) ?? abort(404));

        if(!$request->via_permalink && urldecode($request->slug) !== mb_strtolower(urldecode($product->slug)))
        {
          return redirect(item_url($product));
        }
      
        $category           = Category::find($product->category) ?? abort(404);
        $sales              = $product->fake_sales > 0 ? $product->fake_sales : Transaction::where('products_ids', 'LIKE', "%'{$product->id}'%")->count();
        $comments_count     = Comment::where('product_id', $product->id)->count();
        $reviews_count      = Review::where('product_id', $product->id)->count();
        $rating             = Review::selectRaw('ROUND(AVG(rating)) as rating')->where(['product_id' => $product->id, 'approved' => 1])->first()->rating;
        $has_keys           = Key::where('product_id', $product->id)->exists() ? 1 : 0;
        $remaining_keys     = Key::where(['product_id' => $product->id, 'user_id' => null])->count();
        


        $product->fill([
          "remaining_keys"               => $remaining_keys,
          "has_keys"                     => $has_keys,
          "category"                     => $category,
          "sales"                        => $sales,
          "reviewed"                     => false,
          "purchased"                    => false,
          "comments_count"               => $comments_count,
          "reviews_count"                => $reviews_count,
          "rating"                       => $rating,
          "remaining_downloads"          => null,
          "valid_subscription"           => 0,
          "limit_downloads_reached"      => 0,
          "daily_download_limit_reached" => 0,
          "stock"                        => $has_keys ? $remaining_keys : $product->stock,
          "license_id"                   => $regular_license->id ?? null,
          "license_name"                 => $regular_license->name ?? null,
        ]);

        $product->table_of_contents = json_decode($product->table_of_contents);

        $reviews =  Review::useIndex('product_id', 'approved')
                    ->selectRaw("reviews.*, users.name, SUBSTR(users.email, 1, LOCATE('@', users.email)-1) as alias_name, CONCAT(users.firstname, ' ', users.lastname) AS fullname, IFNULL(users.avatar, 'default.webp') AS avatar")
                    ->leftJoin('users', 'users.id', '=', 'reviews.user_id')
                    ->where(['reviews.product_id' => $product->id, 'reviews.approved' => 1])
                    ->orderBy('created_at', 'DESC')->get();

        if($fake_reviews = json_decode($product->fake_reviews))
        {
          $last_id = max($reviews->pluck('id')->toArray() ?: [0]);

          foreach($fake_reviews as &$fake_review)
          {
            $last_id++;

            $fake_review->id         = $last_id;
            $fake_review->name       = $fake_review->username;
            $fake_review->avatar     = "default.webp";
            $fake_review->created_at = format_date($fake_review->created_at, 'Y-m-d H:i:s');
            $fake_review->updated_at = $fake_review->created_at;
            $fake_review->parent     = null;
            $fake_review->content    = $fake_review->review;
            $fake_review->product_id = $product->id;
            $fake_review->user_id    = md5($fake_review->name);
            $fake_review->approved   = 1;
            $fake_review->rating     = $fake_review->rating;
            $fake_review->is_admin   = 0;

            unset($fake_comment->username, $fake_review->review);

            $fake_review = new Review((array)$fake_review);
          }

          $reviews = $reviews->merge(collect($fake_reviews))->sortByDesc('created_at');
          $ratings = $reviews->pluck('rating')->toArray();

          $product->rating = $ratings ? round(array_sum($ratings) / count($ratings)) : 0;
        }

        $comments = Comment::useIndex('product_id', 'approved')
                            ->selectRaw("comments.*, users.name, SUBSTR(users.email, 1, LOCATE('@', users.email)-1) as alias_name, CONCAT(users.firstname, ' ', users.lastname) AS fullname, IFNULL(users.avatar, 'default.webp') AS avatar, IF(users.role = 'admin', 1, 0) as is_admin, 
                              IF((SELECT COUNT(transactions.id) FROM transactions WHERE transactions.user_id = comments.user_id AND transactions.status = 'paid' AND transactions.refunded = 0 AND transactions.confirmed = 1 AND transactions.products_ids REGEXP CONCAT('\'', comments.product_id, '\'')) > 0, 1, 0) as item_purchased")
                            ->leftJoin('users', 'users.id', '=', 'comments.user_id')
                            ->where(['comments.product_id' => $product->id, 'comments.approved' => 1])
                            ->orderBy('id', 'ASC')->get();

        if($fake_comments = json_decode($product->fake_comments))
        {
          $product->comments_count = $comments->count() + count($fake_comments);

          $last_id = max($comments->pluck('id')->toArray() ?: [0]);

          foreach($fake_comments as &$fake_comment)
          {
            $last_id++;

            $fake_comment->id         = $last_id;
            $fake_comment->name       = $fake_comment->username;
            $fake_comment->avatar     = "default.webp";
            $fake_comment->created_at = format_date($fake_comment->created_at, 'Y-m-d H:i:s');
            $fake_comment->updated_at = $fake_comment->created_at;
            $fake_comment->parent     = null;
            $fake_comment->body       = $fake_comment->comment;
            $fake_comment->product_id = $product->id;
            $fake_comment->user_id    = md5($fake_comment->name);
            $fake_comment->approved   = 1;
            $fake_comment->item_purchase = null;
            $fake_comment->is_admin   = 0;

            unset($fake_comment->username, $fake_comment->comment);

            $fake_comment = new Comment((array)$fake_comment);
          }

          $comments = $comments->merge(collect($fake_comments))->sortByDesc('created_at');
        }

        $similar_products = Product::useIndex('primary', 'category', 'active')
                            ->selectRaw(implode(',', Self::$product_columns).', categories.name as category_name, categories.slug as category_slug')
                            ->leftJoin('categories', 'categories.id', '=', 'products.category')
                            ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('CONCAT("\'", subcategories.id, "\'")'))
                            ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                            ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                            ->leftJoin('product_price', function($join)
                            {
                              $join->on('product_price.license_id', '=', 'licenses.id')
                                   ->on('product_price.product_id', '=', 'products.id');
                            })
                            ->where(['products.category' => $product->category_id, 
                                     'products.active' => 1,
                                     'products.for_subscriptions' => 0])
                            ->where('products.id', '!=', $product->id)
                            ->groupBy('products.id', 'products.name', 'products.views', 'products.preview', 'products.preview_type',
                                     'products.slug', 'products.updated_at', 'products.hidden_content', 'products.active', 'products.stock', 'products.bpm', 'products.label', 'products.pages', 
                                     'products.authors', 'products.language', 'products.words', 'products.formats',
                                     'products.cover', 'product_price.price', 'products.last_update', 
                                     'category_name', 'category_slug', 'categories.id', 'promotional_price_time', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.country_city')
                            ->orderByRaw('rand()')
                            ->limit(5)->get();

        if($parents = $comments->where('parent', null)->sortByDesc('created_at')) // parents comments only
        {
          $children = $comments->where('parent', '!=', null); // children comments only

          // Append children comments to their parents
          $parents->map(function($item, $key) use ($children, $request, $product)
          {
            $request->merge(['item_type' => 'comment', 'item_id' => $item->id, 'product_id' => $product->id]);

            $item->reactions = $this->get_reactions($request); 

            $item->children = $children->where('parent', $item->id)->sortBy('created_at');

            foreach($item->children as $children)
            {
              $request->merge(['item_type' => 'comment', 'item_id' => $children->id, 'product_id' => $product->id]);

              $children->reactions = $this->get_reactions($request); 
            }
          });
        }

        if($product->country_city)
        {
          $country_city = json_decode($product->country_city);

          $product->country = $country_city->country ?? null;
          $product->city = $country_city->city ?? null;
        }

        if($product->screenshots)
        {
          $product->screenshots = array_reduce(explode(',', $product->screenshots), function($ac, $img)
                                  {
                                    $ac[] = asset_("storage/screenshots/{$img}");
                                    return $ac;
                                  }, []);
        }

        $product->tags = array_filter(explode(',', $product->tags));

        $product->additional_fields = json_decode($product->additional_fields);

        $product->faq = json_decode($product->faq, true) ?? [];

        if(count(array_column($product->faq, 'Q')))
        {
          $faqs = [];

          foreach($product->faq as $faq)
          {
            $faqs[] = [
              'question' => $faq['Q'] ?? '',
              'answer' => $faq['A'] ?? ''
            ];
          }

          $product->faq = $faqs;
        }

        $product->faq = arr2obj($product->faq);

        $subscriptions =  Pricing_Table::whereRaw("CASE 
                            WHEN pricing_table.products IS NOT NULL 
                              THEN FIND_IN_SET(?, pricing_table.products)
                            ELSE 1=1
                          END", [$product->id])->get();

        $product->cover = $product->cover ?? "default.webp";
        
        $json_ld = [
          "@context" => "https://schema.org/",
          "@type" => "Product",
          "name" => $product->name,
          "image" => route('resize_image', ['name' => pathinfo($product->cover, PATHINFO_FILENAME), 'size' => 256, "ext" => pathinfo($product->cover, PATHINFO_EXTENSION)]),
          "description" => $product->short_description,
          "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => $product->rating ?? 0,
            "reviewCount" => $product->reviews_count ?? 0,
          ],
          "offers" => [
            "@type" => "Offer",
            "availability" => "https://schema.org/".(out_of_stock($product) ? 'OutOfStock' : 'InStock'),
            "price" => 0,
            "priceCurrency" => config('payments.currency_code'),
          ],
        ];

        if(strtolower(pathinfo($product->cover, PATHINFO_EXTENSION)) === "svg")
        {
          unset($json_ld["image"]); 
        }

        if($product->for_subscriptions)
        {
          unset($json_ld["offers"]);
        }

        $data = [
          'subscriptions' => $subscriptions,
          'title'     => mb_ucfirst($product->name),
          'product'   => $product,
          'reviews'   => $reviews,
          'comments'  => $parents, // Parents comments with their children
          'similar_products' => $similar_products,
          'json_ld' => $json_ld
        ];

        Cache::put($request_id, $data, now()->addSeconds(3600));
      }

      extract($data);

      if(Auth::check())
      {
        $subscription = User_Subscription::useIndex('user_id', 'subscription_id')
                        ->selectRaw("
                          pricing_table.limit_downloads_same_item, user_subscription.transaction_id,
                          (user_subscription.ends_at IS NOT NULL AND user_subscription.ends_at < CURRENT_TIMESTAMP) as time_expired,
                          (pricing_table.limit_downloads > 0 AND user_subscription.downloads >= pricing_table.limit_downloads) as limit_downloads_reached,
                          (pricing_table.limit_downloads_per_day > 0 AND user_subscription.daily_downloads >= pricing_table.limit_downloads_per_day AND user_subscription.daily_downloads_date = CURDATE()) as daily_download_limit_reached,
                          IF(pricing_table.limit_downloads_same_item > 0, pricing_table.limit_downloads_same_item - IFNULL(subscription_same_item_downloads.downloads, 0), null) as remaining_downloads,
                          (pricing_table.limit_downloads_same_item > 0 AND subscription_same_item_downloads.downloads >= pricing_table.limit_downloads_same_item) as same_items_downloads_reached")
                          ->join('pricing_table', 'user_subscription.subscription_id', '=', 'pricing_table.id')
                          ->join('products', 'products.id', '=', DB::raw($product->id))
                          ->leftJoin(DB::raw('subscription_same_item_downloads USE INDEX(product_id, subscription_id)'), function($join) use($product)
                          {
                            $join->on('subscription_same_item_downloads.subscription_id', '=', 'user_subscription.id')
                                 ->where('subscription_same_item_downloads.product_id', $product->id);
                          })
                          ->join(DB::raw('transactions USE INDEX(primary)'), 'user_subscription.transaction_id', '=', 'transactions.id')
                          ->where(function($query)
                          {
                              $query->where('transactions.refunded', 0)
                                    ->orWhere('transactions.refunded', null);
                          })
                          ->where(['transactions.status' => 'paid', 'user_subscription.user_id' => Auth::id()])
                          ->whereRaw('CASE 
                                        WHEN pricing_table.products IS NOT NULL
                                          THEN FIND_IN_SET(?, pricing_table.products)
                                        ELSE 
                                          1=1
                                      END
                                        ', [$product->id])
                          ->having('time_expired', 0)
                          ->first();

        if($subscription)
        {
          $product->transaction_id               = $subscription->transaction_id;
          $product->valid_subscription           = 1;
          $product->remaining_downloads          = $subscription->remaining_downloads;
          $product->limit_downloads_reached      = $subscription->limit_downloads_reached;
          $product->daily_download_limit_reached = $subscription->daily_download_limit_reached;

          if($subscription->limit_downloads_reached || $subscription->daily_download_limit_reached)
          {
            $product->valid_subscription = 0;
          }
        }
      }
      
      $product->reviewed  = Auth::check() ? (Review::where(['product_id' => $product->id, 'user_id' => Auth::id()])->exists() ? 1 : 0) : 0;
      
      $puchase_conds = ['confirmed' => 1, 'status' => 'paid', 'refunded' => 0];

      if(!config('app.allow_download_in_test_mode'))
      {
        $puchase_conds['sandbox'] = 0;
      }

      $product->fill(['purchased' => 0, 'order_id' => null]);

      if(Auth::check())
      {
        $order = (Transaction::where((array_merge($puchase_conds, ['user_id' => Auth::id()])))->where('products_ids', 'LIKE', "%'{$product->id}'%")->first());
        
        $product->purchased = $order ? 1 : 0;
        $product->order_id = $order->id ?? null;
      }
      
      if(!$product->purchased && !$product->valid_subscription)
      {
        if($guest_token = $request->query('guest_token'))
        {
          $product->purchased = Transaction::useIndex('guest_token')
                                ->where(['guest_token' => $guest_token, 'status' => 'paid', 'refunded' => 0, 'confirmed' => 1])
                                ->where('transactions.products_ids', 'LIKE', "'%{$product->id}%'")
                                ->first();
        }
      }
 
      if($request->isMethod('POST'))
      {
        $type = $request->input('type');
        
        $redirect_url = $request->redirect_url ?? $request->server('HTTP_REFERER');

        if($type === 'reviews')
        {
          config('app.enable_reviews') ?? abort(404);

          if(!$product->purchased && !$product->valid_subscription) abort(404);

          $rating  = $request->input('rating');
          $review  = $request->input('review');
          $approved = auth_is_admin() ? 1 : (config('app.auto_approve.reviews') ? 1 : 0);

          if(!filter_var($rating, FILTER_VALIDATE_INT)) return redirect($redirect_url);

          if($request->post('edit_review_id'))
          {
            $_review = Review::where(['product_id' => $product->id, 'user_id' => Auth::id(), 'id' => $request->edit_review_id])->first();

            $_review->content = $request->post('review');
            $_review->rating = $request->post('rating');
            $_review->updated_at = date('Y-m-d H:i:s');
            $_review->approved = $approved;

            $_review->save();

            $redirect_url .= "#rev-{$_review}";
          }
          else
          {
            DB::insert("INSERT INTO reviews (product_id, user_id, rating, content, approved) VALUES (?, ?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE rating = ?, content = ?", 
                      [$product->id, $user_id, $rating, $review, $approved, $rating, $review]);

            if(!$approved)
              $request->session()->put(['review_response' => 'Your review is waiting for approval. Thank you!']);

            if(Auth::check() && !auth_is_admin() && config('app.admin_notifications.reviews'))
            {
                $mail_props = [
                  'data'   => ['text' => __('A new review has been posted by :user for :item.', ['user' => $request->user()->name ?? null, 'item' => $product->name]),
                               'subject' => __('You have a new review.'),
                               'user_email' => $request->user()->email],
                  'action' => 'send',
                  'view'   => 'mail.message',
                  'reply_to' => $request->user()->email,
                  'to'     => config('app.email'),
                  'subject' => __('You have a new review.')
                ];

                sendEmailMessage($mail_props, config('mail.mailers.smtp.use_queue'));
            }  
          }
          
        }
        elseif(preg_match('/^support|comments$/i', $type))
        {
          config('app.enable_comments') ?? abort(404);

          if(!$comment = $request->input('comment'))
          {
            return redirect($redirect_url);
          }

          $approved = auth_is_admin() ? 1 : (config('app.auto_approve.support') ? 1 : 0);
          $comment  = strip_tags($comment);

          if($request->edit_comment_id)
          {
            $edit_comment = Comment::where('id', $request->edit_comment_id)->where('user_id', Auth::id())
                            ->where('product_id', $product->id)->first();

            if($edit_comment)
            {
              $edit_comment->body       = $comment;
              $edit_comment->approved   = $approved;
              $edit_comment->updated_at = date("Y-m-d H:i:s");

              $edit_comment->save();
            }
          }
          else
          {
            if($request->comment_id) // parent
            {
              if($parent_comment = Comment::where('id', $request->comment_id)->where('parent', null)->where('product_id', $product->id)->first())
              {
                DB::insert("INSERT INTO comments (product_id, user_id, body, approved, parent) VALUES (?, ?, ?, ?, ?)", 
                            [$product->id, $user_id, $comment, $approved, $parent_comment->id]);    
              }
            }
            else
            {
              DB::insert("INSERT INTO comments (product_id, user_id, body, approved) VALUES (?, ?, ?, ?)", 
                          [$product->id, $user_id, $comment, $approved]); 
            }

            if(!$approved)
            {
              $request->session()->put(['comment_response' => __('Your comment is waiting for approval. Thank you!')]);
            }

            if(Auth::check() && !auth_is_admin() && config('app.admin_notifications.comments'))
            {
                $mail_props = [
                  'data'   => ['text' => __('A new comment has been posted by :user for :item.', ['user' => $request->user()->name ?? null, 'item' => $product->name]),
                               'subject' => __('You have a new comment.'),
                               'user_email' => $request->user()->email
                             ],
                  'action' => 'send',
                  'view'   => 'mail.message',
                  'to'     => config('app.email'),
                  'subject' => __('You have a new comment.')
                ];

                sendEmailMessage($mail_props, config('mail.mailers.smtp.use_queue'));
            }
          }
        }

        return redirect($redirect_url);
      }

      $custom_meta_tags = json_decode($product->meta_tags ?? '');

      config([
        "meta_data.title" => $custom_meta_tags->title ?? $product->name,
        "meta_data.description" => $custom_meta_tags->description ?? $product->short_description,
        "meta_data.image" => asset("storage/covers/{$product->cover}"),
        "meta_tags.keywords" => $custom_meta_tags->keywords ?? $product->tags,
        "json_ld" => $json_ld
      ]);

      DB::update('UPDATE products USE INDEX(primary) SET views = views+1 WHERE id = ?', [$product->id]);

      if(auth_is_admin() || $product->purchased || $product->valid_subscription)
      {
        $temp_direct_url = Temp_Direct_Url::where('product_id', $product->id)->first();

        if(!$temp_direct_url)
        {
          (new \App\Http\Controllers\ProductsController)->update_temp_direc_url($product);

          $temp_direct_url = Temp_Direct_Url::where('product_id', $product->id)->first();
        }

        $product->setAttribute('temp_direct_url', $temp_direct_url ? $temp_direct_url->url : ($product->direct_download_link ?? null));
      }

      if(!$product->for_subscriptions)
      {
        $response = $this->product_price($product, $regular_license->id);

        $product->fill(array_merge(["product_prices" => $response['product_prices']],
          $response['config'],
          ['has_keys' => $product->has_keys(), 'license_id' => ($regular_license->id ?? null)],
        ));
      }

      return view_('product', $data);
    }



    public function product_price($product_id, $license_id)
    {
        $product = $product_id instanceof Product ? $product_id : Product::find($product_id) ?? abort(404);

        $licenses         = collect(config('licenses', []));
        $regular_license  = collect($licenses)->where('regular', 1)->first();
        $product_prices   = Product_Price::where('product_id', $product->id)->get()->toArray();
        $product_prices   = array_combine(array_column($product_prices, 'license_id'), $product_prices);

        $free_time  = json_decode($product->free, 1) ?? [];
        $promo_time = json_decode($product->promotional_price_time, true) ?? [];

        foreach($product_prices as &$product_price)
        {
          if($license = $licenses->where('id', $product_price['license_id'])->first())
          {
            $product_price['license_id']   = $license->id;
            $product_price['license_name'] = $license->name;
            $product_price['regular']      = $license->regular;
            $product_price['has_promo']    = false;
            $product_price['promo_time']   = null;
            $product_price['is_free']      = 0;
            $product_price['free_time']    = null;

            if(count($free_time) && strtotime($free_time['from']) <= time() && strtotime($free_time['to']) >= time() && $regular_license->id === $product_price['license_id'])
            {
              $product_price['free_time']  = $free_time;
              $product_price['is_free']    = 1;
              $product_price['price']      = 0;
            }
            elseif(count($promo_time) && strtotime($promo_time['from']) <= time() && strtotime($promo_time['to']) >= time() && isset($product_price['promo_price']))
            {
              $product_price['promo_time']  = $promo_time;
              $product_price['has_promo']   = 1;
              $product_price['price']       = exchange_rate_required() ? convert_amount($product_price['price']) : $product_price['price'];
              $product_price['promo_price'] = exchange_rate_required() ? convert_amount($product_price['promo_price']) : $product_price['promo_price'];
            }
            elseif(isset($product_price['promo_price']))
            {
              $product_price['has_promo']   = 1;
              $product_price['price']       = exchange_rate_required() ? convert_amount($product_price['price']) : $product_price['price'];
              $product_price['promo_price'] = exchange_rate_required() ? convert_amount($product_price['promo_price']) : $product_price['promo_price'];
            }
            elseif(!($product_price['price'] > 0))
            {
              $product_price['is_free'] = 1;
            }
            else
            {
              $product_price['price'] = exchange_rate_required() ? convert_amount($product_price['price']) : $product_price['price'];
            }
          }
        }

        return [
          'product_prices' => $product_prices,
          'config' => [
            'price'      => $product_prices[$license_id]['price'] ?? null,
            'has_promo'  => $product_prices[$license_id]['has_promo'] ?? false,
            'promo_time' => $product_prices[$license_id]['promo_time'] ?? null,
            'is_free'    => $product_prices[$license_id]['is_free'] ?? false,
            'free_time'  => $product_prices[$license_id]['free_time'] ?? null,
          ]
        ];
    }




    public function product_with_permalink(Request $request)
    {
      $request->merge(['via_permalink' => 1]);

      return $this->product($request);
    }



    // Redirect old product URLs to new URLs
    public function old_product_redirect(Request $request)
    {
      $product = Product::where(['slug' => $request->slug, 'active' => 1])->first() ?? abort(404);

      return redirect(item_url($product));
    }



    public function prepaid_credits(Request $request)
    {
      $packs = Prepaid_Credit::orderBy('order', 'asc')->get();

      config([
        "meta_data.title" => __('Deposit cash'),
        "meta_data.description" => __('Add prepaid credits'),
        'json_ld' => [
          '@context' => 'http://schema.org',
          '@type' => 'WebPage',
          'name' => __('Prepaid credits'),
          'description' => __('Prepaid credits'),
          'url' => route('home.add_prepaid_credits'),
        ],
      ]);

      return view_('prepaid_credits', compact('packs'));
    }



    // Trending products
    public static function trending_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('trending', 'active')
                          ->selectRaw(implode(',', Self::$product_columns))
                          ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                          ->leftJoin('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                          ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')
                                 ->on('product_price.product_id', '=', 'products.id');
                          })
                          ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'promotional_price_time', 'products.stock', 'products.bpm', 'products.label',
                                    'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'products.trending', 'products.hidden_content', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.country_city')
                          ->havingRaw("active = 1 AND (trending = 1 OR count(transactions.id) > 0)")
                          ->orderByRaw('trending, sales DESC');
                          
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;

      return $returnQueryBuilder ? $products : $products->limit($limit)->get();                                
    }


    // Featured products
    private static function featured_products(bool $returnQueryBuilder, $limit = 15, $category_id = null, $randomize = false)
    {
      $products = Product::useIndex('featured', 'active')
                          ->selectRaw(implode(',', Self::$product_columns))
                          ->leftJoin('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('transactions', 'transactions.products_ids', 'LIKE', \DB::raw("CONCAT(\"%'\", products.id,\"'%\")"))
                          ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                          ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                          })
                          ->where(['products.featured' => 1, 'active' => 1])
                          ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'promotional_price_time', 'products.stock', 'products.bpm', 'products.label', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'products.featured', 'products.hidden_content', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.country_city');
      
      $products = $category_id ? $products->where('category', $category_id) : $products;
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products->orderByRaw("products.featured DESC, products.id DESC");
      
      return $returnQueryBuilder ? $products : $products->limit($limit)->get();   
    }



    // Newest products
    private static function newest_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('newest', 'active')
                          ->selectRaw(implode(',', Self::$product_columns).', products.newest')
                          ->leftJoin('transactions', 'transactions.products_ids', 'LIKE', \DB::raw("CONCAT(\"%'\", products.id,\"'%\")"))
                          ->join('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                          ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                          })
                          ->where(['active' => 1])
                          ->groupBy('products.id', 'products.name','products.views','products.newest','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'promotional_price_time', 'products.hidden_content', 'products.stock', 'categories.id', 'products.tags', 'licenses.id', 'licenses.name', 'products.pages', 'products.bpm', 'products.label', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.country_city');
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products->orderByRaw("products.newest, products.id DESC");
      
      if($returnQueryBuilder)
      {
        return $returnQueryBuilder;
      }

      $products = $products->limit($limit)->get();

      /*foreach($products as &$product)
      {
        if(!is_null($product->price))
        {
          $price = is_null($product->promotional_price) ? $product->price : $product->promotional_price;

          $product->price = price($price, false, false, 0, null, null);
        }
      }*/

      return $products;
    }


    // Free products
    private static function free_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('free', 'active')
                          ->selectRaw(implode(',', Self::$product_columns))
                          ->leftJoin('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                          ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                          })
                          ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                          ->where(['active' => 1])
                          ->where(function ($query)
                          {
                            $query->where('product_price.price', 0)
                                  ->orWhereRaw("CURRENT_DATE between substr(free, 10, 10) and substr(free, 28, 10)");
                          })
                          ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'promotional_price_time', 'products.hidden_content', 'products.stock', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.bpm', 'products.label', 'products.country_city');
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;
      
      return $returnQueryBuilder ? $products : $products->limit($limit)->get();   
    }


    // Flash products
    private static function flash_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('free', 'active')
                    ->selectRaw(implode(',', Self::$product_columns))
                    ->leftJoin('categories', 'categories.id', '=', 'products.category')
                    ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                    ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                    ->where(['active' => 1])
                    ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                    ->leftJoin('product_price', function($join)
                    {
                      $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                    })
                    ->where('product_price.promo_price', '!=', null)
                    ->whereRaw('promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10))')
                    ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'promotional_price_time', 'products.hidden_content', 'products.stock', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.bpm', 'products.label', 'products.country_city');
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;
      
      return $returnQueryBuilder ? $products : $products->limit($limit)->get();   
    }



    // Blog 
    public function blog(Request $request)
    {      
      $filter = [];

      config([
        "meta_data.title" => config('app.blog.title'),
        "meta_data.description" => config('app.blog.description'),
        "meta_data.image" => asset('storage/images/'.(config('app.blog_cover') ?? 'blog_cover.jpg')),
      ]);

      if($request->category)
      {
        if(!$category = Category::useIndex('slug')->where('slug', $request->category)->first())
          abort(404);

        $posts = Post::useIndex('category')->where(['category' => $category->id, 'active' => 1]);

        $filter = ['name' => 'Category', 'value' => $category->name];

        config([
          "meta_data.title" => config('app.name').' Blog - '.$category->name,
          "meta_data.description" => $category->description,
        ]);
      }
      elseif($request->tag)
      {
        $posts = Post::useIndex('tags')->where(function ($query) use ($request) {
                                                        $tag = str_replace('-', ' ', $request->tag);

                                                        $query->where('tags', 'LIKE', "%{$request->tag}%")
                                                              ->orWhere('tags', 'like', "%{$tag}%");
                                                   })
                                                   ->where('active', 1);

        $filter = ['name' => 'Tag', 'value' => $request->tag];

        config([
          "meta_data.title" => config('app.name').' Blog - '.$request->tag,
        ]);
      }
      elseif($request->q)
      {
        $request->tag = str_replace('-', ' ', $request->tag);
        $posts = Post::useIndex('search', 'active')->where(function ($query) use ($request) {
                                                         $query->where('name', 'like', "%{$request->q}%")
                                                               ->orWhere('tags', 'like', "%{$request->q}%")
                                                               ->orWhere('short_description', 'like', "%{$request->q}%")
                                                               ->orWhere('content', 'like', "%{$request->q}%")
                                                               ->orWhere('slug', 'like', "%{$request->q}%");
                                                     })
                                                     ->where('active', 1);

        $filter = ['name' => 'Search', 'value' => $request->q];

        config([
          "meta_data.title" => config('app.name').' '.__('Blog').' - '.__('Searching for').' '.$request->q,
          "meta_data.description" => $category->description,
        ]);
      }
      else
      {
        $posts = Post::useIndex('primary')->where('active', 1);
      }

      $posts = $posts->orderBy('id', 'desc')->paginate(9);

      if($filter) settype($filter, 'object');

      $posts_categories = Category::useIndex('`for`')->select('name', 'slug')->where('categories.for', 0)->get();

      $latest_posts = Post::useIndex('primary', 'active')
                      ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                      ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                      ->where('posts.active', 1)->orderBy('updated_at')->limit(5)->get();

      $posts_tags = Post::useIndex('active')->select('tags')->where('active', 1)->orderByRaw('rand()')
                                  ->limit(10)->get()->pluck('tags')->toArray();

      $tags  = [];

      foreach($posts_tags as $tag)
        $tags = array_merge($tags, array_map('trim', explode(',', $tag)));

      $tags = array_unique($tags);

      config([
        'json_ld' => [
          '@context' => 'http://schema.org',
          '@type' => 'Blog',
          '@id' => route('home.blog'),
          'name' => __('Blog'),
          'url' => route('home.blog'),
          'image' => asset("storage/images/" . config('blog_cover')),
          'description' => config('app.blog.description'),
        ]
      ]);

      return view_('blog', compact('posts_categories', 'latest_posts', 'tags', 'posts', 'filter'));
    }


    // BLOG POST
    public function post(string $slug)
    {
      $post = Post::useIndex('slug', 'active')->select('posts.*', 'categories.name AS category', 'posts.category as category_id')
                  ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                  ->where(['posts.slug' => $slug, 'posts.active' => 1])->first() ?? abort(404);

      config([
        "meta_data.title" => $post->name,
        "meta_data.description" => $post->short_description,
        "meta_data.image" => asset('storage/posts/'.$post->cover)
      ]);

      $post->setTable('posts')->increment('views', 1);

      $latest_posts = Post::useIndex('primary', 'active')
                      ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                      ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                      ->where('posts.id', '!=', $post->id)->where('posts.active', 1)->orderBy('updated_at')->limit(5)->get();

      $related_posts =  Post::useIndex('primary', 'active')
                        ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                        ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                        ->where('posts.id', '!=', $post->id)->where('posts.active', 1)
                        ->where('posts.category', $post->category_id)->orderBy('updated_at')->limit(6)->get();


      $posts_categories = Category::useIndex('`for`')->select('name', 'slug')->where('categories.for', 0)->get();
      $posts_tags       = Post::useIndex('active')->select('tags')->where('active', 1)->orderByRaw('rand()')
                                  ->limit(10)->get()->pluck('tags')->toArray();

      $tags  = [];

      foreach($posts_tags as $tag)
      {
        $tags = array_merge($tags, array_map('trim', explode(',', $tag)));
      }

      $tags = array_unique($tags);

      config([
        'json_ld' => [
          '@context' => 'https://schema.org/',
          '@type' => 'BlogPosting',
          '@id' => url()->current(),
          'mainEntityOfPage' => url()->current(),
          'headline' => $post->name,
          'name' => $post->name,
          'description' => $post->short_description,
          'datePublished' => $post->created_at,
          'dateModified' => $post->updated_at,
          'image' => [
            '@type' => 'ImageObject',
            '@id' => asset("storage/images/{$post->cover}"),
            'url' => asset("storage/images/{$post->cover}"),
            'height' => 500,
            'width' => 500,
          ],
          'url' => url()->current(),
          'wordCount' => str_word_count(strip_tags($post->content)),
          'keywords' => array_filter(explode(',', $post->keywords)),
        ]
      ]);

      return view_('post', compact('post', 'posts_categories', 'latest_posts', 'related_posts', 'tags'));
    }


    // Send email verification link
    public function send_email_verification_link(Request $request)
    {
      $notifiable = $request->email ? User::where('email', $request->email)->first() : $request->user();
      $notifiable->sendEmailVerificationNotification();

      return response()->json([
        'status' => true, 
        'message' => __('Please check your :email inbox for a verification link.', ['email' => $request->email])
      ]);
    }


    private function get_random_product()
    {
      return  Product::selectRaw('products.name as item_name, products.slug, products.cover, products.id, product_price.price')
              ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
              ->join('product_price', function($join)
              {
                $join->on('product_price.license_id', '=', 'licenses.id')
                     ->on('product_price.product_id', '=', 'products.id');
              })
              ->where('product_price.price', '>', '0')
              ->where('products.active', 1)
              ->orderByRaw('RAND()')
              ->first();
    }



    public function live_sales(Request $request)
    {
        $response = [];

        $i_min = config('app.fake_purchases.interval.min', 10);
        $i_max = config('app.fake_purchases.interval.max', 20);

        $fake_profiles = shuffle_array(config('app.fake_profiles', []));

        $response = ['status' => 0, 'sale' => []];

        $product = $this->get_random_product();

        if($product && $fake_profiles)
        {
          $response['status'] = 1;
          $product->price = price($product->price, false, true, 2, 'code');
          $product->cover = asset_("storage/covers/{$product->cover}");
          $product->url   = item_url($product);

          $response['sale'] = $product->toArray();
          $fake_profile = $fake_profiles[rand(0, (count($fake_profiles) - 1))];
          $response['sale'] = array_merge($response['sale'], $fake_profile);
        }

        return json($response);
    }



    public function invoice(Request $request)
    {
      $required_params = ["buyer_name", "buyer_email", "date", "reference", "items", "subtotal", "fee", "tax", 
                        "discount", "total_due", "refunded", "is_subscription", "currency"];

      $missing_params = array_diff($required_params, array_keys($_GET));

      if(count($missing_params))
      {
          abort(403, __("The folowing parameters are missing (:params)", ['params' => implode(', ', $missing_params)]));
      }

      $content  = base64_encode(file_get_contents(public_path("storage/images/".config('app.logo'))));
      $mimetype = mime_content_type(public_path("storage/images/".config('app.logo')));

      config(["logo_b64" => "data:{$mimetype};base64,{$content}"]);

      return view('invoices.template_2', $_GET);
    }



    public function export_invoice(Request $request)
    {
      $transaction_id = $request->itemId ?? abort(404);
      $transaction = Transaction::find($transaction_id) ?? abort(404);
      $buyer = User::find($transaction->user_id) ?? abort(404);

      if(!$details = json_decode($transaction->details, true))
      {
        return back();
      }

      $items = array_filter($details['items'], function($k)
      {
        return is_numeric($k);
      }, ARRAY_FILTER_USE_KEY);

      $fee       = $details['items']['fee']['value'] ?? 0;
      $tax       = $details['items']['tax']['value'] ?? 0;
      $discount  = $details['items']['discount']['value'] ?? 0;
      $subtotal  = array_sum(array_column($items, 'value'));
      $total_due = $details['total_amount'];
      $currency  = $details['currency'];
      $refunded  = $transaction->refunded;
      $reference = $transaction->reference_id ?? $transaction->order_id ?? $transaction->transaction_id;
      $is_subscription = $transaction->is_subscription;
      $custom_amount = $transaction->custom_amount;

      $data = compact('items', 'fee', 'tax', 'discount', 'subtotal', 'currency', 'is_subscription',
                      'total_due', 'reference', 'transaction', 'buyer', 'refunded', 'custom_amount');

      $invoice_data = http_build_query([
        'items' => $items,
        'fee' => $fee,
        'tax' => $tax,
        'discount' => $discount,
        'subtotal' => $subtotal,
        'currency' => $currency,
        'is_subscription' => $is_subscription,
        'total_due' => $total_due,
        'reference' => $reference,
        'date' => $transaction->created_at->format('Y-m-d'),
        'buyer_name' => $buyer->name,
        'buyer_email' => $buyer->email,
        'refunded' => $refunded,
        'custom_amount' => $custom_amount
      ]);

      if(config('app.invoice.template') == 2)
      {
        return json(['url' => route('home.invoice', $invoice_data)]);
      }

      $pdf = \PDF::loadView('invoices.template_1', compact('items', 'fee', 'tax', 'discount', 'subtotal', 'currency', 'is_subscription',
                                              'total_due', 'reference', 'transaction', 'buyer', 'refunded', 'custom_amount'));
      
      return $pdf->download('invoice.pdf'); // stream | download
    }



    // Download
    public function download(Request $request)
    {
      set_time_limit(1800); // 30 mins

      $order_id = is_numeric($request->order_id) ? $request->order_id : abort(404);
      $user_id  = (Str::isUuid($request->user_id) || is_numeric($request->user_id)) ? $request->user_id : abort(404);
      $item_id  = is_numeric($request->item_id) ? $request->item_id : abort(404);
      $type     = strtolower($request->type);

      if(!in_array($type, ['file', 'license', 'key']))
      {
        abort(403, __('Wrong request type.')); 
      }

      $transaction =  Transaction::where(['id' => $order_id])->where(function($query) use($user_id)
                      {
                        $query->where('user_id', $user_id)
                              ->orWhere('guest_token', $user_id);
                      })->first();

      $item = Product::where(['active' => 1, 'id' => $item_id])->first() ?? abort(404);

      if($type === 'file' && (!$item->file_name && !$item->direct_download_link))
      {
        abort(403, __('No file was uploaded for this item yet. Please contact support at :email', ['email' => config('app.email')]));
      }

      $item->setAttribute('can_download', auth_is_admin() ? 1 : 0);

      $response = $this->product_price($item, collect(config('licenses', []))->where('regular', 1)->first()->id);

      $product_prices = $response['product_prices'];

      if($response['config']['is_free'] ?? null)
      {
        if(!Auth::check() && config('app.authentication_required_to_download_free_items') && !Str::isUuid($request->user_id))
        {
          abort(403, __('You must be logged in to download free items'));
        }

        $item->can_download = 1;
      }

      if(!$item->can_download)
      {
        if(\Auth::check() && (Auth::id() != $user_id))
        {
          abort(403, __("You are not allowed to download this file"));
        }

        $transaction ?? abort(404);

        if($transaction->status != "paid" || $transaction->confirmed != 1)
        {
          abort(403, __('The payment for your order :number is not confirmed yet, please try again later.', ['number' => $transaction->reference_id]));
        }

        if($transaction->sandbox == 1 && !config('app.allow_download_in_test_mode'))
        {
          abort(403, __('Download not allowed with test orders'));
        }

        if($transaction->refunded == 1)
        {
          abort(403, __('This Order :number has been refunded', ['number' => $transaction->reference_id]));
        }

        if($transaction->type == "product")
        {
          $products_ids = array_map('trim', explode(',', str_ireplace("'", "", $transaction->products_ids)));
          
          in_array($item_id, $products_ids) ?? abort(404);

          $item->can_download = 1;
        }
        elseif($transaction->type == "subscription")
        {
          $item->can_download = 1;

          $subscription = User_Subscription::where('transaction_id', $order_id)->first() ?? abort(404);

          $ends_at_time   = strtotime($subscription->ends_at);
          $now_time       = strtotime("now");

          if($ends_at_time < $now_time)
          {
            abort(403, __('Subscription expired'));
          }

          $pricing_table       = Pricing_Table::where('id', $subscription->subscription_id)->first() ?? abort(404);
          $same_item_downloads = Subscription_Same_Item_Downloads::where(['subscription_id' => $subscription->id, 'product_id' => $item_id])->first();

          if($pricing_table->limit_downloads_same_item > 0)
          {
            if(!$same_item_downloads)
            {
              $same_item_downloads = Subscription_Same_Item_Downloads::create([
                'subscription_id' => $subscription->id,
                'product_id' => $item_id,
                'downloads' => 0
              ]);
            }

            if($same_item_downloads->downloads == $pricing_table->limit_downloads_same_item)
            {
              abort(403, __('Download limit reached'));
            }
          }

          if($pricing_table->limit_downloads_per_day > 0)
          {
            if(is_null($subscription->daily_downloads_date))
            {
              $subscription->daily_downloads_date = date('Y-m-d');
              $subscription->daily_downloads += 1; 

              $subscription->save();
            }
            else
            {
              if(strtotime($subscription->daily_downloads_date) < strtotime("now"))
              {
                $subscription->daily_downloads_date = date('Y-m-d');
                $subscription->daily_downloads = 0;

                $subscription->save();
              }
              elseif($subscription->daily_downloads_date == date("Y-m-d"))
              {
                if($subscription->daily_downloads == $pricing_table->limit_downloads_per_day)
                {
                  abort(403, __('Download limit reached for today'));
                }
                else
                {
                  $subscription->daily_downloads += 1; 

                  $subscription->save();
                }
              }
            }
          }
          
          if($pricing_table->categories)
          {
            $categories = array_filter(explode(',', $pricing_table->categories));
            $categories = filter_var_array($categories, FILTER_VALIDATE_INT);

            // Requested item belongs to valid subscription category
            if(!$valid_subscription_category = Product::select('id')->whereIn('products.category', $categories)->where('products.id', $item_id)->exists())
            {
              abort(403, __('You are not allowed to download this item'));
            }
          }
        }
      }

      if($item->can_download)
      {
        if($type == "file")
        {
          if($item->direct_download_link)
          {
              return redirect()->away($item->direct_download_link);
          }
          
          if($item->file_host == 'local')
          {          
            if(file_exists(storage_path("app/downloads/{$item->file_name}")))
            {
              return response()->streamDownload(function() use($item)
              {
                readfile(storage_path("app/downloads/{$item->file_name}"));
              }, "{$item->slug}.{$item->file_extension}", ["Content-Type" => "application/octet-stream"]);
            }
          }
          else
          {
            $host_class = [
              'dropbox'   => 'DropBox',
              'google'    => 'GoogleDrive',
              'yandex'    => 'YandexDisk',
              'amazon_s3' => 'AmazonS3',
              'wasabi'    => 'Wasabi',
              'gcs'       => "GoogleCloudStorage",
            ];

            $class_name = $host_class[$item->file_host];

            try
            {
              $config = [
                "item_id"    => $item->file_name,
                "cache_id"   => $item->id,
                "file_name"  => "{$item->id}-{$item->slug}.{$item->file_extension}",
                "expiry"     => null,
                "bucketName" => null,
                "bucket"     => null,
                "options"    => null,
              ];

              $response = call_user_func(["\App\Libraries\\{$class_name}", 'download'], $config);

              if(config('app.force_download'))
              {
                header("Content-disposition:attachment; filename={$config['file_name']}");
                readfile($response->getTargetUrl());
                exit;
              }

              return $response;
            }
            catch(\Exception $e)
            {
              abort(403, __('Could not download the main file. Please contact support at :email', ['email' => config('app.email')]));
            }
          }
        }
        elseif($type == "license")
        {
          try 
          {
            $license = json_decode(decrypt(urldecode($request->query('content')), false));

            $user_email = $transaction->user_id ? User::find($transaction->user_id)->email : $transaction->guest_email;

            $replaces = [
              "{LICENSE_NAME}"  => $license->name,
              "{APP_NAME}"      => config('app.name'),
              "{APP_OWNER}"     => config('app.email'),
              "{BUYER_EMAIL_ADDRESS}" => $user_email,
              "{ITEM_NAME}"     => $item->name,
              "{ITEM_ID}"       => $item->id,
              "{ITEM_URL}"      => item_url($item),
              "{LICENSE_KEY}"   => $license->license,
              "{PURCHASE_DATE}" => $transaction->created_at,
              "{CONTACT_PAGE_URL}" => route('home.support')
            ];

            $license_model = file_get_contents(resource_path("extra/license_model.txt"));
            $license_file  = str_ireplace(array_keys($replaces), array_values($replaces), $license_model);

            return response()->streamDownload(function() use($license_file)
            {
              echo $license_file;
            }, "{$item->id}-{$item->slug}-license.txt")->send();
          }
          catch(\Exception $e)
          {
            abort(403, __('Could not download the license key. Please contact support at :email', ['email' => config('app.email')]));
          }
        }
        elseif($type == "key")
        {
          try
          {
            $key = decrypt(urldecode($request->query('content')), false);

            return response()->streamDownload(function() use($key)
            {
              echo $key;
            }, "{$item->id}-{$item->slug}-key.txt")->send();
          }
          catch(\Exception $e)
          {
            abort(403, __('Could not download the key. Please contact support at :email', ['email' => config('app.email')]));
          }
        }
      }

      return redirect('/');
    }




    public function download_license(Request $request)
    {
      $request->validate(['itemId' => 'required|numeric']);

      $item_id = $request->itemId;
      $product = Product::find($item_id) ?? abort(404);
      $user    = null;

      $transaction = Transaction::useIndex('user_id')
                      ->whereRaw('products_ids REGEXP ?', [wrap_str($item_id, "'")])
                      ->where([ 'is_subscription' => 0, 
                                'refunded' => 0, 
                                'status' => 'paid', 
                                'confirmed' => 1])
                      ->orderBy('id', 'desc');

      if(!Auth::check())
      {
        $request->validate(['access_token' => 'required|uuid']);

        $transaction->where('guest_token', $request->post('access_token'));
      }
      else
      {
        $this->middleware('auth');
      }

      $transaction = $transaction->first();

      $user = Auth::check() ? $request->user()->email : ($transaction->guest_token ?? null);

      if($transaction)
      {
        if($licenses = json_decode($transaction->licenses))
        {
          $licenses_ids   = array_filter(explode(',', str_replace("'", "", $transaction->licenses_ids)));
          $licenses_names = License::whereIn('id', $licenses_ids)->get()->pluck('name')->toArray();
          $products_ids   = str_ireplace("'", "", explode(',', $transaction->products_ids));
          $item_index     = array_search($item_id, $products_ids);
          $license_name   = $licenses_names[$item_index] ?? null;

          if(property_exists($licenses, $item_id) && $license_name)
          {
            $license_key = $licenses->$item_id;
            $file_name   = "license_key_{$product->slug}.txt";

            $replaces = [
              "{LICENSE_NAME}"  => $license_name,
              "{APP_NAME}"      => config('app.name'),
              "{APP_OWNER}"     => config('app.email'),
              "{BUYER_EMAIL_ADDRESS}" => $user,
              "{ITEM_NAME}"     => $product->name,
              "{ITEM_ID}"       => $product->id,
              "{ITEM_URL}"      => item_url($product),
              "{LICENSE_KEY}"   => $license_key,
              "{PURCHASE_DATE}" => $transaction->created_at,
              "{CONTACT_PAGE_URL}" => route('home.support')
            ];

            $license_model = file_get_contents(resource_path("extra/license_model.txt"));
            $license_file  = str_ireplace(array_keys($replaces), array_values($replaces), $license_model);

            return response()->streamDownload(function() use($license_file)
            {
              echo $license_file;
            }, $file_name)->send();
          }
        }
      }

      return back();
    }



    // Support
    public function support(Request $request)
    {
      if($request->method() === 'POST')
      {
        $rules = [
          'email' => 'required|email|bail',
          'subject' => 'required|bail',
          'message' => 'required'
        ];

        if(captcha_is_enabled('contact'))
        {
          if(captcha_is('mewebstudio'))
          {
            $rules['captcha'] = 'required|captcha';
          }
          elseif(captcha_is('google'))
          {
            $rules['g-recaptcha-response'] = 'required';
          }
        }

        $request->validate($rules, [
            'g-recaptcha-response.required' => __('Please verify that you are not a robot.'),
            'captcha.required' => __('Please verify that you are not a robot.'),
            'captcha.captcha' => __('Wrong captcha, please try again.'),
        ]);

        $user_email = $request->input('email');

        $email = Support_Email::insertIgnore(['email' => $user_email]);

        if(!($email->id ?? null))
        {
          $email = Support_Email::where('email', $user_email)->first();
        }

        $support = new Support();

        $support->email_id = $email->id;
        $support->subject  = strip_tags($request->input('subject'));
        $support->message  = strip_tags($request->input('message'));

        $support->save();

        $mail_props = [
          'data'   => ['subject' => $support->subject, 'text' => $support->message, 'user_email' => $user_email],
          'action' => 'send',
          'view'   => 'mail.message',
          'to'     => config('app.email'),
          'subject' => $support->subject,
          'reply_to' => $user_email,
          'forward_to' => config('mail.forward_to')
        ];

        sendEmailMessage($mail_props, config('mail.mailers.smtp.use_queue'));

        $request->session()->flash('support_response', __('Message sent successfully'));

        return redirect()->route('home.support');
      }

      $faqs = Faq::useIndex('active')->where('active', 1)->get();

      config([
        "meta_data.title" => __('Support'),
        "meta_data.description" => __('Support'),
        "json_ld" => [
          '@context' => 'http://schema.org',
          '@type' => 'WebPage',
          'name' => __('Support and FAQ'),
          'description' => __('Support and FAQ'),
          'url' => url()->current()
        ]
      ]);

      $support = Page::where('slug', 'support')->first();

      return view_('support', compact('faqs', 'support'));
    }



    // Pricing
    public function subscriptions(Request $request)
    {
      $subscriptions = Pricing_Table::useIndex('position')->orderBy('position', 'asc')->get();

      $active_subscription = null;

      config([
        "meta_data.title" => __('Pricing - :app_name', ['app_name' => config('app.name')]),
        "meta_data.description" => __('Pricing - :app_name', ['app_name' => config('app.name')]),
        "json_ld" => [
          '@context' => 'http://schema.org',
          '@type' => 'WebPage',
          'name' => __('Pricing'),
          'description' => __('Pricing plans'),
          'url' => url()->current()
        ]
      ]);

      if(Auth::check() && !config('app.subscriptions.accumulative'))
      {
        $user_subscription =  User_Subscription::useIndex('user_id', 'subscription_id')
                              ->select('user_subscription.id')
                              ->join(DB::raw('pricing_table USE INDEX(primary)'), 'pricing_table.id', '=', 'user_subscription.subscription_id')
                              ->join(DB::raw('transactions USE INDEX(products_ids, is_subscription)'), function($join)
                              {
                                $join->on('transactions.products_ids', '=', DB::raw('QUOTE(pricing_table.id)'))
                                     ->where('transactions.is_subscription', '=', 1);
                              })
                              ->where('user_subscription.user_id', Auth::id())
                              ->whereRaw("user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP < user_subscription.ends_at")
                              ->where(function($query)
                              {
                                $query->where('transactions.refunded', '0')
                                      ->orWhere('transactions.refunded', null);
                              })
                              ->first();

        $active_subscription = $user_subscription ? true : false;
      }

      foreach($subscriptions as &$subscription)
      {
        $subscription->specifications = json_decode($subscription->specifications); 
      }

      return view_('pricing', compact('subscriptions', 'active_subscription'));
    }


    // Get checkout form
    public function checkout_form(Request $request)
    {
      $form_fields = [];

      $processor = $request->post('processor');

      $form_config = config("payment_gateways.{$processor}.form.inputs", []);
      $checkout_token = md5(uuid6());

      if($request->post('subscriptionId'))
      {
        $form_config['subscription_id']['value'] = $request->post('subscriptionId');
      }
      elseif($request->post('prepaidCreditsPackId'))
      {
        $form_config['prepaid_credits_pack_id']['value'] = $request->post('prepaidCreditsPackId'); 
      }
      else
      {
        $form_config['cart']['value'] = base64_encode(json_encode($request->post('cart'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
      }
      
      $form_config['processor']['value']      = $request->post('processor');
      $form_config['coupon']['value']         = $request->post('coupon');
      $form_config['locale']['value']         = $request->post('locale', get_locale());
      $form_config['_token']['value']         = $request->post('_token');
      $form_config['checkout_token']['value'] = $checkout_token;


      if($processor === 'n-a')
      {
        foreach($form_config as $name => $props)
        {
          $form_fields[] = <<<FIELD
            <input type="hidden" name="{$name}" value="{$props['value']}" class="d-none">
          FIELD;
        }
      }
      elseif(config("payment_gateways.{$processor}.form.inputs", []))
      { 
        foreach($form_config as $name => $props)
        {
          $attrs = implode(' ', $props['attributes'] ?? []);

          if($props['replace'])
          {
            $props['value'] = str_replace_adv($props['value'], $props['replace']);
          }

          if($props['type'] == 'hidden')
          {
            $form_fields[] = <<<FIELD
              <input type="{$props['type']}" name="{$name}" value="{$props['value']}" class="{$props['class']}" {$attrs}>
            FIELD;
          }
          elseif($props['type'] != 'hidden')
          {
            if($props['label'])
            {
              $label = __($props['label']);
            
              $form_fields[] = <<<FIELD
                <div class="field {$props['class']}">
                  <label>{$label}</label>
                  <input type="{$props['type']}" name="{$name}" value="{$props['value']}" {$attrs}>
                </div>
              FIELD;
            }
            else
            {
              $form_fields[] = <<<FIELD
                <div class="field {$props['class']}">
                  <input type="{$props['type']}" name="{$name}" value="{$props['value']}" {$attrs}>
                </div>
              FIELD;
            }
          }
        }
      }

      $minimum_amount = config("payments.gateways.{$processor}.minimum");

      if(config('pay_what_you_want.enabled') && array_filter(config('pay_what_you_want.for')) && !is_null($minimum_amount) && ($processor != 'credits'))
      {
        if((($request->post('subscriptionId') && config('pay_what_you_want.for.subscriptions'))
                    || ($request->post('cart') && config('pay_what_you_want.for.products'))) && !$request->post('prepaidCreditsPackId'))
        {
          $label   = __('Pay what you want');
          $placeholder = __('Minimum :amount', ['amount' => price($minimum_amount, false)]);

          $form_fields[] = <<<FIELD
            <div class="field custom_amount">
              <label>{$label}</label>
              <input type="number" name="custom_amount" placeholder="{$placeholder}">
            </div>
          FIELD;   
        }
      }

      if(config('payments.guest_checkout') && !\Auth::check())
      {
        $label   = __("Email address");
        $placeholder = __('To receive download links.');

        $form_fields[] = <<<FIELD
          <div class="field guest-email">
            <label>{$label}</label>
            <input name="guest_email" value="" required placeholder="{$placeholder}" type="email">
          </div>
        FIELD;
      }

      if(config('payments.buyer_note') && !$request->post('subscriptionId') && !$request->post('prepaidCreditsPackId'))
      {
        $label   = __('Notes')."<sup>(".__('Optional').")</sup>";
        $placeholder = __('Add some notes for this order.');

        $form_fields[] = <<<FIELD
          <div class="field notes">
            <label>{$label}</label>
            <textarea name="notes" rows="5" placeholder="{$placeholder}"></textarea>
          </div>
        FIELD;
      }

      if(config('payments.tos'))
      {
        $label   = __('I agree to the');
        $name    = __('Terms and conditions');
        $tos_url = config('payments.tos_url');

        $form_fields[] = <<<FIELD
          <div class="ui checkbox terms">
            <input type="checkbox" name="tos" required>
            <label>
              $label
              <a href="$tos_url" class="ml-1-qt">$name</a>
            </label>
          </div>
        FIELD;
      }
      
      return json(["form" => implode(PHP_EOL, $form_fields)]);
    }


    // Checkout
    public function checkout(Request $request)
    {
      if(Session::has('transaction_details') && ($request->query('token')))
      {
          $transaction_details = Session::pull('transaction_details');
          $payment_processor = Session::pull('payment_processor');
          $cart              = Session::pull('cart');
          $coupon            = Session::pull('coupon');
          $subscription_id   = Session::pull('subscription_id');
          $products_ids      = Session::pull('products_ids');

          $transaction = new Transaction;

          $transaction->reference_id      = generate_transaction_ref();
          $transaction->user_id           = Auth::check() ? Auth::id() : null;
          $transaction->updated_at        = date('Y-m-d H:i:s');
          $transaction->processor         = $payment_processor;
          $transaction->details           = json_encode($transaction_details, JSON_UNESCAPED_UNICODE);
          $transaction->amount            = $transaction_details['total_amount'];
          $transaction->discount          = $coupon->coupon->discount ?? 0;
          $transaction->exchange_rate     = $transaction_details['exchange_rate'] ?? 1;
          $transaction->guest_token       = Auth::check() ? null : uuid6();
          $transaction->items_count       = count($cart);
          $transaction->status            = 'canceled';

          if(($transaction_details['currency'] != config('payments.currency_code')) && $transaction->exchange_rate != 1)
          {
            $transaction->amount = format_amount($transaction_details['total_amount'] / $transaction->exchange_rate, true);
          }

          if($coupon->status)
          {
            $transaction->coupon_id = $coupon->coupon->id;
          }

          if($subscription_id)
          {
            $subscription = array_shift($cart);

            $subscription = Pricing_Table::find($subscription->id) ?? abort(404);

            $transaction->is_subscription = 1;
            $transaction->products_ids    = wrap_str($subscription->id);
            $transaction->guest_token     = null;
            $transaction->items_count     = 1;
          }
          else
          {
            $transaction->products_ids = implode(',', array_map('wrap_str', $products_ids));
          }

          $query = $request->query();

          if($payment_processor === 'paypal')
          {
              unset($query['token']);

              $order_details = (new Paypal)->order_details($request->token);

              $response = json_decode($order_details);

              if(property_exists($response, 'name'))
                return redirect()->route('home');

              $transaction->order_id          = $response->id;
              $transaction->transaction_id    = null;
              $transaction->reference_id      = $response->purchase_units[0]->reference_id;
              $transaction->payment_url       = collect($response->links)->where('rel', 'approve')->first()->href ?? null;
          }

          $transaction->save();

          return redirect()->route('home.checkout', $query);
      }

      $type = $request->query('type');

      $payment_processors = collect(config('payments'))->where('enabled', 'on');

      $payment_processor = $payment_processors->count() > 1 ? null : $payment_processors->first();

      if(strtolower($type) === 'subscription')
      { 
        $subscription_id    = $request->query('id') ?? abort(404);
        $subscription_name  = $request->query('slug') ?? abort(404);
        $subscription       = Pricing_Table::find($request->id) ?? abort(404);

        if(!config('app.subscriptions.accumulative'))
        {
          $active_subscription =  User_Subscription::useIndex('user_id', 'subscription_id')
                              ->select('user_subscription.id')
                              ->join(DB::raw('pricing_table USE INDEX(primary)'), 'pricing_table.id', '=', 'user_subscription.subscription_id')
                              ->join(DB::raw('transactions USE INDEX(products_ids, is_subscription)'), function($join)
                              {
                                $join->on('transactions.products_ids', '=', DB::raw('QUOTE(pricing_table.id)'))
                                     ->where('transactions.is_subscription', '=', 1);
                              })
                              ->where('user_subscription.user_id', Auth::id())
                              ->whereRaw("user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP < user_subscription.ends_at")
                              ->where(function($query)
                              {
                                $query->where('transactions.refunded', '0')
                                      ->orWhere('transactions.refunded', null);
                              })
                              ->first();

          if($active_subscription ?? false)
          {
            return redirect('/')->with(['user_message' => __("It's not possible to subscribe to another membership plan while your previous one has not expired yet.")]);
          }
        }

        $subscription->price = price($subscription->price, false, false, 0, null, null);
        $subscription->specifications = json_decode($subscription->specifications); 

        return view_('checkout.subscription', [
          'title'             => __(':app_name - Checkout', ['app_name' => config('app.name')]),
          'subscription'      => $subscription,
          'payment_processor' => $payment_processor['name'] ?? null
        ]);
      }
      else
      {
        config([
          "meta_data.title" => __(':app_name - Checkout', ['app_name' => config('app.name')]),
          "meta_data.description" => __('Checkout'),
        ]);

        return view_('checkout.shopping_cart', [
          'title'             => __(':app_name - Checkout', ['app_name' => config('app.name')]),
          'payment_processor' => $payment_processor['name'] ?? null
        ]);
      }
    }


    public function checkout_error(Request $request)
    {
      $message = session('message') ?? abort(404);

      config([
        "meta_data.title" => __('Payment failed'),
      ]);

      return view_('checkout.failure', compact('message',));
    }



    // List Product folder To Preview Its Files (POST)
    public function product_folder_async(Request $request)
    {
      $request->validate([
        'id' => 'required|numeric',
        'slug' => 'required|string'
      ]);

      config('filehosts.working_with') == 'folders' || abort(404);

      $item = Product::useIndex('primary')->select('file_name', 'file_host')
                                          ->where(['slug' => $request->slug, 'id' => $request->id])->first() ?? abort(404);

      if($item->file_host === 'google')
      {
        $files_list = GoogleDrive::list_folder($item->file_name)->original['files_list'] ?? [];
      }
      /*if($item->file_host === 'onedrive')
      {
        $files_list = OneDrive::list_folder($item->file_name)->original['files_list'] ?? [];
      }*/
      elseif($item->file_host === 'dropbox')
      {
        $files_list = DropBox::list_folder($item->file_name)->original['files_list'] ?? [];
      }
      elseif($item->file_host === 'local')
      {
        $zip        = new ZipArchive;
        $files_list = ['files' => []];
        $item_file  = get_main_file($item->file_name);

        if($zip->open($item_file) === TRUE)
        {
          for($i = 1; $i < $zip->numFiles; $i++ )
          { 
              $stat = $zip->statIndex($i); 

              $files_list['files'][] = ['name' => File::basename($stat['name']), 'mimeType' => File::extension($stat['name'])];
          }
        }
      }
      else
      {
        $files_list = [];
      }

      return response()->json($files_list);
    }



    // Newsletter
    public function subscribe_to_newsletter(Request $request)
    {
      $request->validate(['email' => 'required|email']);
      
      $subscription = Newsletter_Subscriber::insertIgnore(['email' => strip_tags($request->email)]);

      $request->session()->flash('newsletter_subscription_msg', ($subscription->id ?? null) 
                                                                ? __('Subscription done')
                                                                : __('You are already subscribed to our newsletter'));

      return redirect(($request->redirect ?? '/') . '#footer');
    }


    // Newsletter / unsubscribe
    public function unsubscribe_from_newsletter(Request $request)
    {
      if(Auth::check() && $request->query('email'))
      {
        $user = $request->user();

        if(md5($request->query('email')) && md5($user->email))
        {
          DB::delete("DELETE FROM newsletter_subscribers WHERE email = ?", [$user->email]);

          return redirect('/')->with(['user_message' => __('You have been unsubscribed from our newsletter. Thank you.')]);
        }
      }

      return redirect('/');
    }



    private function set_home_categories($limit = 20)
    {
      $categories    = config('categories.category_parents');
      $subcategories = config('categories.category_children');

      if($categories && $subcategories)
      {
        $_categories  = [];

        foreach($categories as $category)
        {
          if(!key_exists($category->id, array_keys($subcategories)))
            continue;

          foreach($subcategories[$category->id] as $subcategory)
          {
            $_categories[] = (object)['name' => $subcategory->name, 
                                      'url' => route('home.products.category', [$category->slug, $subcategory->slug])];
          }
        }

        shuffle($_categories);

        $_categories = array_slice($_categories, 0, $limit);

        Config::set('home_categories', $_categories);
      }
    }
    



    public function notifications_read(Request $request)
    {
      ctype_digit($request->notif_id) || abort(404);

      $user_id = Auth::id();

      return DB::update("UPDATE notifications SET users_ids = REPLACE(users_ids, CONCAT('|', ?, ':0|'), CONCAT('|', ?, ':1|')) 
                          WHERE users_ids REGEXP CONCAT('/|', ? ,':0|/') AND id = ?",
                          [$user_id, $user_id, $user_id, $request->notif_id]);
    }


    public function add_to_cart_async(Request $request)
    {
      $licenses         = collect(config('licenses', []));
      $regular_license  = collect($licenses)->where('regular', 1)->first();
      $license_id       = $request->input('item.license_id', $regular_license->id);
      $license_name     = collect($licenses)->where('id', $license_id)->first()->name;

      is_numeric($request->input('item.id')) || abort(404);

      $product = Product::where(['id' => $request->input('item.id'), 'for_subscriptions' => 0])->first() ?? abort(404);

      $response = $this->product_price($product, $license_id);

      $product_prices = $response['product_prices'];
      $config         = $response['config'];

      $product->fill(['license_id' => $license_id, 'license_name' => $license_name]);

      $props = ['id', 'name', 'slug', 'cover', 'created_at', 'price', 'url', 'price', 'license_id', 'license_name', 'custom_price'];

      if((is_numeric($product->stock) && $product->stock == 0) || ($product->has_keys() > 0 && $product->keys(remaining:0)->count() == 0))
      {
        abort(404);
      }

      if($product->minimum_price && $request->input('item.custom_price'))
      {
        $product->price        = ($request->input('item.custom_price') >= $product->minimum_price) ? $request->input('item.custom_price') : $product->minimum_price;
        $product->custom_price = $request->input('item.custom_price');
      }
      elseif($product_prices[$license_id]['is_free'] ?? null)
      {
        $product->price = 0;
      }
      elseif($product_prices[$license_id]['has_promo'] ?? null)
      {
        $product->price = $product_prices[$license_id]['promo_price'];
      }
      elseif($request->post('groupBuy') === '1' && productHasGroupBuy($product))
      {
        $props = array_merge($props, ['group_buy_price', 'group_buy_min_buyers', 'group_buy_expiry']);

        $product->price        = $product->group_buy_price;
        $product->custom_price = $product->group_buy_price;
      }
      else
      {
        $product->price = $product_prices[$license_id]['price'];
      }

      $product->cover = asset("storage/covers/{$product->cover}");
      $product->url   = item_url($product);
      $product->price = format_amount($product->price, false, config("payments.decimals.".currency('cody'), 2));

      User_Shopping_Cart_Item::insertOrIgnore([
        'user_ip' => $request->ip(),
        'item_id' => $product->id,
        'user_id' => Auth::id(),
      ]);

      return response()->json(['product' => $product->only($props)]);
    }



    public function remove_from_cart_async(Request $request)
    {
      !Validator::make($request->post(), ['id' => 'required|numeric'])->fails() ?? abort(404);

      User_Shopping_Cart_Item::where(['user_ip' => $request->ip(), 'item_id' => $request->post('id')])->delete();
    }



    public function update_price(Request $request)
    {
      $request->validate(['items' => 'array|required']);

      $items = array_filter($request->post('items'));

      foreach($items as &$item)
      {
        $request->merge([
          'item' => [
            'id' => $item['id'],
            'license_id' => $item['license_id'] ?? null,
            'custom_price' => $item['custom_price'] ?? null
          ]
        ]);

        if(productHasGroupBuy((object)$item))
        {
          continue;
        }

        $item = $this->add_to_cart_async($request)->getData(true)['product'];
      }

      return response()->json(['items' => json_decode(json_encode($items), true)]);
    }


    public function get_group_buy_buyers(Request $request)
    {
      is_numeric($request->query('product_id')) ?? abort(404);

      $buyers_count = User_Shopping_Cart_Item::where(['user_ip' => $request->ip(), 'item_id' => $request->query('product_id')])->count();

      return json(['buyers' => $buyers_count]);
    }
                                 

    public static function init_notifications()
    {
      $notifications = [];

      if($user_id = Auth::id())
      {
        $notifications = DB::select("SELECT products.id as product_id, products.name, products.slug, notifications.updated_at, 
                                      notifications.id, `for`,
                                        CASE
                                          WHEN `for` = 0 THEN IFNULL(products.cover, 'default.webp')
                                          WHEN `for` = 1 OR `for` = 2 THEN IFNULL(users.avatar, 'default.webp')
                                        END AS `image`,
                                        CASE notifications.`for`
                                          WHEN 0 THEN 'New release is available for :product_name'
                                          WHEN 1 THEN 'Your comment has been approved for :product_name'
                                          WHEN 2 THEN 'Your review has been approved for :product_name'
                                        END AS `text`
                                       FROM notifications USE INDEX (users_ids, updated_at)
                                       JOIN products ON products.id = notifications.product_id
                                       JOIN users ON users.id = ?
                                       WHERE users_ids REGEXP CONCAT('/|', ? ,':0|/')
                                       ORDER BY updated_at DESC
                                       LIMIT 5", [$user_id, $user_id]);

        config(['notifications' => $notifications]);
      }

      return $notifications;
    }



    public function save_reaction(Request $request)
    {
      $this->middleware('auth');

      $request->validate([
        'product_id' => 'required|numeric',
        'item_id' => 'required|numeric',
        'item_type' => 'required|string',
        'reaction' => 'required|string|max:255'
      ]);

      $res = DB::insert("INSERT INTO reactions (product_id, item_id, item_type, user_id, name) VALUES (?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE name = ?", 
                  [$request->product_id, $request->item_id, $request->item_type, \Auth::id(), $request->reaction, $request->reaction]);

      $reactions = $this->get_reactions($request);

      return response()->json(['status' => $res, 'reactions' => $reactions]);
    }


    // Get item data for user favorites
    public function get_item_data(Request $request)
    {
      $request->validate(['id' => 'numeric|required|gt:0']);

      $id = $request->query('id');

      $item = Product::selectRaw(implode(',', Self::$product_columns))
              ->leftJoin('categories', 'categories.id', '=', 'products.category')
              ->leftJoin('transactions', 'transactions.products_ids', 'LIKE', \DB::raw("CONCAT(\"%'\", products.id,\"'%\")"))
              ->leftJoin('categories as subcategories', function($join)
              {
                  $join->on('products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                     ->where('subcategories.parent', '=', null);
              })
              ->leftJoin('reviews', 'reviews.product_id', '=', 'products.id')
              ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
              ->leftJoin('product_price', function($join)
              {
                $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
              })
              ->where(['products.id' => $id, 'products.active' => 1])
              ->groupBy('products.id', 'products.name', 'products.views', 'products.preview', 'products.preview_type', 'categories.id',
               'products.slug', 'products.updated_at', 'products.active', 'products.bpm', 'products.label',
               'products.cover', 'product_price.price', 'products.hidden_content', 'products.last_update', 'promotional_price_time', 'products.stock', 'products.pages', 'products.authors', 'products.language',
               'products.words', 'products.formats', 
               'categories.name', 'categories.slug', 'reviews.rating', 'products.short_description', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.country_city')
              ->first();

      $item = $item->only(['id', 'name', 'slug', 'category_id', 'category_slug', 'category_name', 'cover', 'updated_at']);

      return json(compact('item')); 
    }


    // App installation
    public function install(Request $request)
    {      
      if(config('app.installed'))
      {
        return redirect('/');
      }

      if($request->method() === 'POST')
      {
        $request->validate([
          'database.*'          => 'required|bail|string',
          'site.name'           => 'required|bail|string',
          'site.title'          => 'required|bail|string',
          'site.items_per_page' => 'numeric|bail|string|gt:0',
          'site.purchase_code'  => 'required|bail|string',
          'admin.username'      => 'required',
          'admin.email'         => 'required|bail|email',
          'admin.password'      => 'required|bail|max:255',
          'admin.avatar'        => 'nullable|bail|image',
        ]);

        /** CREATE DATABASE CONNECTION START **/
          $db_params = $request->input('database');

          Config::set("database.connections.mysql", array_merge(config('database.connections.mysql'), $db_params));

          try 
          {
            DB::connection()->getPdo();
          }
          catch (\Exception $e)
          {
            $validator = Validator::make($request->all(), [])
                         ->errors()->add('Database', $e->getMessage());

            return redirect()->back()->withErrors($validator)->withInput();
          }
        /** CREATE DATABASE CONNECTION END **/


        /** CREATE DATABASE TABLES START **/
        DB::unprepared(File::get(base_path('database/db_tables.sql')));
        /** CREATE DATABASE TABLES END **/



        /** SETTING .ENV VARS START **/
        update_env_var("DB_HOST", wrap_str($db_params['host']));
        update_env_var("APP_ENV", "production");
        update_env_var("DB_DATABASE", wrap_str($db_params['database']));
        update_env_var("DB_USERNAME", wrap_str($db_params['username']));
        update_env_var("DB_PASSWORD", wrap_str($db_params['password']));
        update_env_var("APP_NAME", wrap_str($request->input('site.name')));
        update_env_var("APP_URL", wrap_str("{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}"));
        update_env_var("APP_INSTALLED", 'true');
        update_env_var("PURCHASE_CODE", wrap_str($request->input('site.purchase_code')));
        update_env_var("SESSION_DOMAIN", str_replace('.www', '', $_SERVER['HTTP_HOST']));
        update_env_var("SESSION_DRIVER", wrap_str('database'));
        update_env_var("CACHE_DRIVER", wrap_str('database'));
        /** SETTING .ENV VARS END **/


        /** CREATE ADMIN USER START **/
          if(!$user = User::where('email', $request->input('admin.email'))->first())
          {
            $user = new User;

            $user->name = $request->input('admin.username');
            $user->email = $request->input('admin.email');
            $user->password = Hash::make($request->input('admin.password'));
            $user->email_verified_at = date('Y-m-d');
            $user->role = 'admin';
            $user->avatar = 'default.webp';

            // Avatar
            if($avatar_file = $request->file('admin.avatar'))
            {
              $user_auto_inc_id = DB::select("SHOW TABLE STATUS LIKE 'users'")[0]->Auto_increment;

              $ext    = $avatar_file->extension();
              $avatar = $avatar_file->storeAs('avatars', "{$user_auto_inc_id}.{$ext}", ['disk' => 'public']);

              $user->avatar = pathinfo($avatar, PATHINFO_BASENAME);
            }

            $user->save();
          }
        /** CREATE ADMIN USER END **/


        $settings = Setting::first();

        /** GENERAL SETTINGS START **/
        //----------------------------
          $general_settings = json_decode($settings->general);

          $general_settings->name           = $request->input('site.name');
          $general_settings->title          = $request->input('site.title');
          $general_settings->description    = $request->input('site.description');
          $general_settings->items_per_page = $request->input('site.items_per_page');
          $general_settings->timezone       = $request->input('site.timezone');
          $general_settings->purchase_code  = $request->input('site.purchase_code');
          $general_settings->email          = $request->input('admin.email');
          
          $settings->general = json_encode($general_settings);
        /** GENERAL SETTINGS END **/


        /** MAILER SETTINGS START **/
        //----------------------------
          $mailer_settings = json_decode($settings->mailer);

          $mailer_settings->mail = json_encode($request->input('mailer.mail'));

          $mailer_settings = json_encode($mailer_settings);
        /** MAILER SETTINGS END **/


        $settings->save();

        Auth::loginUsingId($user->id, true);

        return redirect()->route('admin');
      }

      \Artisan::call('key:generate');

      $mysql_user_version = ['distrib' => __('Unable to find MySQL version'), 'version' => null, 'compatible' => false];

      if(function_exists('exec') || function_exists('shell_exec'))
      {
        $mysqldump_v = function_exists('exec') ? exec('mysqldump.exe --version') : shell_exec('mysqldump --version');
        
        preg_match('/(?<mysqlVersion>\d+\.\d+\.\d+(-\w+)?)/i', $mysqldump_v, $matches);

        if($mysqld = ($matches['mysqlVersion'] ?? null))
        {
          $mysql_user_version['distrib'] = (stripos($mysqld, 'mariadb') !== false) ? 'mariadb' : 'mysql';
          $mysql_user_version['version'] = (stripos($mysqld, 'mariadb') !== false) ? explode('-', $mysqld, 2)[0] : $mysqld;

          if($mysql_user_version['distrib'] == 'mysql' && $mysql_user_version['version'] >= 5.7)
          {
            $mysql_user_version['compatible'] = true;
          }
          elseif($mysql_user_version['distrib'] == 'mariadb' && $mysql_user_version['version'] >= 10)
          {
            $mysql_user_version['compatible'] = true;
          }
        }
      }

      $requirements = [
        "php" => ["version" => 8.0, "current" => phpversion()],
        "mysql" => ["version" => 8.0, "current" => $mysql_user_version],
        "php_extensions" => [
          "curl" => false,
          "fileinfo" => false,
          "intl" => false,
          "json" => false,
          "mbstring" => false,
          "openssl" => false,
          "mysqli" => false,
          "zip" => false,
          "ctype" => false,
          "dom" => false,
          "calendar" => false,
          "xml" => false,
          "xsl" => false,
          "pcre" => false,
          "tokenizer" => false
        ],
      ];

      $php_loaded_extensions = get_loaded_extensions();

      foreach($requirements['php_extensions'] as $name => &$enabled)
      {
          $enabled = in_array($name, $php_loaded_extensions);
      }

      return view('install', compact('requirements'));
    }



    public function get_reactions(Request $request)
    {
      if($request->users)
      {
        $reactions = Reaction::selectRaw("reactions.name, users.name as user_name, IFNULL(users.avatar, 'default.webp') as user_avatar")
                          ->join(DB::raw('users USE INDEX(primary)'), 'users.id', '=', 'reactions.user_id')
                          ->where(['reactions.item_id'    => $request->item_id, 
                                   'reactions.product_id' => $request->product_id,
                                   'reactions.item_type'  => $request->item_type]);

        //$reactions = $request->reaction ? $reactions->where('reactions.name', $request->reaction) : $reactions;

        $reactions = $reactions->get();

        return response()->json(['reactions' => $reactions->groupBy('name')->toArray()]);
      }
      else
      {
        return Reaction::selectRaw("COUNT(reactions.item_id) as `count`, reactions.name")
                          ->join(DB::raw('users USE INDEX(primary)'), 'users.id', '=', 'reactions.user_id')
                          ->where(['reactions.item_id'    => $request->item_id, 
                                   'reactions.product_id' => $request->product_id,
                                   'reactions.item_type'  => $request->item_type])
                          ->groupBy('reactions.name')
                          ->get()->pluck('count', 'name')->toArray();
      }
    }


    public function get_cities(Request $request)
    {
      config('app.products_by_country_city') || abort(404);

      $country = $request->post('country') ?? abort(404);
      $cities = config("app.countries_cities.{$country}") ?? abort(404);

      return response()->json(compact('cities'));
    }



    public function delete_comment(Request $request)
    {
      $comment = Comment::where(['id' => $request->id, 'user_id' => Auth::id()])->first() ?? abort(404);
  
      if(!$comment->parent)
      {
        $subcomments = Comment::where(['parent' => $comment->id])->get();
        
        if($subcomments->count())
        {
          $ids = array_column($subcomments->toArray(), 'id');

          Reaction::where('item_type', 'comment')->whereIn('item_id', $ids)->delete();
        
          foreach($subcomments as $subcomment)
          {
            $subcomment->delete();
          }
        }        
      }

      $comment->delete();

      return redirect($request->redirect ?? '/');
    }



    public function delete_review(Request $request)
    {
      $review = Review::where(['id' => $request->id, 'user_id' => Auth::id()])->first() ?? abort(404);

      $review->delete();

      return redirect($request->redirect ?? '/'); 
    }



    public function generate_sitemap(Request $request)
    {
      $sitemap = "";
      $type    = mb_strtolower(str_ireplace(['_', '.xml'], '', $request->type));

      $sitemap = sitemap($type);

      header('Content-Type: application/xml');

      exit($sitemap);
    }



    public function realtime_views(Request $request)
    {
      $user_id = md5($request->server("HTTP_USER_AGENT").'-'.$request->server("REMOTE_ADDR"));

      $realtime_views = [
        "website" => 0,
        "product" => 0
      ];

      if(config('app.realtime_views.website.enabled'))
      {
        if(!config('app.realtime_views.website.fake'))
        {
          $website_visitor_ids = \Cache::get('website_visitor_ids', []);

          $website_visitor_ids[$user_id] = time() + config('app.realtime_views.refresh', 5);

          foreach($website_visitor_ids as $visitor_id => $expire)
          {
            if(time() >= $expire)
            {
              unset($website_visitor_ids[$visitor_id]);   
            }
          }

          \Cache::forever('website_visitor_ids', $website_visitor_ids);

          $realtime_views['website'] = count($website_visitor_ids);
        }
        else
        {
          $fake_views_range = explode(',', config('app.realtime_views.website.range', '15,30'));

          $realtime_views['website'] = rand(...$fake_views_range);
        }
      }

      if(config('app.realtime_views.product.enabled') && is_numeric($request->query('i')))
      {
        if(!config('app.realtime_views.product.fake'))
        {
          $products_visitor_ids = \Cache::get('products_visitor_ids', []);
          $current_product_id   = $request->query('i');
          $product_visitor_ids  = $products_visitor_ids[$current_product_id] ?? [];

          $product_visitor_ids[$user_id] = time() + config('app.realtime_views.refresh', 5);

          foreach($product_visitor_ids as $visitor_id => $expire)
          {
            if(time() >= $expire)
            {
              unset($product_visitor_ids[$visitor_id]);   
            }
          }

          $products_visitor_ids[$current_product_id] = $product_visitor_ids;

          \Cache::forever('products_visitor_ids', $products_visitor_ids);

          $realtime_views['product'] = count($product_visitor_ids);
        }
        else
        {
          $fake_views_range = explode(',', config('app.realtime_views.product.range', '15,30'));

          $realtime_views['product'] = rand(...$fake_views_range);
        }
      }

      header("Cache-Control: no-cache, no-store, must-revalidate");
      header("Content-Type: application/javascript");

      if($realtime_views['product'] > $realtime_views['website'])
      {
        $realtime_views['website'] = $realtime_views['product'] + rand(10, 20);
      }

      $realtime_views = json_encode($realtime_views, JSON_PRETTY_PRINT);

      exit(<<<SCRIPT
      app.realtimeViews = $realtime_views;
      SCRIPT); 
    }



    public function bricks_mask(Request $request)
    {
        return cover_mask($request->query('name'));
    }


    public function proceed_payment_link(Request $request)
    {      
      $token = $request->token ?? abort(404);

      $short_link = route('home.proceed_payment_link', ['token' => $token]);

      $payment_link = Payment_Link::useIndex('short_link')
                      ->where('short_link', $short_link)
                      ->first() ?? abort(403, __('Payment link expired.'));

      list($user_email, $payment_identifer) = explode('|', decrypt(base64_decode($payment_link->token), false));

      if(!Auth::check())
      {
        return redirect()->route('login', ['redirect' => url()->current()])->with(['email' => $user_email]);
      }
      elseif(strtolower($request->user()->email) != strtolower($user_email))
      {
        return redirect('/')->with(['user_message' => __('You must be logged in with this email address : :email_address', ['email_address' => $user_email])]);
      }

      $user = User::where('email', $user_email)->where('blocked', 0)->first() ?? abort(404);

      Auth::login($user, true);

      $payment_data = json_decode($payment_link->content, true);

      Session::put('short_link', $short_link);

      return redirect()->away($payment_data['payment_link']);
    }


    public function stream_video(Request $request)
    {
      $product = Product::find($request->id) ?? abort(404);

      $supported_formats = [
        //"webv" => "video/webm",
        "mp4" => "video/mp4"
      ];

      if(isset($supported_formats[$product->file_extension]))
      {
        if($product->file_host === "local")
        {
          $file = storage_path("app/downloads/{$product->file_name}");

          if(file_exists($file))
          {
            $vStream = new \App\Libraries\VideoStream($file);

            exit($vStream->start());
          }
        }
        elseif($product->file_host === "yandex")
        {
          try
          {
            $vStream = new \App\Libraries\VideoStreamUrl(urldecode(base64_decode($request->temp_url)));

            exit($vStream->start());
          }
          catch(\Exception $e)
          {

          }
        }
      }
    }


    public function set_template(Request $request)
    {
      $url  = urldecode($request->query('redirect', ''));

      if(auth_is_admin() || env_is('local'))
      {
        $template = $request->query('template');

        $templates = \File::glob(resource_path('views/front/*', GLOB_ONLYDIR));
        $base_path = resource_path('views/front/');
        $templates = array_filter($templates, 'is_dir');
        $templates = str_ireplace($base_path, '', $templates);

        if(in_array($template, $templates))
        {
            session(["template" => $template]);
        }
      }

      return redirect($url);
    }


    public function set_currency(Request $request)
    {
      if(config('app.installed') === true)
      {
        $url  = urldecode($request->query('redirect', ''));
        $code = $request->query('code');

        if(in_array(mb_strtoupper($code), array_keys(config('payments.currencies', []))))
        {
            session(["currency" => $code]);
        }
      }
      
      return redirect($url);
    }


    public function set_locale(Request $request)
    {
      $url  = $request->post('redirect', '');
      $locale = $request->post('locale', config('app.locale'));

      if(in_array($locale, \LaravelLocalization::getSupportedLanguagesKeys()))
      {
        session(["locale" => $locale]);
      }

      return redirect($url);
    }


    public function update_statistics(Request $request)
    {
      try
      {
        if(!$stats = \App\Models\Statistic::where("date", \DB::raw("CURDATE()"))->first())
        {
          $stats = new \App\Models\Statistic;
        }

        // Traffic
        if($code = user_country($request->ip(), "isoCode"))
        {
          $stats->traffic = implode(",", array_filter(array_merge(explode(',', $stats->traffic), [$code])));
        }

        if(!BrowserDetect::isBot())
        {
          // Browser name
          if($browser = BrowserDetect::browserFamily())
          {
            $browsers = json_decode($stats->browsers, true) ?? [];

            if(isset($browsers[$browser]))
            {
              $browsers[$browser] += 1;  
            }
            else
            {
              $browsers[$browser] = 1;
            }

            $stats->browsers = json_encode($browsers);
          }

          // Operating systems
          if($os = BrowserDetect::platformFamily())
          {
            $oss = json_decode($stats->oss, true) ?? [];

            if(isset($oss[$os]))
            {
              $oss[$os] += 1;  
            }
            else
            {
              $oss[$os] = 1;
            }

            $stats->oss = json_encode($oss);
          }

          // Devices (mobile, tablet, desktop)
          $devices = json_decode($stats->devices, true) ?? [];
          $devices = array_merge(['mobile' => 0, 'tablet' => 0, 'desktop' => 0], $devices);

          if(BrowserDetect::isMobile())
          {
            $devices['mobile'] += 1;  
          }
          elseif(BrowserDetect::isTablet())
          {
            $devices['tablet'] += 1;
          }
          elseif(BrowserDetect::isDesktop())
          {
            $devices['desktop'] += 1;
          }

          $stats->devices = json_encode($devices);
        }

        $stats->date = \DB::raw("CURDATE()");

        $stats->save();
      }
      catch(\Throwable $e)
      {
        
      }
    }


    public function load_translations()
    {
      $seconds_to_cache = 3600;
      $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
      header("Expires: $ts");
      header("Pragma: cache");
      header("Cache-Control: max-age=$seconds_to_cache");
      header("Content-Type: text/javascript");

      exit("window.translation = ".json_encode(config('translation')));
    }


    public function load_js_props(Request $request)
    {
      $js_code = (string)view('components.js_props', ['payment_processor' => $request->query('processor')]);

      $seconds_to_cache = 3600;
      $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
      header("Expires: $ts");
      header("Pragma: cache");
      header("Cache-Control: max-age=$seconds_to_cache");
      header("Content-Type: application/javascript");

      exit($js_code);
    }


    public function resize_image($size, $name, $ext)
    {
        if(!file_exists(public_path("storage/covers/{$name}.{$ext}")))
        {
          return;
        }

        if(strtolower($ext) !== "svg")
        {
          $expiry = 2592000; // 30 days
          $manager = new ImageManager(['driver' => 'imagick']);

          $image =  $manager->cache(function($image) use ($name, $size, $ext) 
                    {
                        $image->make(public_path("storage/covers/{$name}.{$ext}"))
                        ->resize($size, $size);
                        /*->resize($size, null, function ($constraint) 
                        {
                            $constraint->aspectRatio();
                        });*/
                    }, $expiry);

          $seconds_to_cache = $expiry;
          $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";

          return \Response::make($image, 200, [
            "Content-Type" => "image/{$ext}", 
            "Pragma" => "cache", 
            "Cache-Control" => "max-age={$seconds_to_cache}, public",
            "Expires" => $ts
          ]);
        }
    }



    public function user_coupons(Request $request)
    {
      $coupons =  Coupon::where(function($query)
                  {
                    $query->where("users_ids", "=", null)
                          ->orWhere("users_ids", "=", "")
                          ->orWhereRaw("users_ids REGEXP QUOTE(?)", [Auth::id()]);
                  })
                  ->orderBy('id', 'desc')
                  ->paginate(10);

      return view_("user", compact('coupons'));
    }



    // User Purchases
    public function user_purchases(Request $request)
    {
      $transactions = Transaction::where(['user_id' => Auth::id(), 'type' => 'product']);

      if(!config('app.allow_download_in_test_mode'))
      {
        $transactions = $transactions->where('sandbox', 0);
      }
      
      $transactions = $transactions->paginate(10);

      $transactions_collection = $transactions->getCollection();

      foreach($transactions_collection as &$transaction)
      {
        $checkout_controller = new \App\Http\Controllers\CheckoutController();

        $transaction->setAttribute('items', $checkout_controller->order_download_links($transaction, 0));
      }

      $transactions->setCollection($transactions_collection);

      return view_("user", compact('transactions'));
    }




    public function user_credits(Request $request)
    { 
      $orders = Transaction::selectRaw("transactions.*, CONCAT_WS(' ', users.name, users.email) as referee_name, affiliate_earnings.commission_value as earnings")
                ->where('transactions.referrer_id', Auth::id())
                ->leftJoin('users', 'users.id', '=', 'transactions.user_id')
                ->leftJoin('affiliate_earnings', function($join)
                {
                  $join->on('affiliate_earnings.transaction_id', 'transactions.id')
                      ->where([
                        'transactions.status' => 'paid', 
                        'transactions.confirmed' => 1, 
                        'transactions.refunded' => 0,
                      ]);
                })
                ->paginate(10);
      
      $orders_collection = $orders->getCollection();
      
      foreach($orders_collection as $order)
      {
          $items = json_decode($order->details, 1)['items'] ?? [];
         
          $order->setAttribute('items', implode(',', array_column($items, 'name')));
      }
      
      $orders->setCollection($orders_collection);

      $user_orders = Transaction::where('user_id', Auth::id())->get();

      $purchased_items = $user_orders->where('type', '!=', 'credits')->pluck('products_ids')->toArray();
      $purchased_items = count(explode(',', implode(',', $purchased_items)));

      $referred_users = Transaction::where('referrer_id', Auth::id())->count();

      $completed_orders = Transaction::where(['confirmed' => 1, 'refunded' => 0, 'status' => 'paid', 'referrer_id' => Auth::id()])->count();

      $cashed_out_earnings = Cashout::selectRaw("cashouts.amount")->where("cashouts.user_id", Auth::id())->sum('amount');

      $spent_credits =  Transaction::select('amount')
                        ->where([
                          "transactions.user_id" => Auth::id(), 
                          "transactions.processor" => "credits",
                          "transactions.confirmed" => 1,
                          "transactions.status" => "paid",
                          "transactions.refunded" => 0
                        ])
                        ->sum("amount");

      $affiliate_credits = Affiliate_Earning::selectRaw("affiliate_earnings.id, affiliate_earnings.commission_value as credits,
                            affiliate_earnings.updated_at, affiliate_earnings.transaction_id, referrers.name as referrer_name, 
                            referees.name as referee_name, transactions.status, transactions.confirmed, transactions.refunded, 
                            affiliate_earnings.paid, transactions.products_ids, transactions.type, transactions.amount, 
                            transactions.custom_amount, transactions.sandbox, transactions.custom_amount, transactions.details")
                            ->join('transactions', 'transactions.id', '=', 'affiliate_earnings.transaction_id')
                            ->join('users as referrers', 'referrers.id', '=', 'affiliate_earnings.referrer_id')
                            ->join('users as referees', 'referees.id', '=', 'affiliate_earnings.referee_id')
                            ->where(['affiliate_earnings.referrer_id' => Auth::id()])
                            ->orderBy('updated_at', 'desc')
                            ->get();
      
      extract(user_credits(true));

      $affiliate_credits = $affiliate_credits->sum('credits');
      $prepaid_credits   = $prepaid_credits->sum('credits');

      $data = compact('affiliate_credits', 'prepaid_credits', 'orders', 'purchased_items', 'completed_orders', 'referred_users',
                      'cashed_out_earnings', 'spent_credits');

      $data = array_merge($data, $this->user_affiliate_earnings($request));

      return view_('user', $data);
    }


    public function user_affiliate_earnings(Request $request)
    {      
      $daysInMonth = now()->daysInMonth;

      if($request->date && is_string($request->date))
      {
        $daysInMonth = Carbon::parse($request->date)->daysInMonth;
        $date = [(string)Carbon::parse($request->date)->firstOfMonth()->format('Y-m-d'), (string)Carbon::parse($request->date)->lastOfMonth()->format('Y-m-d')];
      }
      else
      {
        $date = [(string)now()->firstOfMonth()->format('Y-m-d'), (string)now()->lastOfMonth()->format('Y-m-d')];
      }

      $earnings_per_day = array_fill(1, date('t'), 0);

      $_earnings = Affiliate_Earning::selectRaw("DATE_FORMAT(affiliate_earnings.updated_at, '%e') as `day`, 
                    affiliate_earnings.amount as count")
                    ->join('transactions', 'transactions.id', '=', 'affiliate_earnings.transaction_id')
                    ->where([
                      'transactions.status' => 'paid', 
                      'transactions.confirmed' => 1, 
                      'transactions.refunded' => 0, 
                      'affiliate_earnings.referrer_id' => Auth::id()
                    ])
                    ->whereBetween('affiliate_earnings.updated_at', $date)
                    ->get();

      foreach($_earnings as $earning)
      {
        $earnings_per_day[$earning->day] += $earning->count;
      }

      $current_month = trim(explode('-', $date[0], 3)[1] ?? null, 0);

      $max_value = max($earnings_per_day);
      $max_value = $max_value > 0 ? $max_value : 10;

      $earnings_steps = [];

      for($i = $max_value; $i >= 0; $i -= $max_value/10)
      {
        $earnings_steps[] = $i;
      }

      $earnings_steps[] = 0;
      $earnings_steps = array_unique($earnings_steps);
      $earnings_per_day = array_values($earnings_per_day);
    
      if($request->get('refresh'))
      {
        $data = '<div class="wrapper">';

                  foreach($earnings_steps as $step):
                   $data .= '<div class="row"><div>'.ceil($step).'</div>';

                    for($k = 0; $k <= (count($earnings_per_day) - 1); $k++):
                    $data .= '<div>';

                      if($step == 0):
                      $data .= '<span data-tooltip="'. __(':count sales', ['count' => $earnings_per_day[$k] ?? '0']) .'" 
                      style="height:'. ($earnings_per_day[$k] > 0 ? ($earnings_per_day[$k] / $max_value * 305) : "0") .'px"><i class="circle blue icon mx-0"></i></span>';
                      endif;

                    $data .= '</div>';

                    endfor;
                  $data .= '</div>';

                 endforeach;

                $data .= '<div class="row"><div>-</div>';

                for($day = 1; $day <= count($earnings_per_day); $day++):
                $data .= '<div>'. $day .'</div>';
                endfor;

        $data .= '</div></div>';

        return json(['html' => $data]);
      }
      else
      {
        $data = [
          'earnings_steps' => $earnings_steps, 
          'earnings_per_day' => $earnings_per_day, 
          'max_value' => $max_value, 
          'current_month' => $current_month
        ];

        return $data;
      }
    }


    public function user_prepaid_credits(Request $request)
    {
      config([
        'meta_data.title' => __('My prepaid credits')
      ]);

      $expires_in_days = config('app.prepaid_credits.expires_in', null);

      $user_prepaid_credits = User_Prepaid_Credit::useIndex('user_id')
                              ->selectRaw('prepaid_credits.name, prepaid_credits.discount, prepaid_credits.amount, user_prepaid_credits.credits, 
                              transactions.status, IF(? IS NOT NULL, NOW() >= DATE_ADD(user_prepaid_credits.updated_at, INTERVAL ? DAY), 0) as expired,
                              IF(? IS NOT NULL, DATE_ADD(user_prepaid_credits.updated_at, INTERVAL ? DAY), null) as expires_at,
                              user_prepaid_credits.updated_at', [$expires_in_days, $expires_in_days, $expires_in_days, $expires_in_days])
                              ->join('transactions', 'transactions.id', '=', 'user_prepaid_credits.transaction_id')
                              ->join('prepaid_credits', 'user_prepaid_credits.prepaid_credits_id', '=', 'prepaid_credits.id')
                              ->where('user_prepaid_credits.user_id', auth()->user()->id)
                              ->orderBy('user_prepaid_credits.id', 'DESC')
                              ->paginate(15);

      return view_('user', compact('user_prepaid_credits'));
    }


    // Profile
    public function user_profile(Request $request)
    {
      $user = User::find($request->user()->id);
      
      if($request->method() === 'POST')
      {
        $cashout_methods = implode(',', array_values(config('affiliate.cashout_methods', [])));

        $request->validate([
          'name' => 'string|nullable|max:255|bail',
          'firstname' => 'string|nullable|max:255|bail',
          'lastname' => 'string|nullable|max:255|bail',
          'country' => 'string|nullable|max:255|bail',
          'city' => 'string|nullable|max:255|bail',
          'address' => 'string|nullable|max:255|bail',
          'zip_code' => 'string|nullable|max:255|bail',
          'id_number' => 'string|nullable|max:255|bail',
          'state' => 'string|nullable|max:255|bail',
          'affiliate_name' => 'string|nullable|max:255|bail',
          'cashout_method' => "string|nullable|in:{$cashout_methods}|max:255|bail",
          'paypal_account' => 'string|nullable|email|max:255|bail',
          'bank_account' => 'array|nullable|bail',
          'bank_account.*' => 'nullable|string|bail',
          'phone' => 'string|nullable|max:255|bail',
          'receive_notifs' => 'string|nullable|in:0,1|bail',
          'old_password' => 'string|nullable|max:255|bail',
          'new_password' => 'string|nullable|max:255|bail',
          'avatar' => 'nullable|image',
          'credits_sources' => 'nullable|string',
          'two_factor_auth' => 'nullable|numeric|in:0,1',
        ]);

        if($affiliate_name = $request->post('affiliate_name'))
        {
          if(User::where('affiliate_name', $affiliate_name)->where('id', '!=', $user->id)->first())
          {
            return back_with_errors(['user_message' => __('The affiliate name is already token, please chose another one.')]);
          }
        }

        $user->name       = $request->input('name', $user->name ?? null);
        $user->firstname  = $request->input('firstname', $user->firstname ?? null);
        $user->lastname   = $request->input('lastname', $user->lastname ?? null);
        $user->country    = $request->input('country', $user->country ?? null);
        $user->city       = $request->input('city', $user->city ?? null);
        $user->address    = $request->input('address', $user->address ?? null);
        $user->zip_code   = $request->input('zip_code', $user->zip_code ?? null);
        $user->id_number  = $request->input('id_number', $user->id_number ?? null);
        $user->state      = $request->input('state', $user->state ?? null);
        $user->affiliate_name = $request->input('affiliate_name');
        $user->paypal_account = $request->input('paypal_account');
        $user->bank_account   = json_encode($request->input('bank_account'));
        $user->phone      = $request->input('phone', $user->phone ?? null);
        $user->receive_notifs = $request->input('receive_notifs', $user->receive_notifs ?? '1');
        $user->cashout_method = $request->input('cashout_method');
        $user->credits_sources = $request->input('credits_sources');
        $user->two_factor_auth = $request->post('two_factor_auth') ?? '0';

        if($request->old_password && $request->new_password)
        {
          Validator::make($request->all(), [
            'old_password' => [
              function ($attribute, $value, $fail) 
              {
                  if(! Hash::check($value, auth()->user()->password)) {
                      $fail($attribute.' is incorrect.');
                  }
              }
            ],
          ])->validate();
        
          $user->password = Hash::make($request->new_password);
        }


        if($avatar = $request->file('avatar'))
        {
          $request->validate(['avatar' => 'image']);

          if(File::exists(public_path("storage/avatars/{$user->avatar}")))
          {
            File::delete(public_path("storage/avatars/{$user->avatar}"));
          }

          $ext  = "webp";

          Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
          
          $img = Image::make($avatar);

          $img =  $img->resize(128, null, function($constraint) 
                  {
                    $constraint->aspectRatio();
                  });

          $img->encode('webp', 100)->save("storage/avatars/{$user->id}.{$ext}");

          $user->avatar = "{$user->id}.{$ext}";
        }

        $user->save();

        $request->session()->flash('profile_updated', __('Done').'!');

        return redirect()->route('home.profile');
      }

      $user = (object)$user->getAttributes();

      $user->fullname = null;

      if($user->firstname && $user->lastname)
      {
        $user->fullname = $user->firstname . ' ' . $user->lastname;
      }

      $user->bank_account = json_decode($user->bank_account);

      $credits_sources = [];

      if(config('affiliate.enabled'))
      {
        $credits_sources['affiliate_credits'] = __('Affiliate earnings');
      }

      if(config('app.prepaid_credits.enabled'))
      {
        $credits_sources['prepaid_credits'] = __('Prepaid credits');
      }
      
      return view_('user', compact('user', 'credits_sources'));
    }


    // User invoices
    public function user_invoices(Request $request)
    {
      $invoices = Transaction::useIndex('user_id', 'confirmed')
                  ->select('id', 'reference_id', 'amount', 'created_at', 'details')
                  ->where(['user_id' => Auth::id(), 'confirmed' => 1])
                  ->where('details', '!=', null)
                  ->orderBy('id', 'desc')
                  ->paginate(10);

      foreach($invoices as &$invoice)
      {
        $details = json_decode($invoice->details);
        $currency = $details->currency ?? currency('code');
        $invoice->setAttribute('currency', $currency);
        $invoice->amount = $details->total_amount ?? $invoice->amount;
      }

      return view_("user", compact('invoices'));
    }


    // Favorites
    public function user_favorites(Request $request)
    {
        config([
          "meta_data.title" => __(':app_name - My Collection', ['app_name' => config('app.name')]),
          "meta_data.description" => __(':app_name - My Collection', ['app_name' => config('app.name')]),
        ]);

        return view_('user');
    }


    // User subscriptions
    public function user_subscriptions(Request $request)
    {
      config([
        'meta_data.title' => __('My pricing_table')
      ]);

      $user_subscriptions = User_Subscription::useIndex('user_id')
                            ->selectRaw("pricing_table.name, user_subscription.id, user_subscription.downloads, pricing_table.limit_downloads, user_subscription.starts_at, user_subscription.ends_at,
                              user_subscription.daily_downloads, pricing_table.limit_downloads_per_day,
                              IF(DATEDIFF(user_subscription.ends_at, CURRENT_TIMESTAMP) > 0, DATEDIFF(user_subscription.ends_at, CURRENT_TIMESTAMP), 0) as remaining_days,
                              ((user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP > user_subscription.ends_at) OR
                              (pricing_table.limit_downloads > 0 AND user_subscription.downloads >= pricing_table.limit_downloads) OR 
                              (pricing_table.limit_downloads_per_day > 0 AND user_subscription.daily_downloads >= pricing_table.limit_downloads_per_day AND user_subscription.daily_downloads_date = CURDATE())) AS expired, transactions.status = 'paid' as payment_status")
                            ->join('pricing_table', 'pricing_table.id', '=', 'user_subscription.subscription_id')
                            ->join(DB::raw('transactions USE INDEX(primary)'), 'user_subscription.transaction_id', '=', 'transactions.id')
                            ->where('user_subscription.user_id', auth()->user()->id)
                            ->orderBy('user_subscription.starts_at', 'DESC')
                            ->paginate(5);

      return view_('user', compact('user_subscriptions'));
    }


    public function user_notifications(Request $request)
    {
      $notifications = Notification::useIndex('users_ids', 'updated_at')
                            ->selectRaw("products.id as product_id, products.name, products.slug, notifications.updated_at, notifications.id, `for`,
                                          CASE
                                            WHEN `for` = 0 THEN IFNULL(products.cover, 'default.png')
                                            WHEN `for` = 1 OR `for` = 2 THEN IFNULL(users.avatar, 'default.webp')
                                          END AS `image`,
                                          CASE notifications.`for`
                                            WHEN 0 THEN 'New version is available for :product_name'
                                            WHEN 1 THEN 'Your comment has been approved for :product_name'
                                            WHEN 2 THEN 'Your review has been approved for :product_name'
                                          END AS `text`,
                                          IF(users_ids LIKE CONCAT('%|', ?,':0|%'), 0, 1) AS `read`", [Auth::id()])
                            ->leftJoin('products', 'products.id', '=', 'notifications.product_id')
                            ->leftJoin('users', 'users.id', '=', DB::raw(Auth::id()))
                            ->where('users_ids', 'REGEXP', '\|'.Auth::id().':(0|1)\|')
                            ->where('products.slug', '!=', null)
                            ->orderBy('updated_at', 'desc')
                            ->paginate(5);

      return view_('user', ['notifications' => $notifications]);
    }


    public function two_factor_authentication(Request $request)
    {
      $user = $request->user();

      if(!config('app.two_factor_authentication') || !$user->two_factor_auth || !$request->query('2fa_sec'))
      {
          abort(404);
      }

      if($request->isMethod('POST'))
      {
        if($request->post('2fa_sec') !== session('2fa_sec'))
        {
          return redirect('/');
        } 

        $request->validate(['verification_code.*' => 'required|numeric']);

        $code = array_filter($request->post('verification_code'), fn($input) => is_numeric($input));

        if(count($code) !== 6)
        {
          return back()->with(['user_message' => __('The verification code must contain 6 digits')]);
        }

        $code = implode('', $code);

        if(!verifyQRCode($request->user()->two_factor_auth_secret, $code))
        {
          return back()->with(['user_message' => __("Wrong code, please try again")]);
        }

        $user->update(['two_factor_auth_expiry' => config('app.two_factor_authentication_expiry') > 0 ? time()+(int)(config('app.two_factor_authentication_expiry')*60) : null]);
        
        return redirect($request->query('redirect', '/'));
      }

      config([
        "meta_data.name"  => __('Two Factor Authentication'),
        "meta_data.title" => __('Two Factor Authentication'),
      ]);

      $qrCodeUrl = null;

      if(!$request->user()->two_factor_auth_secret)
      {
        $response = generateQRCode(16, 200);

        $request->user()->update([
          'two_factor_auth_expiry' => null,
          'two_factor_auth_secret' => $response['secretKey'],
          'two_factor_auth_ip'     => $request->ip(),
        ]);

        $qrCodeUrl = $response['qrCodeUrl'];
      }

      return view('auth.two_factor_authenication', ['qrCodeUrl' => $qrCodeUrl]);
    }
}
