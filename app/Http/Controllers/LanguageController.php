<?php

namespace App\Http\Controllers;

use App\Services\LanguageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function __invoke(Request $request, LanguageService $language): RedirectResponse
    {
        $locale = $request->string('locale')->toString();

        if (! $language->switchTo($locale)) {
            return back()->with('error', __('messages.unsupported_locale'));
        }

        return back()->with('success', __('messages.language_changed'));
    }
}
