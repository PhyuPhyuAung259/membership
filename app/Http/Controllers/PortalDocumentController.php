<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the SIGNED-IN member's own registration document — never takes a
 * member id, unlike the staff equivalent (MemberDocumentController), so
 * there is no id to guess to read another company's paperwork.
 */
class PortalDocumentController extends Controller
{
    public function registration(Request $request): StreamedResponse
    {
        $member = Auth::guard('member')->user();

        abort_if(blank($member->registration_document_path), 404, 'No registration document on file.');

        $disk = Storage::disk('local');

        abort_unless($disk->exists($member->registration_document_path), 404, 'The file is recorded but missing from storage.');

        $extension = pathinfo($member->registration_document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $name = str($member->company_name)->slug()->value() ?: 'member';
        $filename = "{$name}-registration.{$extension}";

        return $disk->response(
            $member->registration_document_path,
            $filename,
            ['Content-Disposition' => "inline; filename=\"{$filename}\""],
        );
    }
}
