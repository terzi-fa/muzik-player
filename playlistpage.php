<?php
/**
 * Author: Fatma Terzi (20190702041)
 * playlistpage.php – Part III: Playlist detayları ve şarkı ekleme (sync düzeltmesi)
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
$plid = intval($_GET['id'] ?? 0);

$mysqli = new mysqli('localhost', 'root', '', 'fatma_terzi');
if ($mysqli->connect_error) die('DB bağlantı hatası: ' . $mysqli->connect_error);

// Playlist bilgisi
$stmt = $mysqli->prepare(
    'SELECT title, description FROM PLAYLISTS WHERE playlist_id = ?'
);
$stmt->bind_param('i', $plid);
$stmt->execute();
$stmt->store_result();            // <-- store_result ekleyin
$stmt->bind_result($ptitle, $pdesc);
if (!$stmt->fetch()) die('Playlist bulunamadı');
$stmt->close();

// Şarkı ekleme işlemi
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $song_title = trim($_POST['song_title'] ?? '');
    if ($song_title === '') {
        $error = 'Lütfen bir şarkı adı girin.';
    } else {
        // Şarkı ID'sini al
        $s = $mysqli->prepare('SELECT song_id FROM SONGS WHERE title = ?');
        $s->bind_param('s', $song_title);
        $s->execute();
        $s->store_result();          // <-- store_result ekleyin
        $s->bind_result($sid);
        if ($s->fetch()) {
            $s->close();
            // Duplicate kontrol
            $chk = $mysqli->prepare(
                'SELECT 1 FROM PLAYLIST_SONGS WHERE playlist_id = ? AND song_id = ?'
            );
            $chk->bind_param('ii', $plid, $sid);
            $chk->execute();
            $chk->store_result();      // zaten ekli
            if ($chk->num_rows === 0) {
                $ins = $mysqli->prepare(
                    'INSERT INTO PLAYLIST_SONGS (playlist_id, song_id, date_added) VALUES (?, ?, CURDATE())'
                );
                $ins->bind_param('ii', $plid, $sid);
                $ins->execute();
                $ins->close();
                header("Location: playlistpage.php?id={$plid}");
                exit;
            } else {
                $error = 'Bu şarkı zaten playlist\'te var.';
            }
            $chk->close();
        } else {
            $error = 'Şarkı bulunamadı.';
            $s->close();
        }
    }
}

// Playlist şarkılarını listele
$songs = $mysqli->prepare(
    'SELECT s.song_id, s.title, a.name AS artist, c.country_name
     FROM PLAYLIST_SONGS ps
     JOIN SONGS s ON ps.song_id = s.song_id
     JOIN ALBUMS al ON s.album_id = al.album_id
     JOIN ARTISTS a ON al.artist_id = a.artist_id
     JOIN COUNTRY c ON a.country_id = c.country_id
     WHERE ps.playlist_id = ?
     ORDER BY ps.date_added DESC'
);
$songs->bind_param('i', $plid);
$songs->execute();
$result = $songs->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($ptitle) ?> – Playlist</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="page-container">
    <a href="homepage.php" class="back-link">← Anasayfa</a>
    <h2><?= htmlspecialchars($ptitle) ?></h2>
    <p class="description"><?= htmlspecialchars($pdesc) ?></p>

    <div class="form-container">
      <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form action="playlistpage.php?id=<?= $plid ?>" method="post" class="add-form">
        <input type="text" name="song_title" placeholder="Eklenecek şarkının tam adı">
        <button type="submit">Ekle</button>
      </form>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Şarkı</th><th>Sanatçı</th><th>Ülke</th></tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><a href="currentmusic.php?id=<?= $row['song_id'] ?>"><?= htmlspecialchars($row['title']) ?></a></td>
            <td><?= htmlspecialchars($row['artist']) ?></td>
            <td><?= htmlspecialchars($row['country_name']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
