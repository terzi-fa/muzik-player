<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); exit;
}

$uid  = $_SESSION['user_id'];
$name = $_SESSION['name'];
$mysqli = new mysqli('localhost','root','','fatma_terzi');
if ($mysqli->connect_error) die($mysqli->connect_error);

// 1) Kullanıcının ülke_id'sini al
$stmt = $mysqli->prepare("SELECT country_id FROM USERS WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->store_result();                   // ← ekledik
$stmt->bind_result($cid);
$stmt->fetch();
$stmt->close();

// 2) Playlistleri hazırla
$plstm = $mysqli->prepare("SELECT playlist_id,title,image FROM PLAYLISTS WHERE user_id=?");
$plstm->bind_param("i", $uid);
$plstm->execute();
$plstm->store_result();                  // ← ekledik
$plstm->bind_result($pid, $pt, $pi);

// 3) Son 10 çalınan şarkı
$hst = $mysqli->prepare("
    SELECT s.song_id, s.title, s.image
    FROM PLAY_HISTORY h
    JOIN SONGS s ON h.song_id=s.song_id
    WHERE h.user_id=?
    ORDER BY h.playtime DESC LIMIT 10
");
$hst->bind_param("i", $uid);
$hst->execute();
$hst->store_result();                     // ← ekledik
$hst->bind_result($sid, $st, $si);

// 4) Ülkenin top 5 sanatçısı
$ast = $mysqli->prepare("
    SELECT artist_id,name,listeners,image
    FROM ARTISTS
    WHERE country_id=?
    ORDER BY listeners DESC
    LIMIT 5
");
$ast->bind_param("i", $cid);
$ast->execute();
$ast->store_result();                     // ← ekledik
$ast->bind_result($aid, $an, $al, $ai);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Merhaba, <?=htmlspecialchars($name)?>!</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <h1>Merhaba, <?=htmlspecialchars($name)?>!</h1>
  <div class="container">
    <!-- Sol: Playlistler -->
    <div class="left">
      <!-- (arama barı) -->
      <h3>Playlistlerim</h3>
      <?php while($plstm->fetch()): ?>
        <div class="playlist">
          <img src="<?=htmlspecialchars($pi)?>" alt="">
          <a href="playlistpage.php?id=<?=$pid?>"><?=htmlspecialchars($pt)?></a>
        </div>
      <?php endwhile; ?>
      <?php $plstm->close(); ?>
    </div>

    <!-- Sağ: Geçmiş & Sanatçılar -->
    <div class="right">
      <h3>Son 10 Çalınan Şarkı</h3>
      <?php while($hst->fetch()): ?>
        <div class="song">
          <img src="<?=htmlspecialchars($si)?>" alt="">
          <a href="currentmusic.php?id=<?=$sid?>"><?=htmlspecialchars($st)?></a>
        </div>
      <?php endwhile; ?>
      <?php $hst->close(); ?>

      <hr>

      <h3>Ülkenin En Çok Dinlenen 5 Sanatçısı</h3>
      <?php while($ast->fetch()): ?>
        <div class="artist">
          <img src="<?=htmlspecialchars($ai)?>" alt="">
          <a href="artistpage.php?id=<?=$aid?>"><?=htmlspecialchars($an)?></a>
          (<?=$al?> dinleyici)
        </div>
      <?php endwhile; ?>
      <?php $ast->close(); ?>
    </div>
  </div>
</body>
</html>
