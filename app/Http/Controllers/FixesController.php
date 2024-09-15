<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use ZipArchive;
use Illuminate\Support\Facades\{ File };
use Throwable;

class FixesController extends Controller
{
    public function index(Request $request)
    {
        $fixes = [];

        try
        {
            $client = new Client(['verify' => false, 'http_errors' => false, 'timeout' => 10, 'headers' => ['purchase_code' => env('PURCHASE_CODE')]]);

            $payload = [
                "json" => [
                    "item" => "valexa",
                ]
            ];

            $fixes_api_url = config('app.codemayer_api')."/fixes";

            $response = $client->get($fixes_api_url, $payload);

            if($response->getStatusCode() == 200)
            {
                $fixes = json_decode($response->getBody());
            }
        }
        catch(Throwable $e)
        {

        }

        return view('back.fixes', compact('fixes'));
    }


    public function install(Request $request)
    {
        try
        {
            $client = new Client(['verify' => false, 'http_errors' => false, 'timeout' => 10, 'headers' => ['purchase_code' => env('PURCHASE_CODE')]]);

            $fixes_api_url = config('app.codemayer_api')."/fixes/install";

            $payload = [
                "json" => [
                    "item"    => "valexa",
                    "id"      => $request->get('id'),
                    "version" => $request->get('version')
                ]
            ];

            $response = $client->post($fixes_api_url, $payload);

            if($response->getStatusCode() == 200)
            {
                $response = json_decode((string)$response->getBody());
                $status   = $response->status ?? null;

                if($status)
                {
                  $zipfile = public_path("storage/temp/{$request->id}-{$request->version}.zip");

                  if(file_put_contents($zipfile, base64_decode($response->content)))
                  {
                      $zip = new ZipArchive;

                      if($zip->open($zipfile) === true)
                      {
                        $zip->extractTo(base_path());
                        $zip->close();

                        $message = __('Fix :version has been installed successfully.', ['version' => $request->version]);

                        update_env_var('APP_VERSION', wrap_str($request->version));

                        File::delete($zipfile);
                      }
                      else
                      {
                        $message = __('The server was unable to extract the fix zip file.');
                      }

                      return redirect()->route('fixes.index')->with(['message' => $message]);
                  }
                }
                else
                {
                  return back()->message(['message' => $response->messsage]);
                }
            }
            else
            {
              return back()->with(['message' => __('Request returned error 404.')]);
            }
        }
        catch(Throwable $e)
        {
            return back()->with(['message' => __('Unable to install the fix.')]);
        }
    }
}
