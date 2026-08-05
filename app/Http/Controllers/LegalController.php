<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function msa(): View
    {
        return view('legal.msa');
    }
}
