<?php

use App\Livewire\Portal\Documents;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function portalDocsMember(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Portal Co',
        'email' => 'portal' . uniqid() . '@test.invalid',
        'join_date' => now()->subYear()->toDateString(),
    ], $attrs));
}

it('uploads a registration document and records when', function () {
    Storage::fake('local');
    $member = portalDocsMember();
    $member->setPortalPassword('secret-pass');
    $this->actingAs($member, 'member');

    Livewire::test(Documents::class)
        ->set('registrationDocument', UploadedFile::fake()->create('registration.pdf', 200, 'application/pdf'))
        ->call('saveDocument')
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->registration_document_path)->not->toBeNull()
        ->and($member->registration_document_updated_at)->not->toBeNull();

    Storage::disk('local')->assertExists($member->registration_document_path);
});

it('serves only the signed-in member\'s own document', function () {
    Storage::fake('local');
    $member = portalDocsMember();
    $member->setPortalPassword('secret-pass');
    $member->recordRegistrationDocument(
        UploadedFile::fake()->create('reg.pdf', 100)->store('registrations', 'local')
    );
    $this->actingAs($member, 'member');

    $this->get(route('portal.documents.registration'))->assertOk();
});

it('404s when there is no document on file', function () {
    $member = portalDocsMember();
    $member->setPortalPassword('secret-pass');
    $this->actingAs($member, 'member');

    $this->get(route('portal.documents.registration'))->assertNotFound();
});
