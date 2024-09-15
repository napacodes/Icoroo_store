<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ User_Prepaid_Credit, Transaction, Prepaid_Credit };
use Illuminate\Support\Facades\{ Validator, DB };

class UsersPrepaidCreditsController extends Controller
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
                        'orderby' => ['regex:/^(pack|buyer|amount|credits|status|updated_at)$/i', 'required_with:order'],
                        'order'   => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                      ]);

        if($validator->fails()) abort(404);

        $base_uri = [];

        $users_prepaid_credits = User_Prepaid_Credit::selectRaw('user_prepaid_credits.id, user_prepaid_credits.credits, 
            user_prepaid_credits.updated_at, prepaid_credits.amount, prepaid_credits.name as pack, users.email as buyer, transactions.status, 
            transactions.refunded, IF(? IS NOT NULL, NOW() > DATE_ADD(user_prepaid_credits.updated_at, INTERVAL ? DAY), 0) as expired',
            [array_fill(0, 2, config('app.prepaid_credits.expires_in', null))])
            ->join('transactions', 'transactions.id', '=', 'user_prepaid_credits.transaction_id')
            ->join('users', 'users.id', '=', 'user_prepaid_credits.user_id')
            ->join('prepaid_credits', 'prepaid_credits.id', '=', 'user_prepaid_credits.prepaid_credits_id');

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $users_prepaid_credits = $users_prepaid_credits->where(function($builder) use($keywords)
        {
          $builder->where('users.email', 'like', "%{$keywords}%")
                  ->orWhere('user_prepaid_credits.credits', 'like', "%{$keywords}%")
                  ->orWhere('prepaid_credits.name', 'like', "%{$keywords}%");
        });

        $users_prepaid_credits = $users_prepaid_credits->orderBy('id', 'desc');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $users_prepaid_credits->orderBy($request->orderby ?? 'id', $request->order ?? 'DESC');
      }

      $users_prepaid_credits = $users_prepaid_credits->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.user_prepaid_credits', compact('users_prepaid_credits', 'items_order', 'base_uri'));
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
      if(!$user_prepaid_credits = User_Prepaid_Credit::find($id))
      {
        return json(['status' => 0, 'message' => __('Record not found.')]);
      }

      if(!is_numeric($request->post('newCredits')) || $request->post('newCredits') < 0)
      {
        return json(['status' => 0, 'message' => __('The new credits amount must be greater than 0.')]); 
      }

      $user_prepaid_credits->credits = $request->post('newCredits');
      $user_prepaid_credits->save();

      $user_prepaid_credits = User_Prepaid_Credit::selectRaw('user_prepaid_credits.credits, transactions.status, 
          transactions.refunded, IF(? IS NOT NULL, NOW() > DATE_ADD(user_prepaid_credits.updated_at, INTERVAL ? DAY), 0) as expired',
          [array_fill(0, 2, config('app.prepaid_credits.expires_in', null))])
          ->join('transactions', 'transactions.id', '=', 'user_prepaid_credits.transaction_id')
          ->join('users', 'users.id', '=', 'user_prepaid_credits.user_id')
          ->join('prepaid_credits', 'prepaid_credits.id', '=', 'user_prepaid_credits.prepaid_credits_id')
          ->where('user_prepaid_credits.id', $id)
          ->first();

      $status = '';

      if($user_prepaid_credits->credits == 0)
      {
        $status = '<span class="ui basic rounded-corner fluid label red">'. __('Spent') .'</span>';
      }
      elseif($user_prepaid_credits->refunded)
      {
        $status = '<span class="ui basic rounded-corner fluid label red">'. __('Refunded') .'</span>';
      }
      elseif($user_prepaid_credits->expired)
      {
        $status = '<span class="ui basic rounded-corner fluid label red">'. __('Expired') .'</span>';
      }
      else
      {
        $status = '<span class="ui basic rounded-corner fluid label teal">'. __('Active') .'</span>';
      }

      $user_prepaid_credits->status = $status;

      return ['status' => 1, 'message' => $status];
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
        DB::transaction(function() use($ids)
        {
            $ids = array_filter(explode(',', $ids));

            User_Prepaid_Credit::destroy($ids);

            $ids = array_map("wrap_str", $ids);

            Transaction::whereIn('products_ids', $ids)->delete();
        });

        return redirect()->route('users_prepaid_credits');
    }
}
