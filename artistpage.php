<?php
/**
 * Author: Fatma Terzi (20190702041)
 * artistpage.php – Part III: Sanatçı sayfası
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");
$uid = $_SESSION['user_id'];
$aid = intval($_GET['id'] ?? 0);

$mysqli = new mysqli('localhost','root','','fatma_terzi');
if($mysqli->connect_error) die($mysqli->connect_error);

// Sanatçı bilgisi
$stmt = $mysqli->prepare("
  SELECT name,genre,country_id,date_joined,
         total_num_music,total_albums,listeners,bio,image
  FROM ARTISTS WHERE artist_id=?
");
$stmt->bind_param("i",$aid); $stmt->execute();
$stmt->bind_result($an,$gen,$cid,$dj,$tnm,$ta,$lst,$bio,$img);
if(!$stmt->fetch()) die("Sanatçı bulunamadı");
$stmt->close();

// Ülke adı
$cn = $mysqli->query("SELECT country_name FROM COUNTRY WHERE country_id=$cid")
      ->fetch_row()[0];

// Takip etme işlemi
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['follow'])){
  $mysqli->query("
    INSERT IGNORE INTO FOLLOWS (user_id,artist_id,follow_date)
    VALUES ($uid,$aid,CURDATE())
  ");
  $mysqli->query("UPDATE ARTISTS SET listeners=listeners+1 WHERE artist_id=$aid");
}

// Son 5 albüm
$albums = $mysqli->query("
  SELECT album_id,name,release_date,image
  FROM ALBUMS
  WHERE artist_id=$aid
  ORDER BY release_date DESC
  LIMIT 5
");

// Top 5 şarkı
$songs = $mysqli->query("
  SELECT song_id,title,rank
  FROM SONGS
  WHERE album_id IN (
    SELECT album_id FROM ALBUMS WHERE artist_id=$aid
  )
  ORDER BY rank ASC
  LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($an) ?></title>
  <style>/* homepage ile aynı stil */</style>
</head>
<body>
  <div class="artist-box">
    <h2><?= htmlspecialchars($an) ?></h2>
    <img src="<?= htmlspecialchars($img) ?>" alt=""><br>
    <b>Ülke:</b> <?= htmlspecialchars($cn) ?><br>
    <b>Tür:</b> <?= htmlspecialchars($gen) ?><br>
    <b>Katılım:</b> <?= $dj ?><br>
    <b>Müzik:</b> <?= $tnm ?> &nbsp; <b>Albüm:</b> <?= $ta ?><br>
    <b>Dinleyici:</b> <?= $lst ?><br>
    <b>Biyo:</b> <?= nl2br(htmlspecialchars($bio)) ?><br><br>
    <form method="post"><button name="follow">Takip Et</button></form>
    <hr>
    <h3>Son 5 Albüm</h3>
    <ul>
      <?php while($al=$albums->fetch_assoc()): ?>
        <li>
          <img src="<?= htmlspecialchars($al['image']) ?>" alt="" width="40" height="40">
          <a href="currentmusic.php?id=<?= $al['album_id'] ?>">
            <?= htmlspecialchars($al['name']) ?> (<?= $al['release_date'] ?>)
          </a>
        </li>
      <?php endwhile; ?>
    </ul>
    <hr>
    <h3>Top 5 Şarkı</h3>
    <ol>
      <?php while($s=$songs->fetch_assoc()): ?>
        <li>
          <a href="currentmusic.php?id=<?= $s['song_id'] ?>">
            <?= htmlspecialchars($s['title']) ?>
          </a> (Rank: <?= $s['rank'] ?>)
        </li>
      <?php endwhile; ?>
    </ol>
    <br><a href="homepage.php">← Anasayfa</a>
  </div>
</body>
</html>
