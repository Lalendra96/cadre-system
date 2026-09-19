<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:en,si,ta'],
        ]);

        $request->session()->put('locale', $data['locale']);

        if ($request->user()) {
            $request->user()->update([
                'locale' => $data['locale'],
            ]);
        }

        return back()->with(
            'success',
            __('ui.language_changed')
        );
    }
}
