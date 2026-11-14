<?php

namespace App\Http\Controllers;

use App\Support\PharmacyMockData;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.ventas', [
            'data' => PharmacyMockData::sales(),
        ]);
    }
}
