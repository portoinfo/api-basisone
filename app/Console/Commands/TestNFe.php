<?php

namespace App\Console\Commands;

use App\Http\Controllers\NFeController;
use App\Services\NfeService;
use Illuminate\Console\Command;

class TestNFe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:nfe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //$payload = NfeService::payload(1, 1, 106800);
        $controller = new NFeController();
        $controller->envia();
    }
}
