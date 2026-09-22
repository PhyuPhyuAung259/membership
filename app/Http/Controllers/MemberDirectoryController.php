<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\View\View;

/**
 * The public-facing side of the register: one page per company, reachable
 * without signing in.
 *
 * Deliberately narrow in what it shows. This is a directory listing, not the
 * admin detail sheet: no billing status, no monthly fee, no notes, no
 * payment or email history. Just what a company would want a prospective
 * customer to see.
 */
class MemberDirectoryController extends Controller
{
    public function show(Member $member): View
    {
        // A cancelled membership does not get a public page, and neither does
        // one still waiting on staff review — its URL 404s rather than
        // showing an unvetted self-registration to the public.
        abort_if(in_array($member->status, ['cancelled', 'pending'], true), 404);

        $member->load(['businessType', 'products']);

        return view('directory.show', ['member' => $member]);
    }
}
