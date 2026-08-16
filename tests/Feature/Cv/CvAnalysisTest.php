<?php

use App\Models\Company;
use App\Models\CvDocument;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\CvTextExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['services.gemini.api_key' => '']);
});

function makeDocx(string $text): string
{
    $path = tempnam(sys_get_temp_dir(), 'cv').'.docx';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.htmlspecialchars($text).'</w:t></w:r></w:p></w:body></w:document>');
    $zip->close();

    return $path;
}

test('job seekers can upload a cv and see extracted skills', function () {
    Storage::fake('local');
    Storage::fake((string) config('filesystems.cv', 'local'));
    $user = User::factory()->jobSeeker()->create();
    $docx = makeDocx('Alex Rivera. PHP Laravel Vue developer with 4 years experience. alex@example.com');

    $this->actingAs($user)
        ->post(route('cv-review.store'), [
            'cv' => new UploadedFile($docx, 'resume.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
        ])
        ->assertRedirect(route('cv-review'));

    $document = CvDocument::where('user_id', $user->id)->first();

    expect($document)->not->toBeNull()
        ->and($document->status)->toBe('processed')
        ->and($document->parsed_text)->toContain('Alex Rivera')
        ->and($document->extraction['skills'] ?? [])->toContain('php');
});

test('hr can filter candidates by extracted skills', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $match = User::factory()->jobSeeker()->create(['name' => 'Skill Match']);
    $other = User::factory()->jobSeeker()->create(['name' => 'Other Person']);
    CvDocument::factory()->create(['user_id' => $match->id]);
    CvDocument::factory()->create([
        'user_id' => $other->id,
        'extraction' => [
            'skills' => ['Python'],
            'experience_level' => 'entry',
        ],
        'parsed_text' => 'Python developer',
    ]);

    $this->actingAs($hr)
        ->get(route('filter-candidates', ['skills' => 'Laravel']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/FilterCandidates')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.name', 'Skill Match')
        );
});

test('applications attach the latest processed cv', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create(['user_id' => $hr->id, 'company_id' => $company->id, 'status' => 'active']);
    $seeker = User::factory()->jobSeeker()->create();
    $cv = CvDocument::factory()->create(['user_id' => $seeker->id]);

    $this->actingAs($seeker)
        ->post(route('job-applications.store'), [
            'job_id' => $job->id,
            'cover_letter' => 'I would like this role.',
        ])
        ->assertRedirect(route('browse-jobs'));

    expect(JobApplication::first()->cv_document_id)->toBe($cv->id);
});

test('ats scoring requires a processed cv', function () {
    $user = User::factory()->jobSeeker()->create();
    subscribeToPlan($user, [
        'mock_interviews_per_month' => null,
        'cv_documents' => null,
        'ats' => true,
    ], [
        'name' => 'professional',
        'display_name' => 'Premium Plan',
        'amount' => 19.99,
        'target_role' => 'job_seeker',
    ]);

    $this->actingAs($user)
        ->post(route('ats-scoring.store'), [
            'job_description' => str_repeat('Looking for a Laravel engineer. ', 5),
        ])
        ->assertRedirect(route('ats-scoring'))
        ->assertSessionHasErrors('cv');
});

test('ats scoring stores a compatibility result', function () {
    $user = User::factory()->jobSeeker()->create();
    subscribeToPlan($user, [
        'mock_interviews_per_month' => null,
        'cv_documents' => null,
        'ats' => true,
    ], [
        'name' => 'professional',
        'display_name' => 'Premium Plan',
        'amount' => 19.99,
        'target_role' => 'job_seeker',
    ]);
    CvDocument::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('ats-scoring.store'), [
            'job_description' => str_repeat('We need PHP Laravel Vue Docker experience. ', 4),
        ])
        ->assertRedirect(route('ats-scoring'));

    expect($user->atsAnalyses()->count())->toBe(1)
        ->and($user->atsAnalyses()->first()->score)->toBeInt();
});

test('cv text extractor reads docx content', function () {
    $path = makeDocx('Extracted resume text about PostgreSQL');

    expect(app(CvTextExtractor::class)->extract($path, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
        ->toContain('PostgreSQL');

    @unlink($path);
});
