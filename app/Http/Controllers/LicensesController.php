<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ License, Product };
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;


class LicensesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        !\Validator::make($request->all(),
            [
              'orderby' => ['regex:/^(name|updated_at)$/i', 'required_with:order'],
              'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
            ])->fails() || abort(404);


        $base_uri = [];

        $licenses = License::selectRaw('licenses.id, licenses.name, licenses.regular, licenses.updated_at');

        if($keywords = $request->keywords)
        {
          $base_uri = ['keywords' => $keywords];

          $licenses = $licenses->where('licenses.name', 'like', "%{$keywords}%")
                        ->orderBy('id', 'DESC');
        }
        else
        {
          if($request->orderby)
          {
            $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
          }

          $licenses = $licenses->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
        }

        $licenses = $licenses->paginate(15);

        $items_order = $request->order === 'desc' ? 'asc' : 'desc';

        return View('back.licenses.index', compact('licenses', 'items_order', 'base_uri'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.licenses.create');
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
            'name' => ['bail', 'required', 'max:255', Rule::unique('licenses')->where(function($query) use($request) 
                                                        {
                                                           return $query->where('name', $request->post('name'));
                                                        })],
            'regular' => 'nullable|numeric|in:0,1'
        ]);

        $license = new License;

        $license->name = $request->post('name');
        $license->regular = $request->post('regular') ? '1' : '0';

        if($license->regular === '1')
        {
            \DB::update('UPDATE licenses SET regular = 0');
        }

        $license->save();

        return redirect()->route('licenses')->with(['message' => __('Done')]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $license = License::find($id) ?? abort(404);

        return view('back.licenses.edit', compact('license'));
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
        $license = License::find($id) ?? abort(404);

        $request->validate([
            'name' => ['required', 'max:255', Rule::unique('licenses')->where(function($query) use($request) 
                                                {
                                                   return $query->where('name', $request->post('name'));
                                                })->ignore($license->id)],
            'regular' => 'nullable|numeric|in:0,1'
        ]);

        $license->name = $request->post('name');
        $license->regular = $request->post('regular') ? '1' : '0';

        if($license->regular === '1')
        {
            \DB::update("UPDATE licenses SET regular = 0 WHERE id != ?", [$license->id]);
        }

        $license->save();

        return redirect()->route('licenses')->with(['message' => __('Done')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
        License::destroy(explode(',', $ids));

        return redirect()->route('licenses');
    }
}
