<?php
/**
 * One-off migration: upload existing LOCAL execution-attachment files to MinIO,
 * preserving the exact object key so the stored file_path keeps working.
 *
 * Driven by migrate_files_to_minio.sh — all settings come from environment
 * variables (so secrets never appear on the command line):
 *
 *   MINIO_AUTOLOAD   absolute path to the project's vendor/autoload.php
 *   MINIO_ENDPOINT   e.g. https://minio.example:9000
 *   MINIO_KEY        access key
 *   MINIO_SECRET     secret key
 *   MINIO_BUCKET     bucket name (e.g. dms)
 *   MINIO_REGION     region (default us-east-1 — MinIO ignores it but the SDK needs one)
 *   MINIO_CA         path to CA/PEM bundle for HTTPS, or empty
 *   MINIO_VERIFY     "1" verify TLS (default), "0" disable (insecure)
 *   BASE_DIR         local storage base, e.g. /var/www/dms_new/storage/app/private
 *   SUBDIR           subfolder to scan, e.g. execution-attachments
 *   DRY_RUN          "1" = scan/report only, upload nothing
 *   SKIP_EXISTING    "1" = skip objects already present on MinIO (resumable)
 *   LOG_FILE         path to write a per-file log
 *   SQL_FILE         path to write the generated UPDATE statements
 *
 * The object key for each file is its path RELATIVE to BASE_DIR, e.g.
 *   /var/www/dms_new/storage/app/private/execution-attachments/12/abc.pdf
 *   -> key: execution-attachments/12/abc.pdf
 * which is exactly what is stored in execution_attachments.file_path.
 */

function env_req(string $k): string {
    $v = getenv($k);
    if ($v === false || $v === '') { fwrite(STDERR, "Missing required env: $k\n"); exit(2); }
    return $v;
}
function env_opt(string $k, string $default = ''): string {
    $v = getenv($k);
    return ($v === false) ? $default : $v;
}

$autoload = env_req('MINIO_AUTOLOAD');
if (!is_file($autoload)) { fwrite(STDERR, "autoload not found: $autoload\n"); exit(2); }
require $autoload;

if (!class_exists('Aws\\S3\\S3Client')) {
    fwrite(STDERR, "Aws\\S3\\S3Client not found — is the AWS SDK deployed in this project's vendor/?\n");
    exit(2);
}

$endpoint = env_req('MINIO_ENDPOINT');
$key      = env_req('MINIO_KEY');
$secret   = env_req('MINIO_SECRET');
$bucket   = env_req('MINIO_BUCKET');
$region   = env_opt('MINIO_REGION', 'us-east-1');
$ca       = env_opt('MINIO_CA', '');
$verifyOn = env_opt('MINIO_VERIFY', '1') !== '0';
$baseDir  = rtrim(env_req('BASE_DIR'), '/');
$subdir   = trim(env_opt('SUBDIR', 'execution-attachments'), '/');
$dryRun   = env_opt('DRY_RUN', '0') === '1';
$skipEx   = env_opt('SKIP_EXISTING', '1') === '1';
$logFile  = env_opt('LOG_FILE', '');
$sqlFile  = env_opt('SQL_FILE', '');

$scanDir = $baseDir . '/' . $subdir;
if (!is_dir($scanDir)) {
    fwrite(STDERR, "Scan directory does not exist: $scanDir\n");
    exit(2);
}

// TLS verify: true | false | path-to-CA-bundle
$verify = $verifyOn ? true : false;
if ($verifyOn && $ca !== '') {
    if (!is_file($ca)) { fwrite(STDERR, "CA bundle not found: $ca\n"); exit(2); }
    $verify = $ca;
}

$client = new Aws\S3\S3Client([
    'version'                 => 'latest',
    'region'                  => $region,
    'endpoint'                => $endpoint,
    'use_path_style_endpoint' => true,
    'credentials'             => ['key' => $key, 'secret' => $secret],
    'http'                    => ['verify' => $verify],
]);

$logfh = $logFile ? fopen($logFile, 'a') : null;
$log = function (string $line) use ($logfh) {
    $stamp = date('Y-m-d H:i:s') . ' ' . $line;
    echo $stamp . "\n";
    if ($logfh) { fwrite($logfh, $stamp . "\n"); }
};

$log("=== MinIO migration start ===");
$log("endpoint=$endpoint bucket=$bucket scan=$scanDir dry_run=" . ($dryRun ? 'yes' : 'no') . " skip_existing=" . ($skipEx ? 'yes' : 'no') . " verify=" . (is_string($verify) ? $verify : ($verify ? 'true' : 'false')));

// Sanity: bucket reachable?
try {
    if (!$client->doesBucketExist($bucket)) {
        fwrite(STDERR, "Bucket '$bucket' not found or not accessible.\n");
        exit(3);
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "Cannot reach MinIO: " . $e->getMessage() . "\n");
    exit(3);
}

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scanDir, FilesystemIterator::SKIP_DOTS)
);

$total = 0; $uploaded = 0; $skipped = 0; $failed = 0;
$migratedKeys = [];

foreach ($it as $fileInfo) {
    if (!$fileInfo->isFile()) continue;
    $abs = $fileInfo->getPathname();
    // key = path relative to BASE_DIR, normalised to forward slashes
    $key = str_replace('\\', '/', ltrim(substr($abs, strlen($baseDir) + 1), '/'));
    if ($key === '') continue;
    $total++;

    try {
        if ($skipEx && $client->doesObjectExist($bucket, $key)) {
            $skipped++;
            $migratedKeys[] = $key;            // already on MinIO -> safe to flip in DB
            $log("SKIP (exists)  $key");
            continue;
        }
        if ($dryRun) {
            $log("WOULD UPLOAD   $key");
            continue;
        }
        $args = [
            'Bucket'     => $bucket,
            'Key'        => $key,
            'SourceFile' => $abs,
        ];
        $ctype = @mime_content_type($abs);
        if ($ctype) { $args['ContentType'] = $ctype; }

        $client->putObject($args);
        $uploaded++;
        $migratedKeys[] = $key;
        $log("UPLOADED       $key");
    } catch (\Throwable $e) {
        $failed++;
        $log("FAILED         $key  :: " . $e->getMessage());
    }
}

$log("=== summary === total=$total uploaded=$uploaded skipped=$skipped failed=$failed");

// Generate the SQL that flips ONLY the rows whose file is confirmed on MinIO.
if ($sqlFile && $migratedKeys) {
    $fh = fopen($sqlFile, 'w');
    fwrite($fh, "-- Auto-generated " . date('Y-m-d H:i:s') . "\n");
    fwrite($fh, "-- Sets disk='minio' ONLY for attachments whose file was confirmed uploaded.\n");
    fwrite($fh, "-- Review, then run in SQL Server (SSMS).\n\n");
    $chunks = array_chunk($migratedKeys, 500);
    foreach ($chunks as $chunk) {
        $vals = array_map(fn($k) => "N'" . str_replace("'", "''", $k) . "'", $chunk);
        fwrite($fh, "UPDATE execution_attachments SET disk = 'minio'\n");
        fwrite($fh, "WHERE file_path IN (\n    " . implode(",\n    ", $vals) . "\n);\n\n");
    }
    fclose($fh);
    $log("Wrote SQL update script: $sqlFile (" . count($migratedKeys) . " keys)");
}

if ($logfh) fclose($logfh);

// Non-zero exit if any uploads failed, so the shell wrapper can warn.
exit($failed > 0 ? 1 : 0);
