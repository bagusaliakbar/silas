<?php

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use App\Notifications\SystemActivityNotification;
use App\Services\AcademicLetterService;
use Database\Seeders\LetterTypeSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
});

test('authenticated user can view their notifications via API', function () {
    $user = User::factory()->create();

    $user->notify(new SystemActivityNotification(
        title: 'Uji Notifikasi',
        message: 'Ini adalah pesan uji notifikasi.',
        url: '/test',
        category: 'info'
    ));

    $response = $this->actingAs($user)->getJson(route('notifications.index'));

    $response->assertOk()
        ->assertJsonStructure([
            'notifications',
            'unread_count',
        ])
        ->assertJson([
            'unread_count' => 1,
        ]);
});

test('user can mark a single notification as read', function () {
    $user = User::factory()->create();

    $user->notify(new SystemActivityNotification(
        title: 'Notifikasi Baru',
        message: 'Silakan periksa berkas Anda.',
        url: '/test',
        category: 'letter_submitted'
    ));

    $notification = $user->notifications()->first();
    expect($notification->read_at)->toBeNull();

    $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));
    $response->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();

    $user->notify(new SystemActivityNotification('Notif 1', 'Pesan 1'));
    $user->notify(new SystemActivityNotification('Notif 2', 'Pesan 2'));

    expect($user->unreadNotifications()->count())->toBe(2);

    $response = $this->actingAs($user)->post(route('notifications.read-all'));
    $response->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('submitting a letter creates notifications for student and officials', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->mahasiswa()->create();
    $letterType = LetterType::first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Budi Santoso',
        'npm' => 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'purpose' => 'Keperluan Beasiswa',
        'signer_id' => 'kaprodi',
    ];

    $response = $this->actingAs($student)->post(route('mahasiswa.letters.store'), $payload);
    $response->assertRedirect(route('mahasiswa.letters.index'));

    // Check student received notification
    expect($student->fresh()->notifications()->count())->toBeGreaterThan(0);
    $studentNotif = $student->fresh()->notifications()->first();
    expect($studentNotif->data['title'])->toBe('Pengajuan Surat Berhasil');

    // Check admin received notification
    expect($admin->fresh()->notifications()->count())->toBeGreaterThan(0);
    $adminNotif = $admin->fresh()->notifications()->first();
    expect($adminNotif->data['title'])->toBe('Permohonan Surat Baru');
});

test('signing a letter notifies the applicant with TTE completion', function () {
    $student = User::factory()->mahasiswa()->create();
    $official = User::factory()->create(['role' => 'pejabat']);
    $letterType = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $letterType->id,
        'student_name' => $student->name,
        'npm' => $student->npm ?? 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
        'signer_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'signer_title' => 'Ka. Program Studi',
    ]);

    app(AcademicLetterService::class)->generatePdf($letter);

    $response = $this->actingAs($official)->post(route('letters.sign', $letter->uuid));
    $response->assertRedirect();

    // Student should be notified of TTE completion
    $studentNotif = $student->fresh()->notifications()->where('data->category', 'letter_signed')->first();
    expect($studentNotif)->not->toBeNull();
    expect($studentNotif->data['title'])->toContain('Surat Selesai Ditandatangani');

    // Signer official should be notified
    $officialNotif = $official->fresh()->notifications()->where('data->category', 'letter_signed')->first();
    expect($officialNotif)->not->toBeNull();
});

test('rejecting a letter notifies the applicant with reason', function () {
    $student = User::factory()->mahasiswa()->create();
    $official = User::factory()->create(['role' => 'pejabat']);
    $letterType = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $letterType->id,
        'student_name' => $student->name,
        'npm' => $student->npm ?? 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
        'signer_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'signer_title' => 'Ka. Program Studi',
    ]);

    $response = $this->actingAs($official)->post(route('letters.reject', $letter->uuid), [
        'rejection_reason' => 'KRS semester berjalan belum dilampirkan.',
    ]);
    $response->assertRedirect();

    $studentNotif = $student->fresh()->notifications()->where('data->category', 'letter_rejected')->first();
    expect($studentNotif)->not->toBeNull();
    expect($studentNotif->data['message'])->toContain('KRS semester berjalan belum dilampirkan');
});

test('signing a letter sends email notification to student with signed PDF attachment', function () {
    Notification::fake();

    $student = User::factory()->mahasiswa()->create(['email' => 'mahasiswa@unsub.ac.id']);
    $official = User::factory()->create(['role' => 'pejabat']);
    $letterType = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $letterType->id,
        'student_name' => $student->name,
        'npm' => 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
        'signer_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'signer_title' => 'Ka. Program Studi',
    ]);

    app(AcademicLetterService::class)->generatePdf($letter);

    $this->actingAs($official)->post(route('letters.sign', $letter->uuid))->assertRedirect();

    Notification::assertSentTo(
        $student,
        SystemActivityNotification::class,
        function (SystemActivityNotification $notification) use ($student) {
            $channels = $notification->via($student);
            expect($channels)->toContain('mail');
            expect($channels)->toContain('database');
            expect($notification->category)->toBe('letter_signed');

            $mail = $notification->toMail($student);
            expect($mail->subject)->toContain('[SILAS-TTE] Surat Selesai Ditandatangani');

            return true;
        }
    );
});

test('submitting a letter sends email confirmation to applicant', function () {
    Notification::fake();

    $student = User::factory()->mahasiswa()->create(['email' => 'budi@unsub.ac.id']);
    $letterType = LetterType::first();

    $this->actingAs($student)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => $student->name,
        'npm' => 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'purpose' => 'Pengajuan Beasiswa',
    ])->assertRedirect();

    Notification::assertSentTo(
        $student,
        SystemActivityNotification::class,
        function (SystemActivityNotification $notification) use ($student) {
            $channels = $notification->via($student);
            expect($channels)->toContain('mail');
            expect($notification->category)->toBe('letter_submitted');

            $mail = $notification->toMail($student);
            expect($mail->subject)->toContain('[SILAS-TTE] Pengajuan Surat Berhasil');

            return true;
        }
    );
});

test('rejecting a letter sends email notification with rejection reason to student', function () {
    Notification::fake();

    $student = User::factory()->mahasiswa()->create(['email' => 'citra@unsub.ac.id']);
    $official = User::factory()->create(['role' => 'pejabat']);
    $letterType = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $letterType->id,
        'student_name' => $student->name,
        'npm' => 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $this->actingAs($official)->post(route('letters.reject', $letter->uuid), [
        'rejection_reason' => 'Scan KTP dan KTM buram.',
    ])->assertRedirect();

    Notification::assertSentTo(
        $student,
        SystemActivityNotification::class,
        function (SystemActivityNotification $notification) use ($student) {
            $channels = $notification->via($student);
            expect($channels)->toContain('mail');
            expect($notification->category)->toBe('letter_rejected');

            $mail = $notification->toMail($student);
            expect($mail->subject)->toContain('[SILAS-TTE] Permohonan Surat Ditolak');

            return true;
        }
    );
});
