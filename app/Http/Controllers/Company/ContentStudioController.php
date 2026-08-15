<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Support\MarketingContentPacks;
use Illuminate\Support\Facades\Auth;

class ContentStudioController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user->canAccessCompanyBooks()) {
            abort(403);
        }

        $packs = MarketingContentPacks::all();

        return view('company.content-studio', [
            'packs' => $packs,
            'siteUrl' => MarketingContentPacks::SITE,
            'foundingCode' => MarketingContentPacks::FOUNDING_CODE,
        ]);
    }
}
