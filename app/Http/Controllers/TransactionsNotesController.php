<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{ Transaction, Product, User, Transaction_Note };
use Illuminate\Support\Facades\{ Validator, DB };

class TransactionsNotesController extends Controller
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
                      'orderby' => ['regex:/^(email|created_at|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      !$validator->fails() || abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $notes = Transaction_Note::select('transaction_note.id', 'transaction_note.created_at', 'transaction_note.updated_at', 
                    'transactions.reference_id', 'users.email')
                    ->join(DB::raw('users use index(primary)'), 'transaction_note.user_id', '=', 'users.id')
                    ->join(DB::raw('transactions use index(primary)'), 'transaction_note.transaction_id', '=', 'transactions.id')
                    ->where('users.email', 'like', "%{$keywords}%")
                    ->orWhere('reference_id', 'like', "%{$keywords}%")
                    ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $notes = Transaction_Note::select('transaction_note.id', 'transaction_note.created_at', 'transaction_note.updated_at', 
                            'transactions.reference_id', 'users.email')
                            ->join(DB::raw('users use index(primary)'), 'transaction_note.user_id', '=', 'users.id')
                            ->join(DB::raw('transactions use index(primary)'), 'transaction_note.transaction_id', '=', 'transactions.id')
                            ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $notes = $notes->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.transaction_notes', ['notes'=> $notes, 'items_order' => $items_order, 'base_uri' => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function reply(Request $request)
    {
        $request->validate([
            'refId'   => 'required|string',
            'email'   => 'required|email',
            'noteId'  => 'required|numeric',
            'message' => 'required|string'
        ]);

        $config = [
          'data'   => [
            'text' => nl2br($request->message),
            'subject' => __('Reply order notes - Transaction reference :ref.', ['ref' => $request->refId]),
            'user_email' => config('app.mail'),
          ],
          'action' => 'send',
          'view'   => 'mail.message',
          'to'     => $request->email,
          'reply_to' => config('mail.reply_to'),
          'subject' => __(':app_name - You have a response for your order :ref.', ['ref' => $request->refId, 'app_name' => config('app.name')])
        ];

        sendEmailMessage($config, false);

        $note = Transaction_Note::find($request->noteId);

        $note->updated_at = now();
        $note->save();

        return json(['status' => true, 'updated_at' => $note->updated_at->format('Y-m-d H:i:s')]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $note = Transaction_Note::find($request->id);

        return json(['response' => $note->content]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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

      Transaction_Note::destroy($ids);

      return redirect()->route('transaction_notes.index');
    }
}
