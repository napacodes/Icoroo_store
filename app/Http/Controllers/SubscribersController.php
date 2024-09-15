<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Newsletter_Subscriber as Subscriber, Product, Pricing_Table };
use Illuminate\Support\Facades\{ Validator, File };
use App\Events\NewMail;
use PHPHtmlParser\{ Options, StaticDom as DOM };



class SubscribersController extends Controller
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
                      'orderby' => ['regex:/^(email|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($request->orderby)
      {
        $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
      }

      $subscribers = Subscriber::useIndex($request->orderby ?? 'primary')
                                ->select('id', 'email', 'updated_at')
                                ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc')->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.newsletters.subscribers', ['title' => 'Newsletter Subscribers',
                                                   'subscribers' => $subscribers,
                                                   'items_order' => $items_order,
                                                   'base_uri' => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $templates = \File::glob(base_path("resources/views/mail/newsletters/*.blade.php"));
      $templates = array_reduce($templates, function($carry, $file)
      {
        $carry[trim(basename($file), '.blade.php')] = ["selected" => 0, "html" => ""];
        return $carry;
      }, []);

      $emails = Subscriber::select('email')->where('email', '!=', '')->get()->pluck('email')->toArray();

      $subscriptions = [];

      if(config('app.subscriptions.enabled'))
      {
        foreach(Pricing_Table::all() as $subscription)
        {
          $subscriptions[$subscription->id] = ['url' => pricing_plan_url($subscription), 'name' => $subscription->name, 'id' => $subscription->id];
        }
      }

      return View('back.newsletters.create', compact('emails', 'templates', 'subscriptions'));
    }

    /**
     * Send a newsletter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function send(Request $request)
    {
      $request->validate(['subject' => 'required', 'action' => 'required|in:render,send']);

      if(!$emails = array_filter(explode(',', $request->emails)))
      {
        if($emails = Subscriber::select('email')->get())
          $emails = array_column($emails->toArray(), 'email');
      }
      else
      {
        foreach($emails as $key => &$email)
        {
          $email = trim($email);

          if(!filter_var($email, FILTER_VALIDATE_EMAIL))
            unset($emails[$key]);
        }
      }

      if(!$emails)
      {
        $validator = Validator::make($request->all(), [])->errors()->add('Emails', __('Invalid emails input'));

        return back()->withErrors($validator)->withInput();
      }

      $mail_props = [
        'data' => [
          'html' => '',
          'selections' => []
        ],
        'view' => '',
        'to' => [],
        'subject' => $request->subject,
        'action' => $request->action
      ];

      if(strlen(strip_tags($request->newsletter_html)))
      {
        $dom = DOM::loadStr($request->newsletter_html);
        
        $imgs = $dom->find('img');

        foreach($imgs as $img)
        {
          $data = [
                    'name' => $img->getAttribute('data-filename') ?? '',
                    'src' => $img->getAttribute('src')
                  ];

          if(!File::exists(public_path("storage/newsletter/{$data['name']}")))
          {
            list($extension, $file_content) = explode(',', $data['src']);

            $extension = str_ireplace(['data:image/', ';base64'], '', $extension);

            $extension = pathinfo($data['name'], PATHINFO_EXTENSION) ?? $extension ?? 'jpg';
            $filename  = urlencode(pathinfo($data['name'], PATHINFO_FILENAME));

            File::put(public_path("storage/newsletter/{$filename}.{$extension}"), base64_decode($file_content));
          }

          $img->setAttribute('src', secure_asset("storage/newsletter/{$data['name']}"));
        }

        $mail_props = [
          'data'   => ['html' => $dom->outerHtml],
          'action' => $request->action,
          'view'   => 'mail.html',
          'to'     => $emails,
          'subject' => __($request->subject)
        ];
      }
      elseif($request->newsletter_text)
      {
        $mail_props = [
          'data'   => ['html' => $request->newsletter_text],
          'action' => $request->action,
          'view'   => 'mail.html',
          'to'     => $emails,
          'subject' => __($request->subject)
        ]; 
      }
      elseif($newsletter_template = json_decode($request->post('newsletter_template')))
      {
        $template = base64_decode($newsletter_template->html);

        $options = new Options();
        
        $options->setCleanupInput(false)
                ->setRemoveStyles(false);

        $html = DOM::loadStr($template, $options);

        foreach($html->find('.products .item') ?? [] as $product)
        {
          if(empty($product->getAttribute('href')) || !strlen($product->getAttribute('href')))
          {
            try
            {
              $product->delete();
            }
            catch(\Throwable $t){}
          }
        }

        foreach($html->find('.subscriptions > div > .item') ?? [] as $item)
        {
          $subscription_link = $item->find('.subscription-link', 0);

          if(is_null($subscription_link))
          {
            continue;
          }

          if(empty($subscription_link->getAttribute('href')) || !strlen($subscription_link->getAttribute('href')))
          {
            try
            {
              $item->delete();
            }
            catch(\Throwable $t){}
          }
        }

        foreach($html->find('.subscriptions > div > .item') ?? [] as &$item)
        {
          try
          {
            $item->find('.link', 0)->delete();
          }
          catch(\Throwable $t){}
        }

        if(!count($html->find('.subscriptions > div > .item')))
        {
          try
          {
            $html->find('.subscriptions', 0)->delete();
          }
          catch(\Throwable $t){}
        }


        foreach($html->find('[contenteditable]') ?? [] as $item)
        {
          $item->removeAttribute('contenteditable');
        }

        $template = $html->outerHtml;

        $mail_props = [
          'data'   => ['html' => $template],
          'action' => $request->action,
          'view'   => 'mail.raw',
          'to'     => $emails,
          'subject' => __($request->subject)
        ];
      }

      sendEmailMessage($mail_props, config('mail.mailers.smtp.use_queue'));
            
      return back()->with(['newsletter_sent' => __('Newsletter sent successfully')]);
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
        Subscriber::destroy(explode(',', $ids));

        return redirect()->route('subscribers');
    }


    public function get_template(Request $request)
    {
      return view("mail.newsletters.{$request->template}");
    }
}
