<?php

namespace App\Console\Commands;

use App\Services\ArchiveService;
use Illuminate\Console\Command;

class SyncArchivesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archives:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi otomatis seluruh surat keluar berstatus signed dan seluruh surat masuk ke tabel arsip';

    /**
     * Execute the console command.
     */
    public function handle(ArchiveService $archiveService): int
    {
        $this->info('Memulai sinkronisasi otomatis surat keluar & surat masuk ke Pusat Arsip...');

        $result = $archiveService->syncAllExisting();

        $this->info("Berhasil menyinkronkan {$result['outgoing']} surat keluar dan {$result['incoming']} surat masuk ke Pusat Arsip.");

        return Command::SUCCESS;
    }
}

