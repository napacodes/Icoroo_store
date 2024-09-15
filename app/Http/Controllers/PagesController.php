<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ DB, Validator };


class PagesController extends Controller
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
                      'orderby' => ['regex:/^(name|active|views|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $pages = Page::useIndex('description')
                      ->select('pages.id', 'pages.name', 'pages.slug', 'pages.updated_at', 'pages.active', 'pages.views')
                      ->where('pages.name', 'like', "%{$keywords}%")
                      ->orWhere('pages.slug', 'like', "%{$keywords}%")
                      ->orWhere('pages.short_description', 'like', "%{$keywords}%")
                      ->orWhere('pages.content', 'like', "%{$keywords}%")
                      ->orWhere('pages.tags', 'like', "%{$keywords}%")
                      ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $pages = Page::useIndex($request->orderby ?? 'primary')
                      ->select('pages.id', 'pages.name', 'pages.slug', 'pages.updated_at', 'pages.active', 'pages.views')
                      ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $pages = $pages->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.pages.index', ['title' => __('Pages'),
                                       'pages' => $pages,
                                       'items_order' => $items_order,
                                       'base_uri' => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {      
      return view('back.pages.create', ['title' => __('Create page')]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
          'name' => 'bail|required|max:255|unique:pages',
          'content' => 'bail|required'
        ]);

        $page = new Page;

        $page->name = $request->name;
        $page->slug = Str::slug($request->name, '-');
        $page->short_description = $request->short_description;
        $page->content = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($request->post('content')):  $request->post('content');
        $page->tags = $request->tags;

        $page->save();

        $redirect = redirect()->route('pages');

        if(config('app.indexnow_key'))
        {
          $res = indexNow(page_url($page->slug));

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
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {        
        $page = Page::find($id) ?? abort(404);

        return view('back.pages.edit', ['title' => $page->name,
                                        'page'  => $page]);
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
        $request->validate([
          'name'    => "bail|required|max:255|unique:pages,name,{$id}",
          'content' => 'bail|required'
        ]);

        $page = Page::find($id) ?? abort(404);

        if($page->deletable)
        {
          $page->name               = $request->name;
          $page->slug               = Str::slug($request->name, '-');
        }
        
        $page->short_description  = $request->short_description;
        $page->content            = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($request->post('content')):  $request->post('content');
        $page->tags               = $request->tags;
        $page->updated_at         = date('Y-m-d H:i:s');

        $page->save();

        $redirect = redirect()->route('pages');

        if(config('app.indexnow_key'))
        {
          $res = indexNow(page_url($page->slug));

          $redirect = $redirect->with(['user_message' => $res['message'] ?? null]);
        }

        return $redirect;
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  string $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      $ids = array_filter(explode(',', $ids));

      Page::whereIn('id', $ids)->where('deletable', 1)->where('slug', '!=', 'support')->delete();

      return redirect()->route('pages');
    }



    // Toggle "Active" status
    public function status(Request $request)
    {
      $mutable = $res = true;
      
      if($page = Page::find($request->id))
      {
        if($page->slug == 'support')
        {
          $mutable = false;
        }
      }

      if($mutable)
      {
        $res = DB::update("UPDATE pages USE INDEX(primary) SET active = IF(active = 1, 0, 1) WHERE id = ?", [$request->id]);
      }

      return response()->json(['success' => (bool)$res ?? false]);
    }
}
