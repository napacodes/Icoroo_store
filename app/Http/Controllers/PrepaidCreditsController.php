<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ Prepaid_Credit };
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ DB, Validator };


class PrepaidCreditsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $prepaid_credits =  Prepaid_Credit::useIndex('name')
                            ->where('prepaid_credits.name', 'like', "%{$keywords}%")
                            ->orderBy('id', 'DESC');
      }
      else
      {
        $prepaid_credits = Prepaid_Credit::useIndex('primary')->orderBy('order', 'asc');
      }

      $prepaid_credits = $prepaid_credits->get();

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.prepaid_credits.index', compact('prepaid_credits', 'items_order'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return view('back.prepaid_credits.create');
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
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'specs' => 'nullable|string'
        ]);

        $prepaid_credits = new Prepaid_Credit;

        $prepaid_credits->name = $request->post('name');
        $prepaid_credits->amount = $request->post('amount');
        $prepaid_credits->specs = base64_encode($request->post('specs'));
        $prepaid_credits->popular = $request->post('popular');
        $prepaid_credits->discount = $request->post('discount');

        if($request->post('popular') == 1)
        {
            DB::update('UPDATE prepaid_credits SET popular = 0');
        }

        $prepaid_credits->save();
        
        return redirect()->route('prepaid_credits');
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $prepaid_credits = Prepaid_Credit::find($id) ?? abort(404);

        return view('back.prepaid_credits.edit', compact('prepaid_credits'));
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
        $prepaid_credits = Prepaid_Credit::find($id) ?? abort(404);

        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'specs' => 'nullable|string'
        ]);

        $prepaid_credits->name = $request->post('name');
        $prepaid_credits->amount = $request->post('amount');
        $prepaid_credits->specs = base64_encode($request->post('specs'));
        $prepaid_credits->popular = $request->post('popular');
        $prepaid_credits->discount = $request->post('discount');

        if($request->post('popular') == 1)
        {
            DB::update('UPDATE prepaid_credits SET popular = 0');
        }

        $prepaid_credits->save();
        
        return redirect()->route('prepaid_credits');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($ids)
    {
        if($ids = explode(',', $ids))
        {
            Prepaid_Credit::destroy($ids);
        }

        return redirect()->route('prepaid_credits');
    }



    public function sort(Request $request)
    {
        foreach($request->input('order', []) as $id => $order)
        {
            Prepaid_Credit::find($id)->update(['order' => $order]);
        }

        return json(['status' => true]);
    }

}
