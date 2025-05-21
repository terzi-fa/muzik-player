<?php
/**
 * Veri üretme scripti
 * Müzik uygulaması için gerekli tüm verileri oluşturur
 */

// Veritabanı bağlantısı
$db = new PDO("mysql:host=localhost;dbname=fatma_terzi", "root", "");

// Foreign key kontrollerini geçici olarak devre dışı bırak
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

// Dosya yolları
$data_dir = __DIR__ . '/data/';

// Tüm tabloları temizle
$tables = ['users', 'artists', 'albums', 'songs', 'playlists', 'playlist_songs', 'play_history', 'country'];
foreach ($tables as $table) {
    $db->exec("TRUNCATE TABLE $table");
    echo "$table tablosu temizlendi.\n";
}

// Ülke verilerini yükle
$countries_file = $data_dir . 'countries.txt';
if (file_exists($countries_file)) {
    $lines = file($countries_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = explode(',', $line);
        $stmt = $db->prepare("INSERT INTO country (country_id, country_name, country_code) VALUES (?, ?, ?)");
        $stmt->execute($data);
    }
    echo "Ülke verileri yüklendi.\n";
} else {
    die("HATA: countries.txt dosyası bulunamadı!\n");
}

// İsim dosyalarını kontrol et
$first_name_file = $data_dir . 'first_name.txt';
$last_name_file = $data_dir . 'last_name.txt';

if (!file_exists($first_name_file) || !file_exists($last_name_file)) {
    die("HATA: first_name.txt veya last_name.txt dosyaları bulunamadı!\nLütfen data klasöründe bu dosyaların olduğundan emin olun.");
}

// İsim dosyalarını oku
$first_names = file($first_name_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$last_names = file($last_name_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Kullanıcı verilerini oluştur
$usernames = [];
for ($i = 1; $i <= 100; $i++) {
    do {
        $first_name = $first_names[array_rand($first_names)];
        $last_name = $last_names[array_rand($last_names)];
        $username = strtolower($first_name . rand(1, 999));
    } while (in_array($username, $usernames));
    $usernames[] = $username;

    $email = strtolower($first_name . $last_name . rand(1, 999) . '@email.com');
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $country_id = rand(1, 50); // İlk 50 ülke arasından seç
    $age = rand(18, 65);
    $date_joined = date('Y-m-d H:i:s', strtotime('-' . rand(0, 365) . ' days'));
    $last_login = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days'));
    $follower_num = rand(0, 10000);
    $subscription_type = ['free', 'premium', 'family'][rand(0, 2)];
    $top_genre = ['Pop', 'Rock', 'Hip-Hop', 'Jazz', 'Classical'][rand(0, 4)];
    $num_songs_liked = rand(0, 500);
    $most_played_artist = $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
    $image = 'user' . $i . '.jpg';

    $stmt = $db->prepare("INSERT INTO users (country_id, user_id, age, name, username, email, password, date_joined, last_login, follower_num, subscription_type, top_genre, num_songs_liked, most_played_artist, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$country_id, $i, $age, "$first_name $last_name", $username, $email, $password, $date_joined, $last_login, $follower_num, $subscription_type, $top_genre, $num_songs_liked, $most_played_artist, $image]);
}
echo "Kullanıcı verileri yüklendi.\n";

// Görsel URL'leri
$image_urls = [
    'https://picsum.photos/200/300?random=1',
    'https://picsum.photos/200/300?random=2',
    'https://picsum.photos/200/300?random=3',
    'https://picsum.photos/200/300?random=4',
    'https://picsum.photos/200/300?random=5'
];

// Diğer verileri yükle
$files = [
    'artists.txt' => 'artists',
    'albums.txt' => 'albums',
    'songs.txt' => 'songs',
    'playlists.txt' => 'playlists',
    'playlist_songs.txt' => 'playlist_songs',
    'play_history.txt' => 'play_history'
];

// Her dosya için veri yükleme işlemi
foreach ($files as $file => $table) {
    $file_path = $data_dir . $file;
    if (file_exists($file_path)) {
        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Verileri yükle
        foreach ($lines as $line) {
            $data = explode(',', $line);
            
            switch ($table) {
                case 'artists':
                    $stmt = $db->prepare("INSERT INTO artists (name, genre, date_joined, total_num_music, total_albums, listeners, bio, country_id, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute($data);
                    break;
                    
                case 'albums':
                    if (count($data) == 6) {
                        $data[] = $image_urls[array_rand($image_urls)];
                    }
                    if (count($data) == 5) {
                        $data[] = rand(5, 20); // music_number
                        $data[] = $image_urls[array_rand($image_urls)];
                    }
                    $stmt = $db->prepare("INSERT INTO albums (album_id, artist_id, name, release_date, genre, music_number, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute($data);
                    break;
                    
                case 'songs':
                    if (count($data) == 7) {
                        $data[] = $image_urls[array_rand($image_urls)];
                    }
                    $stmt = $db->prepare("INSERT INTO songs (song_id, album_id, title, duration, genre, release_date, rank, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute($data);
                    break;
                    
                case 'playlists':
                    if (count($data) == 5) {
                        $data[] = $image_urls[array_rand($image_urls)];
                    }
                    $stmt = $db->prepare("INSERT INTO playlists (playlist_id, user_id, title, description, date_created, image) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute($data);
                    break;
                    
                case 'playlist_songs':
                    $stmt = $db->prepare("INSERT INTO playlist_songs (playlistsong_id, playlist_id, song_id, date_added) VALUES (?, ?, ?, ?)");
                    $stmt->execute($data);
                    break;
                    
                case 'play_history':
                    $stmt = $db->prepare("INSERT INTO play_history (play_id, user_id, song_id, playtime) VALUES (?, ?, ?, ?)");
                    $stmt->execute($data);
                    break;
            }
        }
        
        echo "$table tablosuna veriler yüklendi.\n";
    } else {
        echo "$file dosyası bulunamadı.\n";
    }
}

// Foreign key kontrollerini tekrar etkinleştir
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\nTüm veriler başarıyla yüklendi.\n";
?>