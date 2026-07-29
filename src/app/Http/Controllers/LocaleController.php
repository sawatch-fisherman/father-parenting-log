<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * ロケールを切り替える。
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(Config::array('totoops.supported_locales'))],
        ]);

        Cookie::queue('locale', $validated['locale'], 60 * 24 * 365);

        return redirect()->back();
    }
}
