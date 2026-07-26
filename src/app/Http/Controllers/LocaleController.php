<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /**
     * ロケールを切り替える。
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:ja,en'],
        ]);

        Cookie::queue('locale', $validated['locale'], 60 * 24 * 365);

        return redirect()->back();
    }
}
