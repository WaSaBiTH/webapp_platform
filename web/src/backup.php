<?php
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'webappdb';
$user = getenv('DB_USER') ?: 'webuser';
$password = getenv('DB_PASS') ?: 'webpass';

$timestamp = date('Y-m-d_H-i-s');
$backupFileName = "backup_{$timestamp}.sql";
$backupFile = "/tmp/{$backupFileName}";

$messages = [];

// 1. Dump database using mysqldump
$dumpCommand = "MYSQL_PWD='{$password}' mysqldump --skip-ssl -h {$host} -u {$user} {$dbname} > {$backupFile} 2>&1";
exec($dumpCommand, $dumpOutput, $dumpReturnCode);

if ($dumpReturnCode === 0) {
    $messages[] = "✅ [SUCCESS] Database backup created locally: " . $backupFileName;

    // 2. Rclone sync to Google Drive
    // Note: This requires Rclone to be configured with a remote named 'gdrive'
    // To configure this, you would run `docker exec -it <web_container> rclone config`
    // OR mount a pre-configured rclone.conf file
    $rcloneCommand = "rclone --config /var/www/rclone.conf copy {$backupFile} gdrive:WebPlatformBackups/ 2>&1";
    exec($rcloneCommand, $rcloneOutput, $rcloneReturnCode);

    if ($rcloneReturnCode === 0) {
        $messages[] = "✅ [SUCCESS] Backup synced to Google Drive successfully.";
    }
    else {
        $messages[] = "⚠️ [WARNING] Failed to sync to Google Drive. This is expected if Rclone is not configured yet.";
        $messages[] = "--- Rclone Output ---";
        $messages = array_merge($messages, $rcloneOutput);
        $messages[] = "---------------------";
        $messages[] = "💡 <b>How to fix:</b> You need to configure rclone. Either mount a valid <code>rclone.conf</code> in <code>docker-compose.yml</code> or run:";
        $messages[] = "<code>docker exec -it webapp_platform-web-1 rclone config</code>";
        $messages[] = "Select 'drive' for Google Drive and follow the prompts.";
    }
}
else {
    $messages[] = "❌ [ERROR] Failed to create database backup.";
    $messages[] = "--- mysqldump Output ---";
    $messages = array_merge($messages, $dumpOutput);
    $messages[] = "---------------------";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backup Status</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { color: #1a73e8; margin-top: 0; }
        .log { background: #202124; color: #e8eaed; padding: 20px; border-radius: 8px; margin-top: 20px; font-family: "Courier New", Courier, monospace; line-height: 1.5; font-size: 14px; overflow-x: auto; }
        .log div { margin-bottom: 5px; }
        a.back-btn { display: inline-block; margin-top: 25px; text-decoration: none; color: white; background: #1a73e8; padding: 10px 20px; border-radius: 4px; font-weight: bold; }
        a.back-btn:hover { background: #1557b0; }
        
        /* Mobile Responsiveness */
        @media (max-width: 650px) {
            body { padding: 20px 10px; }
            .container { padding: 20px 15px; }
            h2 { font-size: 1.5rem; }
            .log { font-size: 12px; padding: 15px; }
            a.back-btn { display: block; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Backup Process Log</h2>
        <p>Executing MariaDB backup and Rclone sync to Google Drive...</p>
        
        <div class="log">
            <?php foreach ($messages as $msg): ?>
                <div><?php echo $msg; ?></div>
            <?php
endforeach; ?>
        </div>
        
        <a href="index.php" class="back-btn">← Back to Dashboard</a>
    </div>
</body>
</html>
