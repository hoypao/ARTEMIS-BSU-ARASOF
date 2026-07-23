<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Ports of ask_spartan.php (standalone assistant page) and
 * actions/misc/chat_action.php (assistant JSON endpoint — read-only,
 * personalizes replies for logged-in students).
 */
class SpartanController extends Controller
{
    /** ask_spartan.php */
    public function page()
    {
        $pageTitle = 'Ask Spartan';

        return view('pages.ask_spartan', compact('pageTitle'));
    }

    /** actions/misc/chat_action.php (JSON) */
    public function chat(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $message = trim((string) ($input['message'] ?? ''));

        if ($message === '') {
            return response()->json(['error' => 'Message is required.'], 400);
        }

        // Personalizes replies (e.g. "track my application") with the logged-in
        // student's own records when a session exists; falls back to generic
        // guidance for anonymous visitors.
        $user = current_user();
        $pdo = $user !== null ? getDB() : null;

        return response()->json([
            'reply' => get_spartan_response($message, $user, $pdo),
            'time'  => date('H:i'),
        ]);
    }
}
