<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Models\Member;
use App\Models\MemberType;
use Illuminate\Http\Request;
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
    /**
     * The browsable front door: search and filter across every listed
     * company, so an outside agent can find a member without already
     * knowing its direct URL.
     */
    public function index(Request $request): View
    {
        $members = Member::query()
            ->whereNotIn('status', ['cancelled', 'pending'])
            ->with(['businessType', 'memberType'])
            ->search($request->string('q')->value())
            ->when($request->filled('business_type'), function ($q) use ($request) {
                $q->where('business_type_id', $request->integer('business_type'));
            })
            ->when($request->filled('member_type'), function ($q) use ($request) {
                $q->where('member_type_id', $request->integer('member_type'));
            })
            ->orderBy('company_name')
            ->paginate(24)
            ->withQueryString();

        return view('directory.index', [
            'members' => $members,
            'businessTypes' => BusinessType::alphabetical()->get(),
            'memberTypes' => MemberType::ranked()->get(),
            'filters' => $request->only(['q', 'business_type', 'member_type']),
        ]);
    }

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
