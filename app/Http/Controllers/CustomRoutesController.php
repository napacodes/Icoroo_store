<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ Custom_Route };
use Illuminate\Support\Facades\{ Validator, DB };


class CustomRoutesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->update_csrf_except();

        $validator =  Validator::make($request->all(),
                      [
                        'orderby' => ['regex:/^(name|active|views|updated_at)$/i', 'required_with:order'],
                        'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                      ]);

        if($validator->fails()) abort(404);

        $custom_routes =  Custom_Route::selectRaw('*');

        $base_uri = [];

        if($keywords = $request->keywords)
        {
          $base_uri = ['keywords' => $keywords];

          $custom_routes = $custom_routes->where(function($builder)
          {
            $builder->where('custom_routes.name', 'LIKE', "%{$keywords}%")
                    ->orWhere('custom_routes.slug', 'LIKE', "%{$keywords}%")
                    ->orWhere('custom_routes.method', 'LIKE', "%{$keywords}%")
                    ->orWhere('custom_routes.view', 'LIKE', "%{$keywords}%");
          })
          ->orderBy('id', 'desc');
        }
        else
        {
          if($request->orderby)
          {
            $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
          }

          $custom_routes = $custom_routes->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
        }

        $custom_routes = $custom_routes->paginate(15);

        $items_order = $request->order === 'desc' ? 'asc' : 'desc';

        return View('back.custom_routes.index', compact('custom_routes', 'items_order', 'base_uri'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return View('back.custom_routes.create');
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
          "name" => "required|string|max:255",
          "view" => "required|string|max:255",
          "method" => "required|string|in:get,post,head,put",
          "csrf_protection" => "nullable|numeric|in:0,1",
        ]);

        $custom_route = new Custom_Route;

        $custom_route->name = $request->post('name');
        $custom_route->slug = slug($request->post('name'));
        $custom_route->view = $request->post('view');
        $custom_route->method = $request->post('method');
        $custom_route->csrf_protection = $request->post('csrf_protection');

        if(trim(strip_tags($request->post('content'))))
        {
          file_put_contents(base_path("resources/views/custom/{$custom_route->view}"), $request->post('content'));
        }
        else
        { 
          file_put_contents(base_path("resources/views/custom/{$custom_route->view}"), "");
        }

        $custom_route->save();

        return redirect()->route('custom_routes')->with(['user_message' => __('A new route has been added successfully.')]);
    } 


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $custom_route = Custom_Route::find($id);

        return view('back.custom_routes.edit', compact('custom_route'));
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
        $custom_route = Custom_Route::find($id);

        $request->validate([
          "name" => "required|string|max:255",
          "view" => "required|string|max:255",
          "method" => "required|string|in:get,post,head,put",
          "csrf_protection" => "nullable|numeric|in:0,1",
        ]);

        $custom_route->name            = $request->post('name');
        $custom_route->slug            = slug($request->post('name'));
        $custom_route->view            = $request->post('view');
        $custom_route->method          = $request->post('method');
        $custom_route->csrf_protection = $request->post('csrf_protection');

        if(trim(strip_tags($request->post('content'))))
        {
          file_put_contents(base_path("resources/views/custom/{$custom_route->view}"), $request->post('content'));
        }

        $custom_route->save();

        return redirect()->route('custom_routes')
                         ->with(['user_message' => __(':name route has been updated successfully.', ['name' => $custom_route->name])]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function status(Request $request)
    {      
      $res = DB::update("UPDATE custom_routes USE INDEX(primary) SET $request->status = IF($request->status = 1, 0, 1) WHERE id = ?", 
             [$request->id]);

      if($request->status == 'csrf_protection')
      {
        $this->update_csrf_except();
      }

      return response()->json(['success' => (bool)$res ?? false]);
    }


    protected function update_csrf_except()
    {
      if($custom_routes = Custom_Route::where('active', 1)->get())
      {
        $except = file(base_path(".csrf_except"));
        $except = array_map('trim', $except);

        foreach($custom_routes as $custom_route)
        {
          if(!$custom_route->csrf_protection && !in_array(trim($custom_route->slug), $except))
          {
            $except[] = $custom_route->slug;
          }
          elseif($custom_route->csrf_protection && in_array(trim($custom_route->slug), $except))
          {
            $i = array_search(trim($custom_route->slug), $except);
            unset($except[$i]);
          }
        }

        file_put_contents(base_path(".csrf_except"), implode(PHP_EOL, $except), LOCK_EX);
      }
    }
}
