<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    /**
     * Show a confirmation page rather than unsubscribing on the GET.
     *
     * Mail clients and corporate security scanners prefetch links in email.
     * If the GET changed the record, those prefetches would unsubscribe
     * members who never clicked anything, and you would never find out.
     */
    public function show(Request $request, Member $member): View
    {
        return view('unsubscribe', [
            'member' => $member,
            'alreadyDone' => $member->unsubscribed_at !== null,
            // Carries the signature through to the POST, so the form cannot
            // be used to unsubscribe an arbitrary member id.
            'confirmUrl' => route('unsubscribe.store', ['member' => $member->id])
                . '?' . http_build_query($request->query()),
        ]);
    }

    public function store(Request $request, Member $member): View
    {
        $member->update([
            'marketing_opt_in' => false,
            'unsubscribed_at' => $member->unsubscribed_at ?? now(),
        ]);

        return view('unsubscribe', [
            'member' => $member,
            'done' => true,
            'alreadyDone' => true,
            'confirmUrl' => null,
        ]);
    }

    public function test(): View
    {
        return view('unsubscribe', [
            'member' => null,
            'isTest' => true,
            'alreadyDone' => false,
            'confirmUrl' => null,
        ]);
    }
}
