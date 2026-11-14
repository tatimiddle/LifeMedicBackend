<?php

namespace App\Http\Controllers;

use App\Support\PharmacyMockData;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.clientes', [
            'data' => PharmacyMockData::customers(),
        ]);
    }
}
