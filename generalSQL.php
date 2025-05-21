<?php
/**
 * Author: Fatma Terzi (20190702041)
 * generalSQL.php – Part III: Ek Gen ve Ülke Sorguları
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");

$mysqli = new mysqli('localhost','root','','fatma_terzi');
if($mysqli->connect_error) die($mysqli->connect_error);

// 1) Top 5 Genre
$top_genres = $mysqli->query("
  SELECT genre,COUNT(*) AS cnt 
  FROM SONGS 
  GROUP BY genre 
  ORDER BY cnt DESC 
  LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// 2) Ülkeye göre Top 5 Şarkı
$top_songs_by_country = [];
if(!empty($_GET['country_code'])){
  $cc = $_GET['country_code'];
  $stmt = $mysqli->prepare("
    SELECT s.title,a.name AS artist,COUNT(h.play_id) AS plays
    FROM PLAY_HISTORY h
    JOIN SONGS s ON h.song_id=s.song_id
    JOIN ALBUMS al ON s.album_id=al.album_id
    JOIN ARTISTS a ON al.artist_id=a.artist_id
    JOIN COUNTRY c ON a.country_id=c.country_id
    WHERE c.country_code=?
    GROUP BY s.song_id
    ORDER BY plays DESC
    LIMIT 5
  ");
  $stmt->bind_param("s",$cc);
  $stmt->execute();
  $top_songs_by_country = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Ülkeler listesi
$countries = $mysqli->query("
  SELECT country_code,country_name 
  FROM COUNTRY 
  ORDER BY country_name
")->fetch_all(MYSQLI_ASSOC);

// 3) Custom SQL
$custom_result = [];
$custom_error  = '';
$custom_sql = '';

if($_SERVER['REQUEST_METHOD']=='POST'){
  if(isset($_POST['custom_sql'])) {
    $sql = trim($_POST['custom_sql'] ?? '');
    if(preg_match('/^\s*select/i',$sql)){
      if($res = $mysqli->query($sql)){
        $custom_result = $res->fetch_all(MYSQLI_ASSOC);
        $custom_sql = $sql;
      } else {
        $custom_error = "Sorgu hatası veya sonuç yok";
      }
    } else {
      $custom_error = "Yalnızca SELECT izni var";
    }
  } elseif(isset($_POST['top_genres'])) {
    $custom_sql = "SELECT genre, COUNT(*) AS cnt FROM SONGS GROUP BY genre ORDER BY cnt DESC LIMIT 5";
    $custom_result = $mysqli->query($custom_sql)->fetch_all(MYSQLI_ASSOC);
  } elseif(isset($_POST['top_songs'])) {
    $custom_sql = "
      SELECT s.title, a.name AS artist, c.country_name, COUNT(h.play_id) AS plays
      FROM PLAY_HISTORY h
      JOIN SONGS s ON h.song_id=s.song_id
      JOIN ALBUMS al ON s.album_id=al.album_id
      JOIN ARTISTS a ON al.artist_id=a.artist_id
      JOIN COUNTRY c ON a.country_id=c.country_id
      GROUP BY s.song_id
      ORDER BY plays DESC
      LIMIT 5
    ";
    $custom_result = $mysqli->query($custom_sql)->fetch_all(MYSQLI_ASSOC);
  }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Gen ve Ülke Sorguları</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="container">
    <a href="homepage.php">← Anasayfa</a>
    <h2>Gen ve Ülke Sorguları</h2>

    <!-- Top 5 Genre -->
    <div class="section">
      <h3>Top 5 Genre</h3>
      <table>
        <tr><th>Genre</th><th>Adet</th></tr>
        <?php foreach($top_genres as $g): ?>
          <tr><td><?= htmlspecialchars($g['genre']) ?></td><td><?= $g['cnt'] ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>

    <!-- Ülkeye Göre Şarkı -->
    <div class="section">
      <h3>Ülkenin Top 5 Şarkısı</h3>
      <form method="get">
        <select name="country_code">
          <option value="">Seçiniz</option>
          <?php foreach($countries as $c): ?>
            <option value="<?= $c['country_code'] ?>"<?=($_GET['country_code']==$c['country_code'])?' selected':''?>>
              <?= htmlspecialchars($c['country_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Göster</button>
      </form>
      <?php if($top_songs_by_country): ?>
        <table>
          <tr><th>Şarkı</th><th>Sanatçı</th><th>Çalma</th></tr>
          <?php foreach($top_songs_by_country as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['title']) ?></td>
              <td><?= htmlspecialchars($s['artist']) ?></td>
              <td><?= $s['plays'] ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <!-- Özel SQL -->
    <div class="section">
      <h3>SQL Sorguları</h3>
      <div class="sql-buttons">
        <form method="post" class="inline-form">
          <button type="submit" name="top_genres" class="sql-button">Top 5 Genre</button>
        </form>
        <form method="post" class="inline-form">
          <button type="submit" name="top_songs" class="sql-button">Top 5 Song By Country</button>
        </form>
      </div>

      <form method="post" class="custom-sql-form">
        <textarea name="custom_sql" rows="3" placeholder="SELECT * FROM USERS LIMIT 5"><?= htmlspecialchars($custom_sql) ?></textarea>
        <button type="submit" class="execute-button">Çalıştır</button>
      </form>

      <?php if($custom_error): ?>
        <p class="error-message"><?= htmlspecialchars($custom_error) ?></p>
      <?php endif; ?>

      <?php if($custom_result): ?>
        <div class="result-container">
          <h4>Sorgu Sonucu (İlk 5 Satır)</h4>
          <table class="result-table">
            <tr>
              <?php foreach(array_keys($custom_result[0]) as $col): ?>
                <th><?= htmlspecialchars($col) ?></th>
              <?php endforeach; ?>
            </tr>
            <?php foreach(array_slice($custom_result, 0, 5) as $row): ?>
              <tr>
                <?php foreach($row as $val): ?>
                  <td><?= htmlspecialchars($val) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
