<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Sauvegarde de la base de données SQLite du projet';

    public function handle()
    {
        $dbPath = database_path('database.sqlite');

        if (!File::exists($dbPath)) {
            $this->error('Base de données SQLite introuvable.');
            return Command::FAILURE;
        }

        $backupDir = storage_path('app/backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sqlite';

        if (File::copy($dbPath, $backupFile)) {
            $this->info("Sauvegarde réussie : {$backupFile}");
            
            activity()->log("Sauvegarde de la base de données effectuée");
            
            return Command::SUCCESS;
        }

        $this->error('Échec de la sauvegarde.');
        return Command::FAILURE;
    }
}
