<?php

namespace App\Http\Controllers;

use App\Support\PublicSiteContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        if ($request->session()->has('central_user')) {
            return app(AppController::class)();
        }

        PublicSiteContent::applyLocale();

        return view('welcome', [
            'welcome' => PublicSiteContent::welcome(),
        ]);
    }
}
