<?php

namespace App\Http\Controllers\ApiController;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class InvoiceApi extends Controller
{
    //
    public function webhook_paynet(Request $request)
    {
        Log::info($request);
        
        response()->json(['success' => 'success'], 200);


    }
}
