<?php

namespace App\Http\Controllers;

use App\Support\PublicSiteContent;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function __invoke(): View
    {
        PublicSiteContent::applyLocale();

        return view('documentation', [
            'docs' => PublicSiteContent::documentation(),
            'welcome' => PublicSiteContent::welcome(),
        ]);
    }
}
