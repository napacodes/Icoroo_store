<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Post, Category};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{ DB, Validator, File };
use Intervention\Image\Facades\Image;

class PostsController extends Controller
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

        $posts = Post::useIndex('search')
                      ->select('posts.id', 'posts.name', 'posts.slug', 'posts.updated_at', 'posts.active', 'posts.views')
                      ->where('posts.name', 'like', "%{$keywords}%")
                      ->orWhere('posts.slug', 'like', "%{$keywords}%")
                      ->orWhere('posts.short_description', 'like', "%{$keywords}%")
                      ->orWhere('posts.content', 'like', "%{$keywords}%")
                      ->orWhere('posts.tags', 'like', "%{$keywords}%")
                      ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $posts = Post::useIndex($request->orderby ?? 'primary')
                      ->select('posts.id', 'posts.name', 'posts.slug', 'posts.updated_at', 'posts.active', 'posts.views')
                      ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $posts = $posts->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.posts.index', compact('posts', 'items_order', 'base_uri'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::useIndex('`for`')->select('id', 'name')->where('categories.for', 0)->get();

        return view('back.posts.create', ['title' => 'Create post', 'categories' => $categories]);
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
          'name' => 'bail|required|max:255|unique:posts',
          'content' => 'required',
          'short_description' => 'required',
          'cover' => 'required|image'
        ]);

        $category_id = null;

        if($request->input('new_category'))
        {
          $category_id = $this->add_new_category($request);
        }
        else
        {
          $request->validate([
            'category' => ['numeric', 'required', 
                            function ($attribute, $value, $fail) 
                            {                              
                              if(!Category::where(['id' => $value, 'for' => 0])->exists())
                                  $fail($attribute.' does not exist.');
                            }
                          ]
          ]);

          $category_id = $request->input('category');
        }

        $post  = new Post;

        $post_id = get_auto_increment('posts');

        $post->name = $request->name;
        $post->slug = Str::slug($request->name, '-');
        $post->short_description = $request->short_description;
        $post->content = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($request->post('content')):  $request->post('content');
        $post->tags = $request->tags;
        $post->category = $category_id;

        $ext  = mb_strtolower($request->file('cover')->extension());

        if($ext !== "svg")
        {
          Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
          
          $img = Image::make($request->file('cover'));

          $img->encode('webp', 100)->save("storage/posts/{$post_id}.webp");

          $post->cover = "{$post_id}.webp";
        }
        else
        {
          $request->file('cover')->storeAs('posts', "{$post_id}.{$ext}", ['disk' => 'public']);

          $post->cover = "{$post_id}.{$ext}";
        }

        $post->save();

        $redirect = redirect()->route('posts');

        if(config('app.indexnow_key'))
        {
          $res = indexNow(post_url($post->slug));
          
          $redirect = $redirect->with(['user_message' => $res['message']]);
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
        $post = Post::find($id) ?? abort(404);
        
        $categories = Category::useIndex('`for`')->select('id', 'name')->where('categories.for', 0)->get();

        return view('back.posts.edit', ['title' => $post->name,
                                        'post' => $post,
                                        'categories' => $categories]);
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
          'name' => ['bail', 'required', 'max:255', Rule::unique('posts')->ignore($id)],
          'content' => 'required',
          'short_description' => 'required'
        ]);

        $category_id = null;

        if($request->input('new_category'))
        {
          $category_id = $this->add_new_category($request);
        }
        else
        {
          $request->validate([
            'category' => ['numeric', 'required', 
                            function ($attribute, $value, $fail) 
                            {                              
                              if(!Category::where(['id' => $value, 'for' => 0])->exists())
                                  $fail($attribute.' '.__('does not exist'));
                            }
                          ]
          ]);

          $category_id = $request->input('category');
        }

        $post = Post::find($id);
        $copy = clone $post;

        $post->name = $request->name;
        $post->slug = Str::slug($request->name, '-');
        $post->category = $category_id;
        $post->short_description = $request->short_description;
        $post->content = config('app.html_editor') == 'tinymce_bbcode' ? bbcode_to_html($request->post('content')):  $request->post('content');
        $post->tags = $request->tags;

        if($request->file('cover'))
        {
          $ext  = mb_strtolower($request->file('cover')->extension());

          if($ext !== "svg")
          {
            Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
            
            $img = Image::make($request->file('cover'));

            $img->encode('webp', 100)->save("storage/posts/{$id}.webp");

            $post->cover = "{$id}.webp";
          }
          else
          {
            $request->file('cover')->storeAs('posts', "{$id}.{$ext}", ['disk' => 'public']);

            $post->cover = "{$id}.{$ext}";
          }
        }

        $post->updated_at = date('Y-m-d H:i:s');
        
        $post->save();

        $redirect = redirect()->route('posts');

        if(config('app.indexnow_key'))
        {
          $res = indexNow(post_url($post->slug));

          $redirect = $redirect->with(['user_message' => $res['message']]);
        }

        return $redirect;
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
      
      if(Post::destroy($ids))
      {
        foreach($ids as $id)
        {
          @File::delete(glob(storage_path("app/downloads/{$id}.*")));
        }
      }


      return redirect()->route('posts');
    }



    public function status(Request $request)
    {      
      $res = DB::update("UPDATE posts USE INDEX(primary) SET {$request->status} = IF({$request->status} = 1, 0, 1) WHERE id = ?", 
                      [$request->id]);

      return response()->json(['success' => (bool)$res ?? false]);
    }



    private function add_new_category($request)
    {
      $request->validate([
        'new_category' => ['required', 'max:255', 
                            function ($attribute, $value, $fail) 
                            {                              
                              if(Category::where(['name' => $value, 'for' => 0])->exists())
                                  $fail($attribute.' '.__('already exists'));
                            }
                          ]
      ]);

      $category = new Category;

      $category->name = $request->input('new_category');
      $category->slug = Str::slug($category->name, '-');
      $category->for  = 0;

      $category->save();

      return $category->id;
    }
}
