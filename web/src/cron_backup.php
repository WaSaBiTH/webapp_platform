<?php
// Load environment variables strictly required for CLI cron job execution
// Source them from /etc/environment if available
$envFile = '/etc/environment';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'webappdb';
$user = getenv('DB_USER') ?: 'webuser';
$password = getenv('DB_PASS') ?: 'webpass';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if auto backup is enabled
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_backup'");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($setting && $setting['setting_value'] === '1') {
        echo date('[Y-m-d H:i:s]') . " Auto-backup is ENABLED. Starting backup process...\n";

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "/tmp/backup_auto_{$timestamp}.sql";

        // Dump database
        $dumpCommand = "MYSQL_PWD='{$password}' mysqldump --skip-ssl -h {$host} -u {$user} {$dbname} > {$backupFile} 2>&1";
        exec($dumpCommand, $dumpOutput, $dumpReturnCode);

        if ($dumpReturnCode === 0) {
            echo date('[Y-m-d H:i:s]') . " Local backup created: {$backupFile}\n";

            // Rclone sync
            $rcloneCommand = "rclone --config /var/www/rclone.conf copy {$backupFile} gdrive:WebPlatformBackups/ 2>&1";
            exec($rcloneCommand, $rcloneOutput, $rcloneReturnCode);

            if ($rcloneReturnCode === 0) {
                echo date('[Y-m-d H:i:s]') . " Backup synced to Google Drive successfully.\n";
            }
            else {
                echo date('[Y-m-d H:i:s]') . " ERROR: Failed to sync to Google Drive.\n";
                echo implode("\n", $rcloneOutput) . "\n";
            }
        }
        else {
            echo date('[Y-m-d H:i:s]') . " ERROR: Failed to create database backup.\n";
            echo implode("\n", $dumpOutput) . "\n";
        }

        // Clean up tmp file
        if (file_exists($backupFile)) {
            unlink($backupFile);
        }

    }
    else {
        echo date('[Y-m-d H:i:s]') . " Auto-backup is DISABLED. Skipping.\n";
    }

}
catch (PDOException $e) {
    echo date('[Y-m-d H:i:s]') . " ERROR: Database connection failed: " . $e->getMessage() . "\n";
}
?>
