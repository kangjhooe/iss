<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class DatabaseBackupService
{
    private const FILENAME_PATTERN = '/^iss-db-\d{8}-\d{6}\.sql(?:\.gz)?$/';

    private const MAX_BACKUPS = 10;

    public function directory(): string
    {
        $dir = storage_path('app/private/backups/database');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0750, true);
        }

        return $dir;
    }

    /**
     * Create a MySQL dump and store as .sql.gz.
     *
     * @return array{filename: string, size: int, created_at: string, path: string}
     */
    public function create(): array
    {
        $connection = config('database.default');
        if ($connection !== 'mysql' && config("database.connections.{$connection}.driver") !== 'mysql') {
            throw new RuntimeException('Backup database hanya didukung untuk koneksi MySQL/MariaDB.');
        }

        $cfg = config('database.connections.mysql');
        $database = $cfg['database'] ?? null;
        if (! $database) {
            throw new RuntimeException('Nama database tidak dikonfigurasi.');
        }

        $dir = $this->directory();
        $basename = 'iss-db-' . now()->format('Ymd-His');
        $sqlPath = $dir . DIRECTORY_SEPARATOR . $basename . '.sql';
        $gzPath = $dir . DIRECTORY_SEPARATOR . $basename . '.sql.gz';

        $mysqldump = $this->resolveMysqldumpBinary();
        $defaultsFile = $this->writeDefaultsExtraFile($cfg);

        try {
            $result = Process::timeout(600)->run([
                $mysqldump,
                '--defaults-extra-file=' . $defaultsFile,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--hex-blob',
                '--default-character-set=utf8mb4',
                '--result-file=' . $sqlPath,
                $database,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException(
                    'mysqldump gagal: ' . trim($result->errorOutput() ?: $result->output() ?: 'unknown error')
                );
            }

            if (! File::exists($sqlPath) || File::size($sqlPath) === 0) {
                throw new RuntimeException('File dump kosong atau tidak dibuat.');
            }

            $this->gzipFile($sqlPath, $gzPath);
            File::delete($sqlPath);
        } finally {
            if (is_file($defaultsFile)) {
                @unlink($defaultsFile);
            }
            if (File::exists($sqlPath)) {
                File::delete($sqlPath);
            }
        }

        $this->pruneOldBackups();

        return $this->metaFor($basename . '.sql.gz');
    }

    /**
     * @return list<array{filename: string, size: int, created_at: string}>
     */
    public function list(): array
    {
        $dir = $this->directory();
        $files = collect(File::files($dir))
            ->filter(fn ($file) => preg_match(self::FILENAME_PATTERN, $file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        return $files->map(function ($file) {
            return [
                'filename' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => date('c', $file->getMTime()),
            ];
        })->all();
    }

    public function absolutePath(string $filename): string
    {
        $this->assertSafeFilename($filename);
        $path = $this->directory() . DIRECTORY_SEPARATOR . $filename;
        if (! File::exists($path)) {
            throw new RuntimeException('File backup tidak ditemukan.');
        }

        return $path;
    }

    public function delete(string $filename): void
    {
        $path = $this->absolutePath($filename);
        File::delete($path);
    }

    /**
     * @return array{filename: string, size: int, created_at: string, path: string}
     */
    private function metaFor(string $filename): array
    {
        $path = $this->absolutePath($filename);

        return [
            'filename' => $filename,
            'size' => File::size($path),
            'created_at' => date('c', File::lastModified($path)),
            'path' => $path,
        ];
    }

    private function assertSafeFilename(string $filename): void
    {
        if (! preg_match(self::FILENAME_PATTERN, $filename)) {
            throw new RuntimeException('Nama file backup tidak valid.');
        }
    }

    private function pruneOldBackups(): void
    {
        $files = collect(File::files($this->directory()))
            ->filter(fn ($file) => preg_match(self::FILENAME_PATTERN, $file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        foreach ($files->slice(self::MAX_BACKUPS) as $file) {
            File::delete($file->getPathname());
        }
    }

    private function resolveMysqldumpBinary(): string
    {
        $configured = config('database.mysqldump_path');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            'mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
        ];

        foreach ($candidates as $bin) {
            if ($bin === 'mysqldump') {
                $which = Process::run([PHP_OS_FAMILY === 'Windows' ? 'where' : 'which', 'mysqldump']);
                if ($which->successful()) {
                    $line = trim(explode("\n", str_replace("\r", '', $which->output()))[0] ?? '');
                    if ($line !== '' && is_file($line)) {
                        return $line;
                    }
                }
                continue;
            }

            if (is_file($bin)) {
                return $bin;
            }
        }

        throw new RuntimeException(
            'Binary mysqldump tidak ditemukan. Set MYSQLDUMP_PATH di .env ke path lengkap mysqldump.'
        );
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function writeDefaultsExtraFile(array $cfg): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'iss_my_');
        if ($tmp === false) {
            throw new RuntimeException('Gagal membuat file konfigurasi sementara untuk mysqldump.');
        }

        $host = $cfg['host'] ?? '127.0.0.1';
        $port = (string) ($cfg['port'] ?? '3306');
        $user = $cfg['username'] ?? 'root';
        $password = (string) ($cfg['password'] ?? '');

        $content = "[client]\n"
            . 'host=' . $host . "\n"
            . 'port=' . $port . "\n"
            . 'user=' . $user . "\n"
            . 'password="' . addcslashes($password, "\\\"\n\r") . "\"\n";

        if (! empty($cfg['unix_socket'])) {
            $content .= 'socket=' . $cfg['unix_socket'] . "\n";
        }

        file_put_contents($tmp, $content);

        return $tmp;
    }

    private function gzipFile(string $source, string $destination): void
    {
        $in = fopen($source, 'rb');
        if ($in === false) {
            throw new RuntimeException('Gagal membaca file dump untuk kompresi.');
        }

        $out = gzopen($destination, 'wb9');
        if ($out === false) {
            fclose($in);
            throw new RuntimeException('Gagal membuat file .sql.gz.');
        }

        while (! feof($in)) {
            $chunk = fread($in, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
    }
}
