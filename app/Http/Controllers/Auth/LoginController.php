<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\BaseController;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\{ DB, Auth, Session };
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User;


class LoginController extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;


    protected function validateLogin(Request $request)
    {
        $rules = [
          $this->username() => 'required|string',
          'password' => 'required|string'
        ];

        if(captcha_is_enabled('login'))
        {
          if(captcha_is('mewebstudio'))
          {
            $rules['captcha'] = 'required|captcha';
          }
          elseif(captcha_is('google'))
          {
            $rules['g-recaptcha-response'] = 'required';
          }
        }

        $request->validate($rules, [
            'g-recaptcha-response.required' => __('Please verify that you are not a robot.'),
            'captcha.required' => __('Please verify that you are not a robot.'),
            'captcha.captcha' => __('Wrong captcha, please try again.'),
        ]);
    }


    public function redirectTo()
    {
        if(Auth::user()->role === 'superadmin')
        {
          return route('admin');
        }

        return request()->redirect ?? '/';
    }


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('guest')->except('logout');
    }




    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    { 
        config(['meta_data.name' => __('Login')]);

        return view('auth.login');
    }



    /**
     * Redirect the user to the authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider)
    {   
        return  Socialite::driver($provider)->redirect();
    }



    /**
     * Obtain the user information from the provider.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($provider)
    {
      try
      {
        $socialite = Socialite::driver(strtolower($provider));

        /*if(strtolower($provider) === 'facebook')
        {
          $socialite->usingGraphVersion('v10.0');        
        }*/

        $userSocial = $socialite->user();

        if($userSocial->accessTokenResponseBody ?? null)
        {
          $userSocial->email = $userSocial->getEmail();
        }

        $user = User::where(['email' => $userSocial->email])->first();

        if(!$user)
        {
            $user = new User;

            $user->name               = Str::slug($userSocial->getName() ?? explode('@', $userSocial->getEmail())[0]);
            $user->email              = $userSocial->getEmail();
            $user->provider_id        = $userSocial->getId();
            $user->provider           = $provider;
            $user->email_verified_at  = date('Y-m-d');

            $user->save();
        }
        else
        {
          if((string)$userSocial->id !== (string)$user->provider_id)
          {
            $user->firstname    = $userSocial->firstname ?? null;
            $user->lastname     = $userSocial->lastname ?? null;
            $user->name         = Str::slug($userSocial->name ?? explode('@', $userSocial->email)[0] ?? $userSocial->id);
            $user->provider_id  = $userSocial->id;
            $user->provider     = $provider;

            $user->save();
          }
        }

        Auth::login($user, true);

        return redirect($this->redirectTo());
      }
      catch(\Exception $e)
      {
        return redirect('/')->with(['user_message' => __('Unable to use :provider to login.', ['provider' => $provider])]);
      }
    }



    private function copy_avatar($userSocial, $update = false, $user = null)
    {
        // Disabled to avoid uploading malicious images from other social platforms

        try 
        {
          $avatar = $userSocial->getAvatar();

          if(!isset($avatar))
              return;

          if(!$update)
          {
            $avatar_id = get_auto_increment('users');
          }
          else
          {
            exists_or_abort($user, __('User id is missing'));

            @unlink(public_path("storage/avatars/{$user->avatar}"));

            $avatar_id = $user->id; 
          }

          $avatar = file_get_contents($avatar);

          $avatar_mime = (new \finfo(FILEINFO_MIME))->buffer($avatar);

          preg_match('/image\/(?P<type>\w+);.+/', $avatar_mime, $matches);

          $file_name = "{$avatar_id}.{$matches['type']}";

          if(copy($userSocial->avatar, public_path("storage/avatars/{$file_name}")))
          {
              return $file_name;
          }
        }
        catch(\Exception $e)
        {

        }
    }



    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
      return redirect($request->redirect ?? '/');
    }
}
