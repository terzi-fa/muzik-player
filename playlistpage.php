<?php
/**
 * Author: Fatma Terzi (20190702041)
 * playlistpage.php – Part III: Playlist detayları
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");
$plid = intval($_GET['id'] ?? 0);

$mysqli = new mysqli('localhost','root','','fatma_terzi');
if($mysqli->connect_error) die($mysqli->connect_error);

// Playlist bilgisi
$pl = $mysqli->prepare("SELECT title,description FROM PLAYLISTS WHERE playlist_id=?");
$pl->bind_param("i",$plid); $pl->execute(); $pl->bind_result($pt,$pd);
if(!$pl->fetch()) die("Playlist bulunamadı");
$pl->close();

// Şarkılar + ülke
$songs = $mysqli->query("
  SELECT s.song_id,s.title,a.name AS artist,c.country_name
  FROM PLAYLIST_SONGS ps
  JOIN SONGS s ON ps.song_id=s.song_id
  JOIN ALBUMS al ON s.album_id=al.album_id
  JOIN ARTISTS a ON al.artist_id=a.artist_id
  JOIN COUNTRY c ON a.country_id=c.country_id
  WHERE ps.playlist_id=$plid
  ORDER BY ps.date_added DESC
");

$err = '';
if($_SERVER['REQUEST_METHOD']=='POST'){
  $t = trim($_POST['song_title'] ?? '');
  if($t){
    $res = $mysqli->prepare("SELECT song_id FROM SONGS WHERE title=?");
    $res->bind_param("s",$t); $res->execute(); $res->bind_result($sid);
    if($res->fetch()){
      $exists = $mysqli->query("
        SELECT 1 FROM PLAYLIST_SONGS 
        WHERE playlist_id=$plid AND song_id=$sid
      ");
      if($exists->num_rows==0){
        $d = date('Y-m-d');
        $mysqli->query("INSERT INTO PLAYLIST_SONGS VALUES(NULL,$plid,$sid,'$d')");
        header("Location: playlistpage.php?id=$plid"); exit;
      } else {
        $err = "Zaten playlist’te var.";
      }
    } else {
      $err = "Şarkı bulunamadı.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pt) ?> – Playlist</title>
  <style>/* homepage ile aynı stil */</style>
</head>
<body>
  <div class="container">
    <a href="homepage.php">← Anasayfa</a>
    <h2><?= htmlspecialchars($pt) ?></h2>
    <p><?= htmlspecialchars($pd) ?></p>

    <form class="search-bar" action="search_song.php" method="get">
      <input type="text" name="q" placeholder="Şarkı ara…">
      <button>Ara</button>
    </form>
    <form method="post">
      <input type="text" name="song_title" placeholder="Eklemek için şarkı adı">
      <button>Ekle</button>
    </form>
    <?php if($err): ?><p style="color:red;"><?= htmlspecialchars($err) ?></p><?php endif; ?>

    <table>
      <tr><th>Şarkı</th><th>Sanatçı</th><th>Ülke</th></tr>
      <?php while($row=$songs->fetch_assoc()): ?>
        <tr>
          <td><a href="currentmusic.php?id=<?= $row['song_id'] ?>">
            <?= htmlspecialchars($row['title']) ?>
          </a></td>
          <td><?= htmlspecialchars($row['artist']) ?></td>
          <td><?= htmlspecialchars($row['country_name']) ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</body>
</html>
