<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\{ Hash, File };
use Intervention\Image\Facades\Image;


class AdminProfileController extends Controller
{

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Request $request)
	{
		$user = User::find($request->user()->id);

		return view('back.profile', ['title' => __('Edit profile'),
																 'user' => $user]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request)
	{		
		$id = $request->user()->id;

		$request->validate([
			'name' 			=> 'required|max:255|nullable',
			'email' 		=> "required|max:255|unique:users,email,{$id}",
			'password' 	=> 'nullable|max:255',
			'avatar' 		=> 'nullable|image|max:2048',
			'firstname' => 'nullable|max:255',
			'lastname' 	=> 'nullable|max:255',
			'two_factor_auth' => 'nullable|numeric|in:0,1',
		]);

		$user = User::find($id);

		$user->name = $request->name;
		$user->email = $request->email;
		$user->firstname = $request->firstname;
		$user->lastname = $request->lastname;
		$user->two_factor_auth = $request->two_factor_auth ?? '0';

		if($avatar = $request->file('avatar'))
		{
			if(File::exists(public_path("storage/avatars/{$user->avatar}")))
			{
				File::delete(public_path("storage/avatars/{$user->avatar}"));
			}

			$ext  = "webp";

      Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
      
      $img = Image::make($avatar);

      $img = 	$img->resize(128, null, function ($constraint) 
      				{
							    $constraint->aspectRatio();
							});

      $img->encode('webp', 100)->save("storage/avatars/{$id}.{$ext}");

      $user->avatar = "{$id}.{$ext}";
		}
		
		if($request->password)
		{
			$user->password = Hash::make($request->password);
		}

		$user->save();

		return redirect()->route('profile.edit');
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
}
