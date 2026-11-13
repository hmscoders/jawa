<?php
/**
 * WP Create Admin - Recovery Script
 * Upload ke root WordPress, akses via browser, lalu HAPUS setelah selesai!
 */

// ====== KONFIGURASI ======
$new_username = 'adminbaru';
$new_password = 'PasswordKuat123!';
$new_email    = 'admin@example.com';
// =========================

// Cari wp-load.php (support subfolder)
$wp_load = __DIR__ . '/wp-load.php';
if (!file_exists($wp_load)) {
    $wp_load = dirname(__DIR__) . '/wp-load.php';
}

if (!file_exists($wp_load)) {
    die("❌ wp-load.php tidak ditemukan. Pastikan script ada di root WordPress.");
}

require_once($wp_load);

// Cek apakah user sudah ada
if (username_exists($new_username)) {
    $user = get_user_by('login', $new_username);
    wp_set_password($new_password, $user->ID);
    echo "♻️ User <b>$new_username</b> sudah ada, password direset.<br>";
} else {
    $user_id = wp_create_user($new_username, $new_password, $new_email);
    if (is_wp_error($user_id)) {
        die("❌ Gagal: " . $user_id->get_error_message());
    }
    echo "✅ User <b>$new_username</b> berhasil dibuat (ID: $user_id).<br>";
}

// Set role admin
$user = new WP_User($user_id ?? get_user_by('login', $new_username)->ID);
$user->set_role('administrator');

echo "👑 Role: <b>administrator</b><br>";
echo "🔑 Login: <b>$new_username</b> / <b>$new_password</b><br><br>";
echo "⚠️ <b>HAPUS file ini sekarang juga!</b>";
