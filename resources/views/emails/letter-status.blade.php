<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 24px 12px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;" cellspacing="0" cellpadding="0">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); padding: 28px 24px; text-align: center; border-bottom: 4px solid #38bdf8;">
                            <div style="font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #93c5fd; margin-bottom: 4px;">
                                UNIVERSITAS SUBANG
                            </div>
                            <h1 style="margin: 0; font-size: 18px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">
                                FAKULTAS ILMU KOMPUTER
                            </h1>
                            <div style="font-size: 12px; color: #cbd5e1; margin-top: 4px;">
                                Sistem Informasi Layanan Akademik & TTE (SILAS-TTE)
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 28px;">
                            
                            <!-- Status Badge -->
                            <div style="margin-bottom: 20px;">
                                @if($category === 'letter_signed')
                                    <span style="display: inline-block; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        &#10003; SELESAI &amp; TTE SAH
                                    </span>
                                @elseif($category === 'letter_rejected')
                                    <span style="display: inline-block; background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        &#10007; PERLU PERBAIKAN
                                    </span>
                                @else
                                    <span style="display: inline-block; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        &#9203; DALAM PROSES
                                    </span>
                                @endif
                            </div>

                            <!-- Greeting -->
                            <h2 style="margin: 0 0 12px 0; font-size: 17px; font-weight: 700; color: #0f172a;">
                                Yth. {{ $notifiable->name }},
                            </h2>

                            <p style="margin: 0 0 20px 0; font-size: 14px; color: #334155; line-height: 1.6;">
                                {{ $messageText }}
                            </p>

                            <!-- Rejection Reason Highlight Box (if rejected) -->
                            @if($category === 'letter_rejected' && !empty($rejectionReason))
                                <div style="background-color: #fff1f2; border-left: 4px solid #e11d48; padding: 14px 16px; border-radius: 6px; margin-bottom: 24px;">
                                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #9f1239; margin-bottom: 4px;">
                                        Catatan / Alasan Penolakan:
                                    </div>
                                    <div style="font-size: 14px; color: #881337; font-style: italic;">
                                        &ldquo;{{ $rejectionReason }}&rdquo;
                                    </div>
                                    <div style="font-size: 12px; color: #be123c; margin-top: 6px;">
                                        Silakan perbaiki data formulir pengajuan Anda melalui tautan di bawah ini.
                                    </div>
                                </div>
                            @endif

                            <!-- Summary Data Table (if letter object is provided) -->
                            @if($letter)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 24px; font-size: 13px;">
                                    <tr>
                                        <td style="padding: 10px 14px; color: #64748b; font-weight: 600; width: 35%; border-bottom: 1px solid #e2e8f0;">Jenis Surat</td>
                                        <td style="padding: 10px 14px; color: #0f172a; font-weight: 700; border-bottom: 1px solid #e2e8f0;">{{ $letter->letterType?->name ?? 'Surat Akademik' }}</td>
                                    </tr>
                                    @if($letter->letter_number)
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Nomor Surat</td>
                                            <td style="padding: 10px 14px; color: #1e1b4b; font-weight: 700; font-family: monospace; border-bottom: 1px solid #e2e8f0;">{{ $letter->letter_number }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 10px 14px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Nama / NPM</td>
                                        <td style="padding: 10px 14px; color: #0f172a; border-bottom: 1px solid #e2e8f0;">{{ $letter->student_name }} ({{ $letter->npm }})</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Program Studi</td>
                                        <td style="padding: 10px 14px; color: #0f172a; border-bottom: 1px solid #e2e8f0;">{{ $letter->study_program }}</td>
                                    </tr>
                                    @if($letter->signer_name)
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">Penandatangan</td>
                                            <td style="padding: 10px 14px; color: #0f172a; border-bottom: 1px solid #e2e8f0;">{{ $letter->signer_name }} ({{ $letter->signer_title }})</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 10px 14px; color: #64748b; font-weight: 600;">Status Terakhir</td>
                                        <td style="padding: 10px 14px; color: #0f172a; font-weight: 700;">{{ ucfirst($letter->status) }}</td>
                                    </tr>
                                </table>
                            @endif

                            <!-- Attachment Banner (if file attached) -->
                            @if($hasAttachment)
                                <div style="background-color: #f0fdf4; border: 1px dashed #86efac; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; font-size: 13px; color: #166534;">
                                    <strong>&#128206; Lampiran Berkas PDF Resmi:</strong> File PDF surat yang telah ditandatangani secara elektronik (TTE) telah dilampirkan langsung pada email ini. Anda dapat mengunduh dan mencetaknya langsung.
                                </div>
                            @endif

                            <!-- Call to Action Button -->
                            @if($actionUrl)
                                <div style="text-align: center; margin: 28px 0 20px 0;">
                                    <a href="{{ $actionUrl }}" target="_blank" style="display: inline-block; background-color: #4338ca; color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 8px; font-size: 14px; font-weight: 700; letter-spacing: 0.3px; box-shadow: 0 2px 4px rgba(67, 56, 202, 0.2);">
                                        {{ $actionText }} &rarr;
                                    </a>
                                </div>
                            @endif

                            <!-- Verification Security Note -->
                            @if($category === 'letter_signed')
                                <div style="border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 24px; font-size: 12px; color: #64748b; line-height: 1.5;">
                                    <strong>Keabsahan TTE &amp; QR Code:</strong> Dokumen ini telah ditandatangani secara elektronik berdasar UU ITE dan dilengkapi kode QR verifikasi keaslian. Pihak penerima dokumen dapat memverifikasi keabsahan surat kapan pun melalui portal verifikasi SILAS-TTE Universitas Subang.
                                </div>
                            @endif

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
                            <div style="font-weight: 700; color: #334155; margin-bottom: 4px;">
                                Fakultas Ilmu Komputer &bull; Universitas Subang
                            </div>
                            <div style="margin-bottom: 8px;">
                                Jl. Raden Ajeng Kartini No. 2, Nyimplung, Kec. Subang, Kabupaten Subang, Jawa Barat 41211
                            </div>
                            <div style="font-size: 11px; color: #94a3b8;">
                                Email ini dikirim secara otomatis oleh Sistem SILAS-TTE. Mohon untuk tidak membalas email ini secara langsung.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

