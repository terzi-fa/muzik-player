<?php
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");
$album_id = intval($_GET['id'] ?? 0);
$mysqli = new mysqli('localhost','root','','fatma_terzi');
if($mysqli->connect_error) die($mysqli->connect_error);

// Albüm bilgisi
$stmt = $mysqli->prepare("
  SELECT al.name, al.release_date, al.image, a.name as artist
  FROM ALBUMS al
  JOIN ARTISTS a ON al.artist_id=a.artist_id
  WHERE al.album_id=?
");
$stmt->bind_param("i", $album_id);
$stmt->execute();
$stmt->bind_result($album_name, $release_date, $album_image, $artist_name);
if(!$stmt->fetch()) die('Albüm bulunamadı');
$stmt->close();

// Şarkılar
$songs = $mysqli->prepare("
  SELECT song_id, title
  FROM SONGS
  WHERE album_id=?
  ORDER BY title ASC
");
$songs->bind_param("i", $album_id);
$songs->execute();
$songs_result = $songs->get_result();
?>
<!DOCTYPE html>
<html lang='tr'>
<head>
  <meta charset='UTF-8'>
  <title><?= htmlspecialchars($album_name) ?> - Albüm</title>
  <link rel='stylesheet' href='css/style.css'>
</head>
<body>
  <div class="header">
    <img src="images/logo.png" alt="Logo" class="header-logo">
    <span class="header-title">Harmony DB</span>
  </div>
  <div class='album-box'>
    <h2><?= htmlspecialchars($album_name) ?></h2>
    <img src='<?= htmlspecialchars($album_image) ?>' alt='' width='120'><br>
    <b>Sanatçı:</b> <?= htmlspecialchars($artist_name) ?><br>
    <b>Çıkış Tarihi:</b> <?= htmlspecialchars($release_date) ?><br>
    <h3>Şarkılar</h3>
    <ul>
      <?php while($row = $songs_result->fetch_assoc()): ?>
        <li>
          <a href='currentmusic.php?id=<?= $row['song_id'] ?>'><?= htmlspecialchars($row['title']) ?></a>
        </li>
      <?php endwhile; ?>
    </ul>
    <a href='javascript:history.back()'>← Geri Dön</a>
  </div>
</body>
</html> 