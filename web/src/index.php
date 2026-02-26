<?php
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'webappdb';
$user = getenv('DB_USER') ?: 'webuser';
$password = getenv('DB_PASS') ?: 'webpass';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "<br>Please ensure the database container is running and initialized.");
}

// Ensure system_settings table exists (useful since we added it to init.sql late)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL
    )");

    // Insert default value if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings WHERE setting_key = 'auto_backup'");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES ('auto_backup', '0')");
    }
}
catch (PDOException $e) {
} // Ignore silently if creation fails or already exists

// Handle Auto-Backup Toggle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_autobackup') {
    $newState = isset($_POST['auto_backup_enabled']) ? '1' : '0';
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = :val WHERE setting_key = 'auto_backup'");
    $stmt->execute(['val' => $newState]);
    // PRG pattern
    header("Location: index.php");
    exit;
}

// Handle Add User Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO users (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);
        // Post/Redirect/Get (PRG) pattern to prevent form resubmission
        header("Location: index.php");
        exit;
    }
}

// Fetch current auto_backup setting
$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_backup'");
$setting = $stmt->fetch(PDO::FETCH_ASSOC);
$autoBackupEnabled = ($setting && $setting['setting_value'] === '1');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web App Platform Demo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --success-hover: #059669;
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f6f8fb 0%, #e5e7eb 100%);
            color: var(--text-main);
            margin: 0; 
            padding: 40px 20px; 
            min-height: 100vh;
        }

        .container { 
            max-width: 850px; 
            margin: 0 auto; 
            background: var(--card-bg); 
            padding: 40px; 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow); 
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 { 
            color: var(--primary); 
            margin-top: 0; 
            font-weight: 700;
            font-size: 2.3rem;
            letter-spacing: -0.025em;
            margin-bottom: 10px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 1.05rem;
            margin: 0;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .form-card { 
            margin-bottom: 35px; 
            background: #f8fafc; 
            padding: 25px; 
            border-radius: var(--radius-md); 
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .input-group {
            display: flex; 
            gap: 12px;
        }

        input[type="text"] { 
            flex-grow: 1;
            padding: 14px 16px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            font-size: 16px; 
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        button { 
            padding: 14px 24px; 
            background: var(--primary); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 15px; 
            font-weight: 600; 
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        button:hover { 
            background: var(--primary-hover); 
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(1px);
        }

        .btn-success { 
            background: var(--success); 
        }

        .btn-success:hover { 
            background: var(--success-hover); 
        }

        .table-container {
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
        }

        th, td { 
            padding: 16px 20px; 
            text-align: left; 
            border-bottom: 1px solid var(--border-color); 
        }

        th { 
            background-color: #f8fafc; 
            font-weight: 600; 
            color: var(--text-muted); 
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f1f5f9;
        }

        .td-id {
            color: var(--text-muted);
            font-weight: 500;
            width: 80px;
        }

        .td-name {
            font-weight: 500;
        }

        .td-date {
            color: var(--text-muted);
            font-size: 0.9em;
        }

        .backup-card {
            background: linear-gradient(to right, #ecfdf5, #d1fae5);
            padding: 25px 30px;
            border-radius: var(--radius-md);
            margin-top: 30px;
            border: 1px solid #10b98150;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .backup-info h3 {
            color: #047857;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.15rem;
        }

        .backup-info p {
            color: #065f46;
            margin: 0;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .footer-links {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
        }

        .link-card {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: #f8fafc;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            border-radius: 30px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .link-card:hover {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 38px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Toggle Switch Styles */
        .toggle-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #10b98150;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .toggle-label {
            font-weight: 500;
            color: #065f46;
            font-size: 0.95rem;
        }
        
        .auto-backup-form {
            display: flex;
            align-items: center;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Web Application Platform EIEI</h1>
            <p class="subtitle">Demonstration of the 5 Dimensions of Platform Management</p>
        </div>

        <div class="form-card">
            <h3 class="section-title"><i class="fas fa-users-cog"></i> M1: Access Control & User Management</h3>
            <form method="POST" class="input-group">
                <input type="text" name="name" placeholder="Enter new user's full name" required autocomplete="off">
                <button type="submit"><i class="fas fa-user-plus"></i> Add User</button>
            </form>
        </div>

        <h3 class="section-title"><i class="fas fa-address-book"></i> Registered Users Directory</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="td-id">ID</th>
                        <th>User Name</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");

if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = date('M j, Y, h:i A', strtotime($row['created_at']));
        echo "<tr>";
        echo "<td class='td-id'>#" . str_pad($row['id'], 3, '0', STR_PAD_LEFT) . "</td>";
        echo "<td class='td-name'><i class='fas fa-user-circle' style='color:#cbd5e1; margin-right:10px; font-size: 1.1em; vertical-align: middle;'></i>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td class='td-date'><i class='far fa-clock' style='margin-right:8px; opacity:0.7;'></i>" . $date . "</td>";
        echo "</tr>";
    }
}
else {
    echo "<tr><td colspan='3'>
                                <div class='empty-state'>
                                    <i class='fas fa-users-slash'></i>
                                    <p>No users found in the database.</p>
                                </div>
                              </td></tr>";
}
?>
                </tbody>
            </table>
        </div>

        <!-- Backup functionality -->
        <div class="backup-card">
            <div class="backup-info" style="flex-grow: 1;">
                <h3><i class="fas fa-shield-alt"></i> M5: Security & Disaster Recovery</h3>
                <p>Hot backup MariaDB and sync to Google Drive via Rclone.</p>
                
                <div class="toggle-container">
                    <form method="POST" class="auto-backup-form" id="autoBackupForm">
                        <input type="hidden" name="action" value="toggle_autobackup">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_backup_enabled" id="autoBackupToggle" onchange="document.getElementById('autoBackupForm').submit();" <?php echo $autoBackupEnabled ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="toggle-label" style="margin-left: 12px;">Enable Daily Auto-Backup (2:00 AM)</span>
                    </form>
                </div>
            </div>
            <form action="backup.php" method="POST" style="margin-left: 20px;">
                <button type="submit" class="btn-success"><i class="fas fa-cloud-upload-alt"></i> Run Backup Now</button>
            </form>
        </div>

        <div class="footer-links">
            <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=RDdQw4w9WgXcQ&start_radio=1" target="_blank" class="link-card">
                <i class="fas fa-database"></i> Open phpMyAdmin Dashboard
            </a>
        </div>
    </div>
</body>
</html>
