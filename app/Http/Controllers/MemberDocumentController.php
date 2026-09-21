<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a member's business registration document.
 *
 * This file does NOT live on the public disk. A business registration carries
 * company numbers, director names and addresses; anything under
 * storage/app/public is served straight off the filesystem by the web server,
 * with no authentication and a guessable URL. So the document is stored on the
 * 'local' disk and streamed through this route, which sits behind auth.
 *
 * Company logos and product images are public — those are meant to be seen.
 */
class MemberDocumentController extends Controller
{
    public function registration(Request $request, Member $member): StreamedResponse
    {
        abort_if(blank($member->registration_document_path), 404, 'No registration document on file.');

        $disk = Storage::disk('local');

        abort_unless($disk->exists($member->registration_document_path), 404, 'The file is recorded but missing from storage.');

        // inline so it previews in the browser rather than forcing a download.
        return $disk->response(
            $member->registration_document_path,
            $this->downloadName($member),
            ['Content-Disposition' => 'inline; filename="' . $this->downloadName($member) . '"'],
        );
    }

    private function downloadName(Member $member): string
    {
        $extension = pathinfo($member->registration_document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $slug = str($member->company_name)->slug()->value() ?: 'member';

        return "{$slug}-registration.{$extension}";
    }
}
