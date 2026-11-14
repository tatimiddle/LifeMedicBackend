<?php

namespace App\Http\Controllers;

use App\Support\PharmacyMockData;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.facturas', [
            'data' => PharmacyMockData::invoices(),
        ]);
    }
}
