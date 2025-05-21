<?php
/**
 * Author: Fatma Terzi (20190702041)
 * homepage.php – Part III: Anasayfa, Son 10 Çalınan Şarkı arama ve önceliklendirme
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$uid  = $_SESSION['user_id'];
$name = $_SESSION['name'];
$mysqli = new mysqli('localhost','root','','fatma_terzi');
if ($mysqli->connect_error) die($mysqli->connect_error);

// Kullanıcının ülke_id'sini al
$stmt = $mysqli->prepare("SELECT country_id FROM USERS WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($cid);
$stmt->fetch();
$stmt->close();

// Playlistleri al
$plstm = $mysqli->prepare("SELECT playlist_id, title, image FROM PLAYLISTS WHERE user_id=?");
$plstm->bind_param("i", $uid);
$plstm->execute();
$plstm->store_result();
$plstm->bind_result($pid, $pt, $pi);

// Arama terimini al
$search = trim($_GET['q_song'] ?? '');

// Son 10 çalınan şarkı - aramaya göre önceliklendir
if ($search !== '') {
    $like_start = $search . '%';
    $like_any = '%' . $search . '%';
    $hst = $mysqli->prepare(
        "SELECT s.song_id, s.title, s.image
         FROM PLAY_HISTORY h
         JOIN SONGS s ON h.song_id=s.song_id
         WHERE h.user_id=?
         ORDER BY 
           (s.title LIKE ?) DESC,
           (s.title LIKE ?) DESC,
           h.playtime DESC
         LIMIT 10"
    );
    $hst->bind_param("iss", $uid, $like_start, $like_any);
} else {
    $hst = $mysqli->prepare(
        "SELECT s.song_id, s.title, s.image
         FROM PLAY_HISTORY h
         JOIN SONGS s ON h.song_id=s.song_id
         WHERE h.user_id=?
         ORDER BY h.playtime DESC
         LIMIT 10"
    );
    $hst->bind_param("i", $uid);
}
$hst->execute();
$hst->store_result();
$hst->bind_result($sid, $st, $si);

// Ülkenin top 5 sanatçısı
$ast = $mysqli->prepare(
    "SELECT artist_id, name, listeners, image
     FROM ARTISTS
     WHERE country_id=?
     ORDER BY listeners DESC
     LIMIT 5"
);
$ast->bind_param("i", $cid);
$ast->execute();
$ast->store_result();
$ast->bind_result($aid, $an, $al, $ai);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $img = trim($_POST['image'] ?? '');
    $uid = $_SESSION['user_id'];
    if ($title === '') {
        $error = 'Başlık boş olamaz!';
    } else {
        $stmt = $mysqli->prepare("INSERT INTO PLAYLISTS (user_id, title, description, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $uid, $title, $desc, $img);
        $stmt->execute();
        $stmt->close();
        header("Location: homepage.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Yeni Playlist Ekle</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="header">
    <img src="images/logo.png" alt="Logo" class="header-logo">
    <span class="header-title">Harmony DB</span>
  </div>
  <div class="container">
    <h2>Yeni Playlist Ekle</h2>
    <?php if($error): ?><p class="error-message"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
      <label>Başlık: <input type="text" name="title" required></label><br>
      <label>Açıklama: <input type="text" name="description"></label><br>
      <label>Görsel URL: <input type="text" name="image"></label><br>
      <button type="submit">Ekle</button>
    </form>
    <a href="homepage.php">← Geri Dön</a>
  </div>
</body>
</html>
