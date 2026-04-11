<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;
use App\Invoice;
use App\Service;
use App\Company;
use App\Mail\SendMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
class CreateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea las facuturas';

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
        
        $companies = Company::Where('deleted_at', '!=',  null)->where('active', 1);


        foreach ($companies as $company) {
           
            

        }
      

     
    }
}
