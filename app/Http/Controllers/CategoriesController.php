<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ DB, Cache, Schema, File };
use App\Models\Category;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;


class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   
      $categories = Category::useIndex('`for`')
                    ->selectRaw("categories.*, _categories.name as parent_name, if(categories.`for` = 1, 'products', 'posts') as `for`")
                    ->leftJoin('categories as _categories', '_categories.id', '=', 'categories.parent');

      if($request->for)
        $categories->where('categories.for', strtolower($request->for) === 'posts' ? 0 : 1);

      $categories = $categories->get();

      return View('back.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parents = Category::useIndex('parent')->select('id as value', 'name', 'for')->where('parent', '=', null)->orderBy('range', 'asc')->get();

      $parents_posts    = $parents->where('for', 0)->toArray();
      $parents_products = $parents->where('for', 1)->toArray();

      return view('back.categories.create', ['title'    => __('Create Category'),
                                             'parents' => $parents, 
                                             'parents_posts' => $parents_posts, 
                                             'parents_products' => $parents_products]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $category = new Category;
        $id       = get_auto_increment('categories');

        $request->validate([
            'for'         => 'bail|required|numeric|in:0,1',
            'name'        =>  ['bail', 'required', 'max:255', Rule::unique('categories')->where(function($query) use($request)
                              {
                                return $query->where(['name' => $request->input('name'), 
                                                      'for' => $request->input('for')]);
                              })],
            'description' => 'bail|max:255',
            'parent'      => 'nullable|numeric'
        ]);

        $category->name = $request->input('name');
        $category->slug = Str::slug($category->name, '-');
        $category->description  = $request->input('description');
        $category->range = $request->input('range');
        $category->for  = $request->input('for');
        $category->parent = ($request->input('parent') === "0") ? NULL : $request->input('parent');

        if($icon_file = $request->file('icon'))
        {
            $extension = $icon_file->getClientOriginalExtension();

            if(!preg_match('/^jpg|jpeg|png|ico|svg|webp$/i', $extension))
            {
                return back_with_errors(['icon' => __('The icon must be an image.')]);
            }

            $icon_file->storeAs("icons", "{$id}-{$category->slug}.{$extension}", ['disk' => 'public']);

            $category->icon = "{$id}-{$category->slug}.{$extension}";
        }
        elseif($icon_url = $request->post('icon'))
        {
            $response  = get_remote_file_content($icon_url);
            $extension = strtolower($response['extension']);
            $content   = (string)$response['content'] ?? null;

            if(preg_match('/^jpg|jpeg|png|ico|svg|webp$/i', $extension))
            {
                file_put_contents(public_path("storage/icons/{$id}-{$category->slug}.{$extension}"), $content);

                $category->icon = "{$id}-{$category->slug}.{$extension}";
            }
        }

        if(isset($category->icon) && !preg_match("/(svg|webp)/i", $category->icon))
        {
          Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
      
          $img = Image::make(public_path("storage/icons/{$category->icon}"));

          $img =  $img->resize(200, null, function($constraint) 
                  {
                    $constraint->aspectRatio();
                  });

          $img->encode('webp', 100)->save("storage/icons/{$id}-{$category->slug}.webp");

          @unlink(public_path("storage/icons/{$category->icon}"));

          $category->icon = "{$id}-{$category->slug}.webp";
        }

        $category->save();

        return redirect()->route('categories');
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
      $category = Category::find($id) ?? abort(404);

      $parents = Category::useIndex('parent')->select('id as value', 'name', 'for')->where('parent', '=', null)->orderBy('range', 'asc')->get();

      $parents_posts    = $parents->where('for', 0)->toArray();
      $parents_products = $parents->where('for', 1)->toArray();

      return view('back.categories.edit', [ 'title'            => $category->name,
                                            'parents'          => $parents,
                                            'category'         => $category,
                                            'parents_posts'    => $parents_posts,
                                            'parents_products' => $parents_products ]);
    }


    public function feature(Request $request)
    {
        $status = null;

        if(is_numeric($request->id))
        {
            $status = DB::update("UPDATE categories SET featured = IF(featured = 1, 0, 1) WHERE id = ?", [$request->id]);
        }

        return json(compact('status'));
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
        $request->validate([
          'name'   => ['bail', 'required', 'max:255', Rule::unique('categories')->where(function($query) use($request, $id)
                {
                  return $query->where(['name' => $request->input('name'), 
                                        'for'  => $request->input('for')])->where('id', '!=', $id);
                })],
          'parent' => 'nullable|numeric',
          'for'    => 'required|numeric|in:0,1'
        ]);

        $category = Category::find($id);

        $category->name                 = $request->input('name');
        $category->slug                 = Str::slug($category->name, '-');
        $category->description  = $request->input('description');
        $category->range                = $request->input('range');
        $category->for          = $request->input('for');
        $category->parent           = ($request->input('parent') === "0") ? NULL : $request->input('parent');

        if($icon_file = $request->file('icon'))
        {          
            $extension = $icon_file->getClientOriginalExtension();

            if(!preg_match('/^jpg|jpeg|png|ico|svg|webp$/i', $extension))
            {
                return back_with_errors(['icon' => __('The icon must be an image.')]);
            }

            $icon_file->storeAs("icons", "{$id}-{$category->slug}.{$extension}", ['disk' => 'public']);

            $category->icon = "{$id}-{$category->slug}.{$extension}";
        }
        elseif($icon_url = $request->post('icon'))
        {
            $response  = get_remote_file_content($icon_url);
            $extension = $response['extension'];
            $content   = (string)$response['content'] ?? null;

            if(preg_match('/^jpg|jpeg|png|ico|svg|webp$/i', $extension))
            {
                if(file_exists(public_path("storage/icons/{$category->icon}")))
                {
                    File::delete(public_path("storage/icons/{$category->icon}"));
                }

                file_put_contents(public_path("storage/icons/{$id}-{$category->slug}.{$extension}"), $content);

                $category->icon = "{$id}-{$category->slug}.{$extension}";
            }
        }
        else
        {
            if(file_exists(public_path("storage/icons/{$category->icon}")))
            {
                File::delete(public_path("storage/icons/{$category->icon}"));
            }

            $category->icon = null;
        }


        if(isset($category->icon) && !preg_match("/(svg|webp)/i", $category->icon))
        {
          Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
          
          $img = Image::make(public_path("storage/icons/{$category->icon}"));

          $img =  $img->resize(200, null, function($constraint) 
                  {
                    $constraint->aspectRatio();
                  });

          $img->encode('webp', 100)->save("storage/icons/{$id}-{$category->slug}.webp");

          @unlink(public_path("storage/icons/{$category->icon}"));

          $category->icon = "{$id}-{$category->slug}.webp";
        }

        $category->save();

        return redirect()->route('categories', \Illuminate\Support\Facades\Route::current()->for ?? '');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      Category::destroy(...explode(',', $ids));

      return redirect()->route('categories', \Illuminate\Support\Facades\Route::current()->for ?? '');
    }

}
