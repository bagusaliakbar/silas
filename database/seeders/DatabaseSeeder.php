<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\SignatureField;
use App\Models\User;
use App\Services\CertificateService;
use FPDF;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a ready-to-test account, certificate, and document.
     */
    public function run(): void
    {
        $this->call([
            LetterTypeSeeder::class,
            SigningOfficialSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@esign.test'],
            [
                'name' => 'Bagus Ali Akbar',
                'username' => 'admin',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->update([
            'username' => 'admin',
            'role' => 'admin',
        ]);

        $mahasiswa = User::firstOrCreate(
            ['email' => 'mahasiswa@unsub.ac.id'],
            [
                'name' => 'Luna Amella Wardhani',
                'role' => 'mahasiswa',
                'npm' => 'D1A260029',
                'study_program' => 'Sistem Informasi',
                'phone' => '081234567890',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 1. Seed a Digital Certificate for the user
        $certService = app(CertificateService::class);
        $certificate = null;

        if (! $user->digitalCertificates()->exists()) {
            $certificate = $certService->generateSelfSignedCertificate($user, [
                'name' => 'Program Studi Sistem Informasi',
                'organization' => 'Universitas Subang',
                'organizational_unit' => 'Fakultas Ilmu Komputer',
                'email' => $user->email,
                'country' => 'ID',
                'days_valid' => 365,
                'passphrase' => 'secret123',
            ]);
        } else {
            $certificate = $user->digitalCertificates()->first();
        }

        // 2. Generate a sample contract PDF file
        $pdf = new FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'PERJANJIAN KERJA SAMA LAYANAN DIGITAL', 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Ln(6);
        $pdf->MultiCell(0, 6, 'Dokumen ini merupakan perjanjian resmi kerja sama digital yang dibuat pada tanggal '.now()->format('d F Y')." antara Pihak Pertama dan Pihak Kedua.\n\nKedua belah pihak telah menyepakati seluruh ketentuan dan mengesahkannya menggunakan tanda tangan elektronik yang memiliki kekuatan hukum mengikat sesuai peraturan perundang-undangan Republik Indonesia (UU ITE No. 11/2008 & PP 71/2019).\n\nIntegritas dan keabsahan dokumen ini dapat diverifikasi kapan saja melalui QR Code yang tertera.");
        $pdf->Ln(15);
        $pdfContent = $pdf->Output('S');

        $docDir = 'documents/'.$user->id;
        Storage::disk('local')->makeDirectory($docDir);
        $samplePath = $docDir.'/sample_agreement_'.time().'.pdf';
        Storage::disk('local')->put($samplePath, $pdfContent);
        $originalHash = hash('sha256', $pdfContent);

        // 3. Create Sample Document
        $document = Document::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Perjanjian Kerja Sama Layanan Digital 2026',
            'original_filename' => 'Perjanjian_Kerja_Sama_2026.pdf',
            'file_path' => $samplePath,
            'file_size' => strlen($pdfContent),
            'total_pages' => 1,
            'sha256_original' => $originalHash,
            'status' => 'draft',
            'signing_method' => 'cryptographic_pki',
            'psre_provider' => 'local',
            'verification_token' => Str::random(32),
        ]);

        // 4. Place Signature Field
        SignatureField::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'signer_name' => $user->name,
            'signer_email' => $user->email,
            'page_number' => 1,
            'pos_x' => 55,
            'pos_y' => 65,
            'width' => 38,
            'height' => 16,
            'field_type' => 'signature',
            'status' => 'pending',
        ]);

        AuditLog::record(
            $document,
            'uploaded',
            'Dokumen sampel perjanjian kerja sama berhasil diinisialisasi.',
            ['seeded' => true],
            $user
        );
    }
}
