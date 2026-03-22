<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        if ($sessionId === $request->session()->getId()) {
            return back()->with('error', 'You cannot terminate your current session from this action.');
        }

        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'The selected session was not found.');
        }

        return back()->with('success', 'Session terminated successfully.');
    }

    public function destroyOther(Request $request): RedirectResponse
    {
        $deleted = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        if ($deleted === 0) {
            return back()->with('info', 'No other active sessions found.');
        }

        return back()->with('success', "{$deleted} session(s) terminated.");
    }
}
