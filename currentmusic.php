<?php
/**
 * Author: Fatma Terzi (20190702041)
 * currentmusic.php – Part III: Şarkı oynatıcı
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");
$id = intval($_GET['id'] ?? 0);
$u  = $_SESSION['user_id'];

$mysqli = new mysqli('localhost','root','','fatma_terzi');
if($mysqli->connect_error) die($mysqli->connect_error);

// Şarkı + albüm + sanatçı bilgisi
$stmt = $mysqli->prepare("
  SELECT s.title,s.image,al.name AS album,
         s.duration,s.genre,s.release_date,s.rank,
         a.artist_id,a.name AS artist_name,a.image AS artist_img
  FROM SONGS s
  JOIN ALBUMS al ON s.album_id=al.album_id
  JOIN ARTISTS a ON al.artist_id=a.artist_id
  WHERE s.song_id=?
");
$stmt->bind_param("i",$id); $stmt->execute();
$stmt->bind_result($title,$img,$alb,$dur,$gen,$rdate,$rank,$aid,$an,$ai);
if(!$stmt->fetch()) die("Şarkı bulunamadı");
$stmt->close();

// Geçmişe ekle
$mysqli->query("
  INSERT INTO PLAY_HISTORY (user_id,song_id,playtime)
  VALUES ($u,$id,NOW())
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?></title>
  <style>/* homepage ile aynı stil */</style>
</head>
<body>
  <div class="music-box">
    <h2><?= htmlspecialchars($title) ?></h2>
    <img src="<?= htmlspecialchars($img) ?>" alt=""><br><br>
    <b>Sanatçı:</b>
      <a href="artistpage.php?id=<?= $aid ?>">
        <img src="<?= htmlspecialchars($ai) ?>" alt=""> <?= htmlspecialchars($an) ?>
      </a><br>
    <b>Albüm:</b> <?= htmlspecialchars($alb) ?><br>
    <b>Süre:</b> <?= $dur ?> sn<br>
    <b>Tür:</b> <?= htmlspecialchars($gen) ?><br>
    <b>Çıkış:</b> <?= $rdate ?><br>
    <b>Rank:</b> <?= $rank ?><br><br>
    <audio controls>
      <source src="audio/<?= $id ?>.mp3" type="audio/mpeg">
      Tarayıcı desteklemiyor.
    </audio>
    <br><br><a href="homepage.php">← Anasayfa</a>
  </div>
</body>
</html>
