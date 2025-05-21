<?php
/**
 * Yazar: Fatma Terzi (20190702041)
 * install.php – Part I: Veritabanı ve tabloların kurulumu
 */

$host   = 'localhost';
$dbname = 'fatma_terzi';
$user   = 'root';
$pass   = '';

/**
 * Ekrana renkli mesaj gösterir
 */
function showMessage($msg, $type = 'info') {
    $colors = [
        'success' => '#28a745',
        'error'   => '#dc3545',
        'warning' => '#ffc107',
        'info'    => '#17a2b8'
    ];
    $c = $colors[$type] ?? $colors['info'];
    echo "<div style='margin:10px;padding:10px;border:1px solid {$c};color:{$c};'>{$msg}</div>";
}

// HTML başlığı
echo '<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>DB Kurulumu</title>
</head>
<body>
  <h1>DB Kurulumu Başlıyor...</h1>';

try {
    // 1) MySQL’e bağlan
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    showMessage("✓ MySQL’e bağlandı", "success");

    // **İsteğe bağlı**: Mevcut veritabanını silip yeniden oluştur
    $pdo->exec("DROP DATABASE IF EXISTS `{$dbname}`");
    showMessage("ℹ Veritabanı '{$dbname}' (varsa) silindi", "info");

    // 2) Veritabanını oluştur
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    showMessage("✓ Veritabanı '{$dbname}' oluşturuldu veya zaten var", "success");

    // 3) Veritabanını kullan
    $pdo->exec("USE `{$dbname}`");
    showMessage("✓ '{$dbname}' seçildi", "success");

    // 4) Tabloları bağımlılık sırasına göre yarat
    $tables = [

        // 1) COUNTRY
        "CREATE TABLE IF NOT EXISTS COUNTRY (
            country_id   INT AUTO_INCREMENT PRIMARY KEY,
            country_name VARCHAR(100) NOT NULL,
            country_code VARCHAR(5)   NOT NULL
        ) ENGINE=InnoDB",

        // 2) USERS
        "CREATE TABLE IF NOT EXISTS USERS (
            user_id            INT AUTO_INCREMENT PRIMARY KEY,
            country_id         INT,
            age                INT,
            name               VARCHAR(50)  NOT NULL,
            username           VARCHAR(50)  NOT NULL UNIQUE,
            email              VARCHAR(100) NOT NULL UNIQUE,
            password           VARCHAR(255) NOT NULL,
            date_joined        DATE,
            last_login         DATETIME,
            follower_num       INT DEFAULT 0,
            subscription_type  ENUM('free','premium','family') DEFAULT 'free',
            top_genre          VARCHAR(30),
            num_songs_liked    INT DEFAULT 0,
            most_played_artist VARCHAR(50),
            image              VARCHAR(255),
            FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id)
        ) ENGINE=InnoDB",

        // 3) ARTISTS
        "CREATE TABLE IF NOT EXISTS ARTISTS (
            artist_id       INT AUTO_INCREMENT PRIMARY KEY,
            name            VARCHAR(100) NOT NULL,
            genre           VARCHAR(50),
            date_joined     DATE,
            total_num_music INT DEFAULT 0,
            total_albums    INT DEFAULT 0,
            listeners       INT DEFAULT 0,
            bio             TEXT,
            country_id      INT,
            image           VARCHAR(255),
            FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id)
        ) ENGINE=InnoDB",

        // 4) ALBUMS
        "CREATE TABLE IF NOT EXISTS ALBUMS (
            album_id     INT AUTO_INCREMENT PRIMARY KEY,
            artist_id    INT NOT NULL,
            name         VARCHAR(100) NOT NULL,
            release_date DATE,
            genre        VARCHAR(50),
            music_number INT,
            image        VARCHAR(255),
            FOREIGN KEY (artist_id) REFERENCES ARTISTS(artist_id)
        ) ENGINE=InnoDB",

        // 5) SONGS
        "CREATE TABLE IF NOT EXISTS SONGS (
            song_id      INT AUTO_INCREMENT PRIMARY KEY,
            album_id     INT NOT NULL,
            title        VARCHAR(100) NOT NULL,
            duration     INT COMMENT 'saniye',
            genre        VARCHAR(50),
            release_date DATE,
            rank         INT,
            image        VARCHAR(255),
            FOREIGN KEY (album_id) REFERENCES ALBUMS(album_id)
        ) ENGINE=InnoDB",

        // 6) PLAY_HISTORY — artık USERS ve SONGS var
        "CREATE TABLE IF NOT EXISTS PLAY_HISTORY (
            play_id  INT AUTO_INCREMENT PRIMARY KEY,
            user_id  INT NOT NULL,
            song_id  INT NOT NULL,
            playtime DATETIME,
            FOREIGN KEY (user_id) REFERENCES USERS(user_id),
            FOREIGN KEY (song_id) REFERENCES SONGS(song_id)
        ) ENGINE=InnoDB",

        // 7) PLAYLISTS
        "CREATE TABLE IF NOT EXISTS PLAYLISTS (
            playlist_id  INT AUTO_INCREMENT PRIMARY KEY,
            user_id      INT NOT NULL,
            title        VARCHAR(100) NOT NULL,
            description  TEXT,
            date_created DATE,
            image        VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES USERS(user_id)
        ) ENGINE=InnoDB",

        // 8) PLAYLIST_SONGS
        "CREATE TABLE IF NOT EXISTS PLAYLIST_SONGS (
            playlistsong_id INT AUTO_INCREMENT PRIMARY KEY,
            playlist_id     INT NOT NULL,
            song_id         INT NOT NULL,
            date_added      DATE,
            FOREIGN KEY (playlist_id) REFERENCES PLAYLISTS(playlist_id),
            FOREIGN KEY (song_id)     REFERENCES SONGS(song_id),
            UNIQUE KEY uidx_playlist_song (playlist_id,song_id)
        ) ENGINE=InnoDB",

        // 9) FOLLOWS (isteğe bağlı)
        "CREATE TABLE IF NOT EXISTS FOLLOWS (
            follow_id    INT AUTO_INCREMENT PRIMARY KEY,
            user_id      INT NOT NULL,
            artist_id    INT NOT NULL,
            follow_date  DATE,
            FOREIGN KEY (user_id)   REFERENCES USERS(user_id),
            FOREIGN KEY (artist_id) REFERENCES ARTISTS(artist_id),
            UNIQUE KEY uidx_follow (user_id,artist_id)
        ) ENGINE=InnoDB"
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    showMessage("✓ Tüm tablolar oluşturuldu", "success");

    // 5) output.sql varsa veri import et
    if (file_exists('output.sql')) {
        $stmts = array_filter(array_map('trim', explode(';', file_get_contents('output.sql'))));
        $pdo->beginTransaction();
        foreach ($stmts as $stmt) {
            if ($stmt) {
                $pdo->exec($stmt);
            }
        }
        $pdo->commit();
        showMessage("✓ output.sql içindeki veriler başarıyla import edildi", "success");
    } else {
        showMessage("ℹ output.sql bulunamadı; sadece boş tablolar oluşturuldu", "warning");
    }

    // 6) Kurulum tamamlandı, login sayfasına yönlendir
    echo '<script>setTimeout(()=>{ window.location.href="login.html"; }, 2000);</script>';
    showMessage("✔ Kurulum tamamlandı, yönlendiriliyorsunuz...", "success");

} catch (PDOException $e) {
    showMessage("✖ Hata: " . $e->getMessage(), "error");
}

echo '</body></html>';
