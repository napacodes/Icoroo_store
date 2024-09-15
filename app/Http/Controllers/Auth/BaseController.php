<?php
	
	namespace App\Http\Controllers\Auth;

  use App\Http\Controllers\Controller;


	class BaseController extends Controller 
  {
    public function __construct()
    {
      config([
        "meta_data.name" => config('app.name'),
        "meta_data.title" => config('app.title'),
        "meta_data.description" => config('app.description'),
        "meta_data.url" => url()->current(),
        "meta_data.fb_app_id" => config('app.fb_app_id'),
        "meta_data.image" => asset('storage/images/'.(config('app.cover') ?? 'cover.jpg'))
      ]);
    }
	}