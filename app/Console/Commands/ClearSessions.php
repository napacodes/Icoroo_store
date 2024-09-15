<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all user sessions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $driver = config('session.driver');
        
        if($driver === "file")
        {
            $directory = config('session.files');

            \File::cleanDirectory($directory);       
        }
        elseif($driver === "database")
        {
            $table = config('session.table');
            
            \DB::table($table)->truncate();
        }
    }
}
