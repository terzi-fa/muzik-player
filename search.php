<?php
/**
 * Author: Fatma Terzi (20190702041)
 * search.php – Part III: “Playlist veya şarkı” arama
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");

$q = trim($_GET['q'] ?? '');
if(!$q) {
  header("Location: homepage.php");
  exit;
}

$mysqli = new mysqli('localhost','root','','fatma_terzi');

// Playlist ara
$stmt = $mysqli->prepare("SELECT playlist_id FROM PLAYLISTS WHERE title LIKE ?");
$like = "%$q%";
$stmt->bind_param("s",$like);
$stmt->execute();
$stmt->bind_result($pid);
if($stmt->fetch()){
  header("Location: playlistpage.php?id=$pid");
  exit;
}
$stmt->close();

// Şarkı ara
$stmt = $mysqli->prepare("SELECT song_id FROM SONGS WHERE title LIKE ?");
$stmt->bind_param("s",$like);
$stmt->execute();
$stmt->bind_result($sid);
if($stmt->fetch()){
  header("Location: currentmusic.php?id=$sid");
  exit;
}

header("Location: homepage.php?error=".urlencode("Bulunamadı"));
