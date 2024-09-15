<?php

namespace App\Http\Controllers;

use App\Libraries\{ GoogleDrive, DropBox, YandexDisk, OneDrive, AmazonS3, Wasabi, GoogleCloudStorage };
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\{ Category, Product, Product_Price, License, Notification, Temp_Direct_Url };
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{ DB, Storage, Validator, File, Cache };
use ZipArchive;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpClient\HttpClient;


class ProductsController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(id|name|price|newest|sales|category|active|trending|featured|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $request->keywords];

        $products = Product::useIndex('primary')
                            ->selectRaw('products.id, products.name, products.newest, products.slug, products.trending, products.cover, 
                                         products.featured, products.active, product_price.price, products.file_name, products.direct_download_link,
                                         products.updated_at, count(transactions.id) as sales, products.preview,
                                         categories.name as category')
                            ->leftJoin('transactions', 'products_ids', 'LIKE', DB::raw('concat("\'%", products.id, "%\'")'))
                            ->leftJoin('categories', 'categories.id', '=', 'products.category')
                            ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                            ->leftJoin('product_price', function($join)
                            {
                              $join->on('product_price.license_id', '=', 'licenses.id')
                                   ->on('product_price.product_id', '=', 'products.id');
                            })
                            ->where('products.name', 'like', "%{$keywords}%")
                            ->orWhere('products.slug', 'like', "%{$keywords}%")
                            ->orWhere('products.overview', 'like', "%{$keywords}%")
                            ->orWhere('products.tags', 'like', "%{$keywords}%")
                            ->orWhere('products.short_description', 'like', "%{$keywords}%")
                            ->groupBy('products.id', 'products.name', 'products.slug', 'products.trending', 
                                        'products.featured', 'products.active', 'product_price.price', 
                                        'products.file_name','products.updated_at', 'categories.name', 
                                        'products.preview', 'products.newest')
                            ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }
        
        $index = $request->orderby ?? 'primary';
        $index = preg_match('/^sales|price|id$/i', $index) ? 'primary' : $index;
        
        $orderBy = $request->orderby ?? 'id';
        $order = $request->order ?? 'DESC';
        
        $order_query = "{$orderBy} {$order}";
        
        if($orderBy !== 'id')
        {
            $order_query = "{$orderBy} {$order}, id DESC";
        }

        $products = Product::useIndex($index)
                            ->selectRaw('products.id, products.name, products.newest, products.slug, products.trending, products.cover, 
                                         products.featured, products.active, product_price.price as price, products.file_name, products.direct_download_link, 
                                         products.updated_at, count(transactions.id) as sales,
                                         categories.name as category, products.preview')
                            ->leftJoin('transactions', 'products_ids', 'LIKE', DB::raw('concat("\'%", products.id, "%\'")'))
                            ->leftJoin('categories', 'categories.id', '=', 'products.category')
                            ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                            ->leftJoin('product_price', function($join)
                            {
                              $join->on('product_price.license_id', '=', 'licenses.id')
                                   ->on('product_price.product_id', '=', 'products.id');
                            })
                            ->groupBy('products.id', 'products.name', 'products.newest', 'products.slug', 'products.trending', 
                                        'products.featured', 'products.active', 'product_price.price', 
                                        'products.updated_at', 'categories.name', 'products.file_name', 'products.preview')
                            ->orderByRaw($order_query);
      }


      $products = $products->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.products.index', compact('products', 'items_order', 'base_uri'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      extract(Category::products());

      $product_id = get_auto_increment('products');

      $cover       = file_uploaded('public/storage/covers', $product_id);
      $download    = file_uploaded('storage/app/downloads', $product_id);
      $screenshots = file_uploaded('public/storage/screenshots', $product_id);
      $preview     = file_uploaded('public/storage/previews', $product_id);

      return view("back.products.create", compact('category_children', 'category_parents', 'product_id', 
                                                   'cover', 'download', 'screenshots', 'preview'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product_id = get_auto_increment('products');

        $product = new Product;

        $request->validate([
            'name'              => 'bail|required|max:255',
            'license'           => 'nullable|array',
            'license.price'     => 'nullable|array',
            'license.price.*'     => 'nullable|numeric|min:0',
            'license.promo_price' => 'nullable|array',
            'overview'          => 'nullable|string',
            'short_description' => 'nullable|string',
            'permalink'         => 'nullable|unique:products,permalink',
            'category'          => 'required|numeric',
            'preview_url'       => 'url|nullable',
            'pages'             => 'nullable|numeric|gte:0',
            'words'             => 'nullable|numeric|gte:0',
            'minimum_price'     => 'nullable|numeric|gte:0',
            'language'          => 'nullable|string|max:255',
            'formats'           => 'nullable|string|max:255',
            'authors'           => 'nullable|string|max:255',
            'tags'              => 'string|nullable|max:255',
            'bpm'               => 'nullable|string|max:255',
            'label'             => 'nullable|string|max:255',
            'bit_rate'          => 'numeric|nullable',
            'question'          => 'array|nullable',
            'question.*'        => 'nullable|string',
            'answer'            => 'array|nullable',
            'answer.*'          => 'nullable|string',
            '_name_'     => 'array|nullable',
            '_name_.*'   => 'nullable|string',
            '_value_'    => 'array|nullable',
            '_value_.*'  => 'nullable|string',
            'preview'           => 'nullable|file',
            'text'              => 'nullable|array',
            'text_type'         => 'nullable|array',
            'free'         => 'nullable|array',
            'free.*'       => 'nullable|string',
            'promotional_price_time'        => 'nullable|array',
            'promotional_price_time.*'      => 'nullable|string',
            'file_host'         => ['nullable', 'regex:/^(local|onedrive|dropbox|google|yandex|amazon_s3|wasabi|gcs)$/i'],
            'direct_download_link' => 'nullable|url',
            'direct_upload_link'   => 'nullable|url',
            'stock'             => 'nullable|numeric|gte:0',
            'preview_type'      => 'nullable|string|in:video,audio,document,archive,other',
            'enable_license'    => 'nullable|in:0,1',
            'for_subscriptions' => 'nullable|in:0,1',
            'affiliate_link'    => 'nullable|string',
            'group_buy_price' => 'nullable|numeric',
            'group_buy_min_buyers' => ['nullable','numeric', Rule::requiredIf(fn () => $request->post('group_buy_price') > 0)],
            'group_buy_expiry' => 'nullable|date',
            'meta_tags' => 'array|nullable',
            'meta_tags.*' => 'nullable|string',
        ]);

        if($subcategories = $request->input('subcategories'))
        {
          $product->subcategories = implode(',', array_map('wrap_str', explode(',', $subcategories)));
        }

        $product->name                = $request->input('name');
        $product->slug                = Str::slug($product->name, '-');
        $product->short_description   = $request->input('short_description');
        $product->overview            = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($request->post('overview')):  $request->post('overview');
        $product->category            = $request->input('category');
        $product->notes               = $request->input('notes');
        $product->version             = $request->input('version');
        $product->preview_url         = $request->input('preview_url');
        $product->pages               = $request->input('pages');
        $product->words               = $request->input('words');
        $product->label               = $request->input('label');
        $product->language            = $request->input('language');
        $product->formats             = $request->input('formats');
        $product->file_extension      = $request->input('file_extension');
        $product->authors             = $request->input('authors');
        $product->release_date        = $request->input('release_date');
        $product->last_update         = $request->input('last_update');
        $product->included_files      = $request->input('included_files');
        $product->tags                = $request->input('tags');
        $product->software            = $request->input('software');
        $product->db                  = $request->input('database');
        $product->compatible_browsers = $request->input('compatible_browsers');
        $product->compatible_os       = $request->input('compatible_os');
        $product->file_host           = $request->input('file_host');
        $product->file_host           = $product->file_host == 'ftp' ? 'local' : $product->file_host; 
        $product->high_resolution     = $request->input('high_resolution');
        $product->preview_type        = $request->input('preview_type');
        $product->preview_extension   = $request->input('preview_extension');
        //$product->download_type       = $request->input('download_type');
        $product->for_subscriptions   = $request->input('for_subscriptions') ?? '0';
        $product->faq                 = json_encode(array_filter($this->faq($request)));
        $product->table_of_contents   = json_encode(array_filter($this->tableOfContents($request)));
        $product->additional_fields   = json_encode(array_filter($this->additional_fields($request)));
        $product->promotional_price_time = null;
        $product->hidden_content      = $request->input('hidden_content');
        $product->hidden_content      = mb_strlen(strip_tags($product->hidden_content)) ? $product->hidden_content : null;
        $product->hidden_content      = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($product->hidden_content):  $product->hidden_content;
        $product->stock               = $request->input('stock') ?? null;
        $product->enable_license      = $request->input('enable_license') ?? null;
        $product->bpm                 = $request->input('bpm') ?? null;
        $product->bit_rate            = $request->input('bit_rate') ?? null;
        $product->minimum_price       = $request->input('minimum_price') ?? null;
        $product->fake_sales          = $request->input('fake_sales');
        $product->fake_reviews        = json_encode($this->fake_reviews($request));
        $product->fake_comments       = json_encode($this->fake_comments($request));
        $product->affiliate_link      = $request->input('affiliate_link') ?? null;
        $product->permalink           = config('app.permalink_url_identifer') ? ($request->input('permalink') ?? null) : null;
        $product->group_buy_price       = $request->group_buy_price;
        $product->group_buy_min_buyers  = $request->group_buy_min_buyers;
        $product->group_buy_expiry      = $request->group_buy_expiry ? \Illuminate\Support\Carbon::parse($request->group_buy_expiry)->timestamp : null;
        $product->meta_tags             = $request->meta_tags ? json_encode($request->meta_tags) : null;

        if(config('app.products_by_country_city'))
        {
          $product->country_city = json_encode($request->country_city);
        }

        if(array_filter($request->input('promotional_price_time')))
        {
          if($request->input('promotional_price_time.from') > $request->input('promotional_price_time.to'))
          {
            return back()->withErrors(['promotional_price_time' => __('The given time for promotional price is incorrect.')])
                         ->withInput();
          }

          $product->promotional_price_time = json_encode($request->input('promotional_price_time'));
        }
        
        if($free = array_filter($request->input('free')))
        {
          $product->free = json_encode($free);
        }


        // Cover
        if($cover = file_uploaded('public/storage/covers', $product_id))
        {
          $extension = pathinfo($cover, PATHINFO_EXTENSION);

          if(!in_array($extension, ['jpg', 'jpeg', 'svg', 'png', 'gif', 'webp']))
          {
            return back()->withInput()->withErrors(['main_file' => __('Only jpg, jpeg, svg, png and gif file type are allowed for cover.')]);
          }

          $product->cover = $cover;
        }



        // Screenshots
        if($screenshots_zip = file_uploaded('public/storage/screenshots', $product_id))
        {
          if(pathinfo($screenshots_zip, PATHINFO_EXTENSION) === 'zip')
          {
            $zip = new ZipArchive;

            if($zip->open(public_path("storage/screenshots/{$screenshots_zip}")))
            {
              $files = [];

              for($i = 0; $i < $zip->numFiles; $i++)
              {
                $filename  = $zip->getNameIndex($i);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if(in_array($extension, ['jpeg', 'jpg', 'png', 'svg']))
                {
                  $new_name = "{$product_id}-{$i}.{$extension}";

                  $zip->renameIndex($i, $new_name);

                  $files[] = $new_name;
                }
                else
                {
                  $zip->deleteIndex($product_id);
                }
              }

              $zip->close();

              $zip->open(public_path("storage/screenshots/{$screenshots_zip}"));

              if($zip->extractTo(public_path("storage/screenshots")))
              {
                $product->screenshots = implode(',', $files);
              }

              $zip->close();
            }
          }
        }


        // preview
        if($preview = $request->post('preview_upload_link'))
        {
            $request->validate(['preview_upload_link' => 'url']);

            $response = get_remote_file_content($preview, $product_id);

            if(isset($response['error']))
            {
              return back_with_errors(['preview_upload_link' => $response['error']]);
            }

            if(File::put(public_path("storage/previews/{$response['file_name']}"), (string)$response['content']))
            {
              $product->preview = $response['file_name'];
            }
        }
        elseif($preview = $request->post('preview_direct_link'))
        {
            $product->preview = urldecode($preview);
        }
        else
        {
          if($local_file = file_uploaded('public/storage/previews', $product_id))
          {
            $product->preview = $local_file;
          }
        }


        // Main file | folder
        if($main_file_upload_link = $request->post('main_file_upload_link'))
        {
          $request->validate(['main_file_upload_link' => 'url']);
          
          $response = get_remote_file_content($main_file_upload_link, $product_id);

          if(isset($response['error']))
          {
            return back_with_errors(['main_file_upload_link' => $response['error']]);
          }

          if(File::put(storage_path("app/downloads/{$response['file_name']}"), (string)$response['content']))
          {
            $product->file_name = $response['file_name'];
            $product->file_host = 'local';
          }
        }
        elseif($main_file_download_link = $request->post('main_file_download_link'))
        {
          $product->file_name = null;
          $product->file_extension = @pathinfo(strtok($main_file_download_link, '?'), PATHINFO_EXTENSION);
          $product->direct_download_link = urldecode($main_file_download_link);
          $product->file_host = null;

          Temp_Direct_Url::where('product_id', $product_id)->delete();
        }
        elseif($request->post('file_name'))
        {
          $product->file_name = $request->post('file_name');
          $product->file_host = $request->post('file_host');
        }
        elseif($file_name = file_uploaded('storage/app/downloads', $product_id))
        {
          Temp_Direct_Url::where('product_id', $product_id)->delete();

          $extension = pathinfo($file_name, PATHINFO_EXTENSION);

          $product->file_name = $file_name;
          $product->file_host = 'local';
        }

        $product->save();

        if(isset($screenshots_zip))
        {
          File::delete(public_path("storage/screenshots/{$screenshots_zip}"));
        }

        $this->save_product_prices($request, $product_id);

        $this->update_temp_direc_url($product, $product_id = null);

        Cache::forget(parse_url(item_url($product), PHP_URL_PATH));

        $redirect = redirect()->route('products');

        if(config('app.indexnow_key'))
        {
          $res = indexNow($product->permalink ?? item_url($product));
          
          $redirect = $redirect->with(['user_message' => $res['message'] ?? null]);
        }

        return $redirect;
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id)
    {
      $product = Product::find($id) ?? abort(404);

      $product_prices = Product_Price::where('product_id', $id)->get()->toArray();
      $product_prices = array_combine(array_column($product_prices, 'license_id'), $product_prices);

      $product->free = json_decode($product->free);
      $product->promotional_price_time = json_decode($product->promotional_price_time);
      
      $product->table_of_contents      = json_decode($product->table_of_contents, true) ?? [];

      $product->faq = json_decode($product->faq, true) ?? [];

      $product->additional_fields   = json_decode($product->additional_fields, true) ?? [];
      $product->fake_reviews        = json_decode($product->fake_reviews, true) ?? [];
      $product->fake_comments       = json_decode($product->fake_comments, true) ?? [];
      
      $product->country_city = json_decode($product->country_city) ?? (object)[];

      $product->question = array_column($product->faq, 'question');
      $product->answer   = array_column($product->faq, 'answer');
      
      $product->text_type = array_column($product->table_of_contents, 'text_type');
      $product->text      = array_column($product->table_of_contents, 'text');

      $product->_name_  = array_column($product->additional_fields, '_name_');
      $product->_value_ = array_column($product->additional_fields, '_value_');

      extract(Category::products());

      if($product->subcategories)
      {
        $subcategories = explode(',', $product->subcategories);
        $subcategories = array_map('unwrap_str', $subcategories);

        $product->subcategories = implode(',', $subcategories);
      }

      if($screenshots_files = File::glob(public_path("storage/screenshots/{$product->id}-*.*")))
      {
        foreach($screenshots_files as &$screenshot_file)
        {
          $screenshot_file = basename($screenshot_file);
        }
      }

      $cover       = file_uploaded('public/storage/covers', $product->id);
      $download    = file_uploaded('storage/app/downloads', $product->id);
      $screenshots = file_uploaded('public/storage/screenshots', $product->id);
      $preview     = file_uploaded('public/storage/previews', $product->id);

      $product->setAttribute('preview_direct_link', preg_match('/^http/i', $product->preview) ? $product->preview : null); 

      $product->meta_tags = $product->meta_tags ? json_decode($product->meta_tags) : null;
      
      return view("back.products.edit",  compact('download', 'cover', 'screenshots', 'preview', 'product', 'product_prices',
                                                    'category_children', 'category_parents', 'screenshots_files'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $product = Product::find($id);
        $copy    = clone $product;

        $request->validate([ 
            'name'                      => 'bail|required|max:255',
            'permalink'                 => ['nullable', Rule::unique('products')->ignore($id)],
            'license'                   => 'nullable|array',
            'license.price'             => 'nullable|array',
            'license.price.*'           => 'nullable|numeric|min:0',
            'license.promo_price'       => 'nullable|array',
            'overview'                  => 'nullable|string',
            'short_description'         => 'string|nullable',
            'category'                  => 'required|numeric',
            'promotional_price'         => 'bail|nullable|numeric|gt:0',
            'country_city'              => 'nullable|array',
            'country_city.*'            => 'nullable|string',
            'stock'                     => 'nullable|numeric|gte:0',
            'enable_license'            => 'nullable|in:0,1',
            'for_subscriptions'         => 'nullable|in:0,1',
            'preview_url'               => 'url|nullable',
            'pages'                     => 'nullable|numeric|gte:0',
            'words'                     => 'nullable|numeric|gte:0',
            'minimum_price'             => 'nullable|numeric|gte:0',
            'language'                  => 'nullable|string|max:255',
            'formats'                   => 'nullable|string|max:255',
            'authors'                   => 'nullable|string|max:255',
            'tags'                      => 'string|nullable|max:255',
            'label'                     => 'nullable|string|max:255',
            'text'                      => 'nullable|array',
            'text_type'                 => 'nullable|array',
            'preview'                   => 'nullable|file',
            'preview_type'              => 'nullable|string|in:video,audio,document,archive,other',
            //'download_type'             => 'nullable|string|in:video,audio,pdf,zip,other',
            'direct_download_link'      => 'nullable|url',
            'direct_upload_link'        => 'nullable|url',
            '_name_'                    => 'array|nullable',
            '_name_.*'                  => 'nullable|string',
            '_value_'                   => 'array|nullable',
            '_value_.*'                 => 'nullable|string',
            'free'                      => 'nullable|array',
            'free.*'                    => 'nullable|string',
            'promotional_price_time'    => 'nullable|array',
            'promotional_price_time.*'  => 'nullable|string',
            'affiliate_link'            => 'nullable|string',
            'group_buy_price'           => 'nullable|numeric',
            'group_buy_min_buyers'      => ['nullable','numeric', Rule::requiredIf(fn () => $request->post('group_buy_price') > 0)],
            'group_buy_expiry'          => 'nullable|date',
            'meta_tags'                 => 'array|nullable',
            'meta_tags.*'               => 'nullable|string',
        ]);

        if($subcategories = $request->input('subcategories'))
        {
          $subcategories = explode(',', $subcategories);
          $subcategories = array_map('unwrap_str', $subcategories);

          $product->subcategories = implode(',', array_map('wrap_str', $subcategories));
        }
        
        $product->name                = $request->input('name');
        $product->slug                = Str::slug($product->name, '-');
        $product->short_description   = $request->input('short_description');
        $product->overview            = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($request->post('overview')):  $request->post('overview');
        $product->category            = $request->input('category');
        $product->notes               = $request->input('notes');
        $product->version             = $request->input('version');
        $product->preview_url         = $request->input('preview_url');
        $product->preview_type        = $request->input('preview_type');
        $product->preview_extension   = $request->input('preview_extension');
        //$product->download_type       = $request->input('download_type');
        $product->pages               = $request->input('pages');
        $product->words               = $request->input('words');
        $product->language            = $request->input('language');
        $product->label               = $request->input('label');
        $product->formats             = $request->input('formats');
        $product->authors             = $request->input('authors');
        $product->release_date        = $request->input('release_date');
        $product->last_update         = $request->input('last_update');
        $product->included_files      = $request->input('included_files');
        $product->file_extension      = $request->input('file_extension');
        $product->tags                = $request->input('tags');
        $product->software            = $request->input('software');
        $product->db                  = $request->input('database');
        $product->faq                 = json_encode(array_filter($this->faq($request)));
        $product->table_of_contents   = json_encode(array_filter($this->tableOfContents($request)));
        $product->additional_fields   = json_encode(array_filter($this->additional_fields($request)));
        $product->compatible_browsers = $request->input('compatible_browsers');
        $product->compatible_os       = $request->input('compatible_os');
        $product->high_resolution     = $request->input('high_resolution');
        $product->for_subscriptions   = $request->input('for_subscriptions') ?? '0';
        $product->free                = array_filter($request->free ?? []) ? json_encode($request->free) : null;
        $product->promotional_price_time = null;
        $product->hidden_content      = $request->input('hidden_content');
        $product->hidden_content      = mb_strlen(strip_tags($product->hidden_content)) ? $product->hidden_content : null;
        $product->hidden_content      = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($product->hidden_content):  $product->hidden_content;
        $product->stock               = $request->input('stock') ?? null;
        $product->enable_license      = $request->input('enable_license') ?? null;
        $product->bpm                 = $request->input('bpm') ?? null;
        $product->bit_rate            = $request->input('bit_rate') ?? null;
        $product->minimum_price       = $request->input('minimum_price') ?? null;
        $product->fake_sales          = $request->input('fake_sales');
        $product->fake_reviews        = json_encode($this->fake_reviews($request));
        $product->fake_comments       = json_encode($this->fake_comments($request));
        $product->affiliate_link      = $request->input('affiliate_link') ?? null;
        $product->permalink           = config('app.permalink_url_identifer') ? ($request->input('permalink') ?? null) : null;
        $product->group_buy_price       = $request->group_buy_price;
        $product->group_buy_min_buyers  = $request->group_buy_min_buyers;
        $product->group_buy_expiry      = $request->group_buy_expiry ? \Illuminate\Support\Carbon::parse($request->group_buy_expiry)->timestamp : null;
        $product->meta_tags             = $request->meta_tags ? json_encode($request->meta_tags) : null;

        if(config('app.products_by_country_city'))
        {
          $product->country_city = json_encode($request->country_city);
        }

        if(array_filter($request->input('promotional_price_time')))
        {
          if($request->input('promotional_price_time.from') > $request->input('promotional_price_time.to'))
          {
            return back()->withErrors(['promotional_price_time' => __('The given time for promotional price is incorrect.')])
                         ->withInput();
          }

          $product->promotional_price_time = json_encode($request->input('promotional_price_time'));
        }


        // Cover
        if($cover = file_uploaded('public/storage/covers', $id))
        {
          $extension = pathinfo($cover, PATHINFO_EXTENSION);

          if(!in_array($extension, ['jpg', 'jpeg', 'svg', 'png', 'gif', 'webp']))
          {
            return back()->withInput()->withErrors(['cover' => __('Only jpg, jpeg, svg, png and gif file type are allowed for cover.')]);
          }

          $product->cover = $cover;
        }


        // Screenshots
        if($screenshots_zip = File::glob(public_path("storage/screenshots/{$id}.zip")))
        {
          $screenshots_zip = $screenshots_zip[0];

          $zip = new ZipArchive;

          if($zip->open($screenshots_zip))
          {
            $files = [];

            for($i = 0; $i < $zip->numFiles; $i++)
            {
              $filename  = $zip->getNameIndex($i);
              $extension = pathinfo($filename, PATHINFO_EXTENSION);

              if(!in_array($extension, ['jpg', 'jpeg', 'png', 'svg']))
                continue;

              $new_name = "{$id}-{$i}.{$extension}";

              $zip->renameIndex($i, $new_name);

              $files[] = $new_name;
            }

            $zip->close();

            $zip->open($screenshots_zip);

            if($zip->extractTo(public_path("storage/screenshots")))
            {
              $product->screenshots = implode(',', $files);
            }

            $zip->close();
          }
        }
        else
        {
          if($screenshots = File::glob(public_path("storage/screenshots/{$id}-*.*")))
          {
            $files = [];

            foreach($screenshots as $file)
            {
              $files[] = basename($file);
            }

            $product->screenshots = implode(',', $files);
          }
          else
          {
            $product->screenshots = null;
          }
        }


        // preview
        if($preview = $request->post('preview_upload_link'))
        {
            $request->validate(['preview_upload_link' => 'url']);

            $response = get_remote_file_content($preview, $id);

            if(isset($response['error']))
            {
              return back_with_errors(['preview_upload_link' => $response['error']]);
            }

            if(File::put(public_path("storage/previews/{$response['file_name']}"), (string)$response['content']))
            {
              $product->preview = $response['file_name'];
            }
        }
        elseif($preview = $request->post('preview_direct_link'))
        {
          $product->preview = urldecode($preview);
        }
        else
        {
          if($local_file = file_uploaded('public/storage/previews', $id))
          {
            $product->preview = $local_file;
          }
        }


        $product->updated_at = date('Y-m-d H:i:s');


        $product->direct_download_link = null;

        // Main file | folder
        if($main_file_upload_link = $request->post('main_file_upload_link'))
        {
          $request->validate(['main_file_upload_link' => 'url']);
          
          $response = get_remote_file_content($main_file_upload_link, $id);

          if(isset($response['error']))
          {
            return back_with_errors(['main_file_upload_link' => $response['error']]);
          }

          if(File::put(storage_path("app/downloads/{$response['file_name']}"), (string)$response['content']))
          {
            $product->file_name = $response['file_name'];
            $product->file_host = 'local';
          }
        }
        elseif($main_file_download_link = $request->post('main_file_download_link'))
        {
          $product->file_name = null;
          $product->file_extension = @pathinfo(strtok($main_file_download_link, '?'), PATHINFO_EXTENSION);
          $product->direct_download_link = urldecode($main_file_download_link);
          $product->file_host = null;

          Temp_Direct_Url::where('product_id', $product->id)->delete();
        }
        elseif($request->post('file_name'))
        {
          $product->file_name = $request->post('file_name');
          $product->file_host = $request->post('file_host');
        }
        elseif($file_name = file_uploaded('storage/app/downloads', $id))
        {
          Temp_Direct_Url::where('product_id', $product->id)->delete();

          $extension = pathinfo($file_name, PATHINFO_EXTENSION);

          $product->file_name = $file_name;
          $product->file_host = 'local';
        }

        $product->save();

        if($request->notify_buyers)
        {
          Notification::notifyUsers($product->id, null, 0);
        }

        if(isset($screenshots_zip))
        {
          $screenshots_zip ? File::delete($screenshots_zip) : null;
        }

        $this->remove_old_files($product, $copy);

        $this->save_product_prices($request, $id);

        $this->update_temp_direc_url($product, $product_id = null);

        Cache::forget(parse_url(item_url($product), PHP_URL_PATH));

        $redirect = redirect()->route('products');

        if(config('app.indexnow_key'))
        {
          $res = indexNow($product->permalink ?? item_url($product));
          
          $redirect = $redirect->with(['user_message' => $res['message'] ?? null]);
        }

        return $redirect;
    }



    public function update_temp_direc_url(Product $product, $product_id = null)
    {
      if($product->file_name && isset($product->file_host) && !preg_match("/^google|local$/i", $product->file_host))
      {
        $host_class = [
          'dropbox'   => 'DropBox',
          'yandex'    => 'YandexDisk',
          'amazon_s3' => 'AmazonS3',
          'wasabi'    => 'Wasabi',
          'gcs'       => "GoogleCloudStorage",
        ];

        $config = [
          "item_id"    => $product->file_name,
          "cache_id"   => $product->id,
          "file_name"  => "{$product->id}-{$product->slug}.{$product->file_extension}",
          "expiry"     => null,
          "bucketName" => null,
          "bucket"     => null,
          "options"    => null,
        ];

        return call_user_func(["\App\Libraries\\{$host_class[$product->file_host]}", 'download'], $config);
      }
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      $ids = array_filter(explode(',', $ids));
      
      if(Product::destroy($ids))
      {
        foreach($ids as $id)
        {
          @$this->unlink_files($id);
        }
      }

      return redirect()->route('products');
    }




    public function status(Request $request)
    {      
      $res = DB::update("UPDATE products USE INDEX(primary) SET {$request->status} = IF({$request->status} = 1, 0, 1) WHERE id = ?", 
                      [$request->id]);

      Cache::forget(parse_url(item_url(Product::find($request->id)), PHP_URL_PATH));

      return response()->json(['success' => (bool)$res ?? false]);
    }



    private function faq(Request $request)
    {
      $faq = [];

      if($request->post('question') && $request->post('answer'))
      {
        foreach($request->post('question') ?? [] as $k => $question)
        {
          if(! isset($request->post('answer')[$k])) continue;

          $faq[] = (object)['question' => strip_tags($question), 'answer' => strip_tags($request->post('answer')[$k])];
        }
      }

      return $faq;
    }


    private function fake_reviews(Request $request)
    {
      $fake_reviews = [];

      if($request->input('fake_reviews'))
      {
        foreach($request->input('fake_reviews.username') as $key => $fake_review)
        {
          $fake_reviews[] = [
            'username'    => $request->input("fake_reviews.username.{$key}"),
            'created_at'  => format_date($request->input("fake_reviews.created_at.{$key}", now()), "y-m-d h:i:s"),
            'review'      => $request->input("fake_reviews.review.{$key}"),
            'rating'      => $request->input("fake_reviews.rating.{$key}"),
          ];
        }

        $fake_reviews = array_filter($fake_reviews, function($review)
        {
          return count(array_filter($review)) === 4;
        });
      }

      return $fake_reviews;
    }


    private function fake_comments(Request $request)
    {
      $fake_comments = [];

      if($request->input('fake_comments'))
      {
        foreach($request->input('fake_comments.username') as $key => $fake_review)
        {
          $fake_comments[] = [
            'username'    => $request->input("fake_comments.username.{$key}"),
            'created_at'  => format_date($request->input("fake_comments.created_at.{$key}", now()), "y-m-d h:i:s"),
            'comment'      => $request->input("fake_comments.comment.{$key}"),
          ];
        }

        $fake_comments = array_filter($fake_comments, function($comment)
        {
          return count(array_filter($comment)) === 3;
        });
      }

      return $fake_comments;
    }




    private function additional_fields(Request $request)
    {
      $faq = [];

      if($request->post('_name_') && $request->post('_value_'))
      {
        foreach($request->post('_name_') ?? [] as $k => $name)
        {
          if(! isset($request->post('_value_')[$k])) continue;

          $faq[] = (object)['_name_' => strip_tags($name), '_value_' => strip_tags($request->post('_value_')[$k])];
        }
      }

      return $faq;
    }


    // Unlink "main file", "screenshots" and "cover"
    private function unlink_files(int $product_id)
    {
      try
      {
        File::delete(glob(storage_path("app/downloads/{$product_id}.*")));

        $screenshots = glob(public_path("storage/screenshots/{$product_id}-*.*"));
        $cover       = glob(public_path("storage/covers/{$product_id}.*"));
        $preview     = glob(public_path("storage/previews/{$product_id}.*"));

        File::delete(array_merge($screenshots, $cover, $preview));
      }
      catch(Exception $e)
      {
        
      }
    }



    public function list_files(Request $request)
    {
      return call_user_func("\App\Libraries\\$request->files_host::list_files", $request);
    }



    public function list_folders(Request $request)
    {
      return call_user_func("\App\Libraries\\$request->files_host::list_folders", $request);
    }
    

    
    // Search products for newsletter selections and others
    public function api(Request $request, $ids = null)
    {
      $products = \App\Models\Product::selectRaw('products.id, products.name, products.slug, 
                  products.short_description, products.cover, product_price.price, licenses.name as license_name, 
                  licenses.id as license_id, products.preview,
                  CASE
                    WHEN product_price.promo_price IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10)))
                      THEN product_price.promo_price
                    ELSE
                      NULL
                  END AS promotional_price')
                  ->leftJoin('licenses', 'licenses.regular', '=', DB::raw('1'))
                  ->leftJoin('product_price', function($join)
                  {
                    $join->on('product_price.license_id', '=', 'licenses.id')
                         ->on('product_price.product_id', '=', 'products.id');
                  })
                  ->where('active', '1');

      if($ids)
      {
        return $products->whereIn('products.id', $ids)->get();
      }

      if($request->keywords)
      {
        $products = $products->where('products.name', 'like', "%{$request->keywords}%");
      }

      if($request->where)
      {
        $products = $products->where($request->where);
      }

      $products = $products->limit($request->limit ?? 50)->get();

      return response()->json(['products' => $products]);
    }





    public function upload_file_async(Request $request)
    {
      if($file = $request->file('file'))
      {        
        $id = $request->post('id') ?? get_auto_increment('products');
        $destination = $request->post('destination') ?? abort(404);

        $file_name = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        if($destination === 'covers')
        {
          $extension = ($extension != 'gif') ? "webp" : $extension;
          
          Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
          
          $img = Image::make($file);

          if($crop = config("image.crop.{$request->type}"))
          {
            $img = $img->crop(...$crop);
          }

          if(config("image.watermark.{$request->type}") && config('app.watermark'))
          {
            $watermark = 'storage/images/'.config('app.watermark');

            $img = $img->insert($watermark, 'top-left')
                        ->insert($watermark, 'top')
                        ->insert($watermark, 'top-right')
                        ->insert($watermark, 'left')
                        ->insert($watermark, 'center')
                        ->insert($watermark, 'right')
                        ->insert($watermark, 'bottom-left')
                        ->insert($watermark, 'bottom')
                        ->insert($watermark, 'bottom-right');
          }
          
          $img->encode('webp', 100)->save("storage/covers/{$id}.{$extension}");

          $path = public_path("storage/covers/{$id}.{$extension}");
        }
        else
        {
          $path = $file->storeAs($destination, "{$id}.{$extension}", $destination === 'downloads' ? [] : ['disk' => 'public']);
        }

        $mimetype  = mb_strtolower(@mime_content_type($file->getPathName()));
        $extension = $mimetype ? config("mimetypes.{$mimetype}") : $file->guessExtension();

        return response()->json([
          'file_name' => $file_name, 
          'file_path' => $path, 
          'name' => "{$id}.{$extension}", 
          'status' => 'success',
          'extension' => $extension,
        ]);
      }

      return response()->json(['status' => 'error']);
    }



    public function delete_file_async(Request $request)
    {
      $path = urldecode($request->path);

      if(is_file(base_path($path)))
      {
        File::delete(base_path($path));
      }      
    }



    private function remove_old_files($product, $copy)
    {
      if($product->file_name !== $copy->file_name)
      {
        $file_path = storage_path("app/downloads/{$copy->file_name}");

        if(is_file($file_path))
        {
          File::delete($file_path);
        }
      }


      $files = [];

      foreach(['cover', 'preview'] as $file)
      {
        if($product->$file !== $copy->$file)
        {
          $file_path = public_path("storage/{$file}s/{$copy->$file}");

          if(is_file($file_path))
          {
            $files[] = $file_path;
          }
        }
      }

      if($files)
      {
        File::delete($files);
      }
    }


    private function tableOfContents(Request $request)
    {
      $table_of_contents = [];

      if($request->post('text_type') && $request->post('text'))
      {
        foreach($request->post('text_type', []) as $k => $text_type)
        {
          if(! isset($request->post('text')[$k])) continue;

          $table_of_contents[] = (object)['text_type' => strip_tags($text_type), 'text' => strip_tags($request->post('text')[$k])];
        }
      }

      return $table_of_contents;
    }


    public function get_temp_url(Request $request)
    {
      $response = get_remote_file_content($request->url, $request->id);

      if(isset($response['error']))
      {
        return '';
      }

      if(File::put(public_path("storage/temp/{$response['file_name']}"), (string)$response['content']))
      {
        return asset_("storage/temp/{$response['file_name']}");
      }
    } 



    private function save_product_prices(Request $request, $product_id)
    {
      $product_prices = [];

      foreach($request->input('license.price', []) as $license_id => $price)
      {
        $is_regular_license = License::find($license_id)->regular;
        
        if(is_null($price) && !$is_regular_license) continue;
        
        $promo_price = $request->input("license.promo_price.{$license_id}");
        
        if($price >= 0)
        {
          $product_prices[] = ['product_id' => $product_id, 'license_id' => $license_id, 'price' => $price, 'promo_price' => $promo_price];
        }
      }

      Product_Price::where('product_id', $product_id)->delete();

      $product_prices = array_filter($product_prices, function($item)
                        {
                          $price = $item['price'] ?? null;
                          return !is_null($price);
                        });

      if($product_prices)
      {
        return Product_Price::insert($product_prices);
      }
    }

}