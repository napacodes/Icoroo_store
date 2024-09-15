<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\{ File };
use PhpZip\ZipFile;
use Throwable;

class ExtensionsController extends Controller
{
    public function index(Request $request)
    {
        $extensions = [];

        try
        {
            $installed_exts = $this->getInstalledExtensions();
            $_extensions    = array_map('basename', File::glob(base_path("extensions/*"), GLOB_ONLYDIR));

            foreach($_extensions as &$extension)
            {
                $setup = $this->getSetupFile($extension);

                $extensions[$setup->name] = $setup;

                $extensions[$setup->name]->installed = 0;
                $extensions[$setup->name]->installed_version = null;

                if(is_object($setup))
                {
                  $extensions[$setup->name]->installed = isset($installed_exts[$setup->name]);
                  $extensions[$setup->name]->installed_version = $extensions[$setup->name]->installed ? $installed_exts[$setup->name]['version'] : null;
                }
            }
        }
        catch(Throwable $e)
        {

        }

        return view("back.extensions", compact('extensions'));
    }



    public function install(Request $request)
    {
        $message = null;
        $setup   = $this->getSetupFile($request->name);

        if(is_array($setup))
        {
            return back()->with($setup);
        }

        // Installing extension
        if(is_dir(base_path("extensions/{$setup->name}")))
        {
          $installed_exts = $this->getInstalledExtensions();

          $installed = in_array("{$setup->name}", $installed_exts);

          if($installed)
          {
            $this->uninstall($request);
          }

          foreach($setup->files as $file => $destination)
          {
              if(is_dir(base_path("extensions/{$setup->name}/{$file}")))
              {
                  File::copyDirectory(base_path("extensions/{$setup->name}/{$file}"), base_path("{$destination}/".basename($file)));
              }
              else
              {
                  File::copy(base_path("extensions/{$setup->name}/{$file}"), base_path("{$destination}/".basename($file)));
              }
          }

          $installed_exts[$setup->name] = $setup;

          $result = file_put_contents(base_path("extensions/installed_exts.json"), 
                                      json_encode($installed_exts, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

          $message =  $result 
                      ? __(":name has been installed.", ['name' => "{$setup->name} V{$setup->version}"]) 
                      : __("Failde to install :name extension.", ['name' => "{$setup->name} V{$setup->version}"]);
        }

        return back()->with(['message' => $message]);
    }



    public function uninstall(Request $request)
    {
        $status         = false;
        $installed_exts = $this->getInstalledExtensions();
        $setup          = $installed_exts[$request->name] ?? null;

        if($setup)
        {
          foreach($setup['files'] as $file => $destination)
          {
              if(is_dir(base_path("extensions/{$setup['name']}/{$file}")))
              {
                  File::deleteDirectory(base_path("{$destination}/".basename($file)));
              }
              else
              {
                  File::delete(base_path("{$destination}/".basename($file)));
              }
          }

          unset($installed_exts[$request->name]);

          file_put_contents(base_path("extensions/installed_exts.json"), json_encode($installed_exts, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

          $status = true;
        }

        if($request->redirect)
        {
          return back()->with(['message' => $status ? __("The extension has been uninstalled successfuly.") : __("This extension is not installed yet.")]);
        }

        return $status;
    }



    private function getInstalledExtensions()
    {
        $installed_exts = [];

        if(file_exists(base_path("extensions/installed_exts.json")))
        {
          $installed_exts = json_decode(file_get_contents(base_path("extensions/installed_exts.json")), true) ?? [];
        }

        return $installed_exts;
    }



    private function getSetupFile($extension_name)
    {
        $setup = @file_get_contents(base_path("extensions/{$extension_name}/setup.json"));

        if(!$setup)
        {
          return ['message' => __('Unable to get setup.json file from the extension zip file.')];
        }

        if(!$setup = json_decode($setup))
        {
            return ['message' => __("setup.json file has malformatted content.")];
        }

        return $setup;
    }
}
