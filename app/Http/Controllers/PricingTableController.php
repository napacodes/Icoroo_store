<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pricing_Table;
use Illuminate\Support\Str;


class PricingTableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $pricing_table = Pricing_Table::selectRaw("*, IF(limit_downloads = 0, 'Unlimited', limit_downloads) AS limit_downloads,
                        IF(limit_downloads_per_day = 0, 'Unlimited', limit_downloads_per_day) AS limit_downloads_per_day,
                        IF(days = 0, 'Unlimited', days) AS days")
                      ->get();

      return View('back.pricing.index', ['title' => __('Pricing table'), 'pricing_table' => $pricing_table]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      return view('back.pricing.create', ['title'  => __('Create subscription')]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $pricing_table = new Pricing_Table;

      $request->validate([
          'name'            => 'bail|required|max:255|unique:pricing_table,name',
          'title'           => 'nullable|string|max:255',
          'price'           => 'nullable|numeric|gte:0',
          'days'            => 'nullable|numeric|gte:0',
          'limit_downloads' => 'nullable|numeric|gte:0',
          'limit_downloads_per_day' => 'nullable|numeric|gte:0',
          'limit_downloads_same_item' => 'nullable|numeric|gte:0',
          'description'     => 'nullable|string',
          'color'           => 'string|nullable',
          'products'        => ['nullable', 'regex:/^([\d,?]+)$/'],
          'position'        => 'nullable|numeric|gte:0',
          'popular'         => 'nullable|numeric|in:0,1',
      ]);

      $specifications = [];

      for($i=0; $i < count($request->input('specifications.text')); $i++)
      {
        if($request->input("specifications.text.{$i}"))
        {
          $specifications[] = ["text" => $request->input("specifications.text.{$i}"), "included" => $request->input("specifications.included.{$i}") === "1"];
        }
      }

      $pricing_table->name             = $request->post('name');
      $pricing_table->title            = $request->post('title');
      $pricing_table->slug             = Str::slug($pricing_table->name, '-');
      $pricing_table->price            = $request->post('price') ?? 0;
      $pricing_table->days             = $request->post('days') ?? 0;
      $pricing_table->limit_downloads  = $request->post('limit_downloads') ?? 0;
      $pricing_table->limit_downloads_per_day = $request->post('limit_downloads_per_day') ?? 0;
      $pricing_table->limit_downloads_same_item = $request->post('limit_downloads_same_item') ?? null;
      $pricing_table->description      = $request->post('description');
      $pricing_table->color            = $request->post('color');
      $pricing_table->products         = $request->post('products');
      $pricing_table->categories       = $request->post('categories');
      $pricing_table->position         = $request->post('position');
      $pricing_table->specifications   = json_encode($specifications, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
      $pricing_table->popular          = $request->post('popular');

      if($pricing_table->popular)
      {
        \DB::update("UPDATE pricing_table SET popular = 0");
      }

      $pricing_table->save();

      return redirect()->route('pricing_table');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      if(!$pricing_table = Pricing_Table::find($id))
        abort(404);

      $pricing_table->specifications = json_decode($pricing_table->specifications);

      return view('back.pricing.edit', ['title' => $pricing_table->name, 'subscription' => $pricing_table]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $pricing_table  = Pricing_Table::find($id) ?? abort(404);
      $specifications = [];

      for($i=0; $i < count($request->input('specifications.text')); $i++)
      {
        if($request->input("specifications.text.{$i}"))
        {
          $specifications[] = ["text" => $request->input("specifications.text.{$i}"), "included" => $request->input("specifications.included.{$i}") === "1"];
        }
      }

      $request->validate([
        'name'                      => "bail|required|max:255|unique:pricing_table,name,{$id}",
        'title'                     => 'nullable|string|max:255',
        'price'                     => 'nullable|numeric|gte:0',
        'days'                      => 'nullable|numeric|gte:0',
        'limit_downloads'           => 'nullable|numeric|gte:0',
        'limit_downloads_per_day'   => 'nullable|numeric|gte:0',
        'limit_downloads_same_item' => 'nullable|numeric|gte:0',
        'description'               => 'nullable|string',
        'color'                     => 'string|nullable',
        'products'                  => ['nullable', 'regex:/^([\d,?]+)$/'],
        'position'                  => 'nullable|numeric|gte:0',
        'popular'                   => 'nullable|numeric|in:0,1',
      ]);

      $pricing_table->name             = $request->post('name');
      $pricing_table->title            = $request->post('title');
      $pricing_table->slug             = Str::slug($pricing_table->name, '-');
      $pricing_table->price            = $request->post('price') ?? 0;
      $pricing_table->days             = $request->post('days') ?? 0;
      $pricing_table->limit_downloads  = $request->post('limit_downloads') ?? 0;
      $pricing_table->limit_downloads_per_day = $request->post('limit_downloads_per_day') ?? 0;
      $pricing_table->limit_downloads_same_item = $request->post('limit_downloads_same_item');
      $pricing_table->description      = $request->post('description');
      $pricing_table->color            = $request->post('color');
      $pricing_table->updated_at       = date('Y-m-d H:i:s');
      $pricing_table->products         = $request->post('products');
      $pricing_table->categories       = $request->post('categories');
      $pricing_table->position         = $request->post('position');
      $pricing_table->specifications   = json_encode($specifications, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
      $pricing_table->popular          = $request->post('popular');

      if($pricing_table->popular)
      {
        \DB::update("UPDATE pricing_table SET popular = 0 WHERE id != ?", [$pricing_table->id]);
      }

      $pricing_table->save();

      return redirect()->route('pricing_table');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      Pricing_Table::destroy(explode(',', $ids));

      return redirect()->route('pricing_table');
    }
}
