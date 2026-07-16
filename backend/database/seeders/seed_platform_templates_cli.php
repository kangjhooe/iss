<?php
/**
 * Seed platform templates without full Laravel (PHP 8.0+).
 * Usage: php database/seeders/seed_platform_templates_cli.php
 */

$root = dirname(__DIR__, 2);
$envFile = $root . '/.env';
$env = [];
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$db = $env['DB_DATABASE'] ?? 'iss_db';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';
$port = (int) ($env['DB_PORT'] ?? 3306);

$mysqli = new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$hasSubjectType = false;
$colRes = $mysqli->query("SHOW COLUMNS FROM template_surat LIKE 'subject_type'");
if ($colRes && $colRes->num_rows > 0) {
    $hasSubjectType = true;
}

$templates = require __DIR__ . '/platform_templates_data.php';

$upsert = $mysqli->prepare(
    'SELECT id FROM template_surat WHERE institution_id IS NULL AND kode = ? LIMIT 1'
);

if ($hasSubjectType) {
    $insert = $mysqli->prepare(
        'INSERT INTO template_surat (institution_id, kode, nama, isi_html, status, letter_type_code, subject_type, created_at, updated_at)
         VALUES (NULL, ?, ?, ?, \'aktif\', ?, ?, NOW(), NOW())'
    );
    $update = $mysqli->prepare(
        'UPDATE template_surat SET nama=?, isi_html=?, status=\'aktif\', letter_type_code=?, subject_type=?, updated_at=NOW() WHERE id=?'
    );
} else {
    $insert = $mysqli->prepare(
        'INSERT INTO template_surat (institution_id, kode, nama, isi_html, status, letter_type_code, created_at, updated_at)
         VALUES (NULL, ?, ?, ?, \'aktif\', ?, NOW(), NOW())'
    );
    $update = $mysqli->prepare(
        'UPDATE template_surat SET nama=?, isi_html=?, status=\'aktif\', letter_type_code=?, updated_at=NOW() WHERE id=?'
    );
}

$count = 0;
foreach ($templates as $tpl) {
    $kode = $tpl['kode'];
    $nama = $tpl['nama'];
    $html = $tpl['isi_html'];
    $ltc = $tpl['letter_type_code'];
    $subject = $tpl['subject_type'] ?? 'siswa';

    $upsert->bind_param('s', $kode);
    $upsert->execute();
    $res = $upsert->get_result();
    $row = $res ? $res->fetch_assoc() : null;

    if ($row) {
        $id = (int) $row['id'];
        if ($hasSubjectType) {
            $update->bind_param('ssssi', $nama, $html, $ltc, $subject, $id);
        } else {
            $update->bind_param('sssi', $nama, $html, $ltc, $id);
        }
        if (!$update->execute()) {
            fwrite(STDERR, "Update failed {$kode}: {$update->error}\n");
            exit(1);
        }
    } else {
        if ($hasSubjectType) {
            $insert->bind_param('sssss', $kode, $nama, $html, $ltc, $subject);
        } else {
            $insert->bind_param('ssss', $kode, $nama, $html, $ltc);
        }
        if (!$insert->execute()) {
            fwrite(STDERR, "Insert failed {$kode}: {$insert->error}\n");
            exit(1);
        }
    }
    $count++;
    echo "OK {$kode} — {$nama}\n";
}

echo "\nDone: {$count} platform templates.\n\n";

$list = $mysqli->query(
    $hasSubjectType
        ? 'SELECT kode, nama, letter_type_code, subject_type FROM template_surat WHERE institution_id IS NULL ORDER BY kode'
        : 'SELECT kode, nama, letter_type_code FROM template_surat WHERE institution_id IS NULL ORDER BY kode'
);
while ($r = $list->fetch_assoc()) {
    $sub = isset($r['subject_type']) ? "\t{$r['subject_type']}" : '';
    echo "{$r['kode']}\t{$r['letter_type_code']}{$sub}\t{$r['nama']}\n";
}
