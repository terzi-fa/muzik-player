<?php
/**
 * Author: Fatma Terzi (20190702041)
 * add_playlist.php – Part III: Yeni playlist ekleme
 */
session_start();
if(!isset($_SESSION['user_id'])){
  header("Location: login.html");
  exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $img   = trim($_POST['image'] ?? '');
  if($title){
    $mysqli = new mysqli('localhost','root','','fatma_terzi');
    $stmt = $mysqli->prepare("
      INSERT INTO PLAYLISTS (user_id,title,description,date_created,image)
      VALUES (?,?,?,?,CURDATE(),?)
    ");
    $stmt->bind_param("isss", $_SESSION['user_id'], $title, $desc, $img);
    $stmt->execute();
    header("Location: homepage.php");
    exit;
  } else {
    $error = "Başlık boş olamaz.";
  }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Yeni Playlist Ekle</title>
  <style>/* homepage ile aynı stil */</style>
</head>
<body>
  <div class="container">
    <a href="homepage.php">← Anasayfa</a>
    <h2>Yeni Playlist Oluştur</h2>
    <?php if(!empty($error)): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
      <label>Başlık:</label>
      <input type="text" name="title" required>
      <label>Açıklama:</label>
      <textarea name="description"></textarea>
      <label>Resim URL:</label>
      <input type="text" name="image">
      <button type="submit">Oluştur</button>
    </form>
  </div>
</body>
</html>
