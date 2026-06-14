<?php

namespace App\Http\Controllers;

use App\Support\PublicSiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicLocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        PublicSiteContent::setLocale($locale);

        return redirect()->to($request->headers->get('referer', route('welcome')));
    }
}
