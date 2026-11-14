<?php

namespace App\Http\Controllers;

use App\Support\PharmacyMockData;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.plan-pro', [
            'plans' => PharmacyMockData::plans(),
        ]);
    }
}
