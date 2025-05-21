<?php
/**
 * Author: Fatma Terzi (20190702041)
 * search_artist.php – Part III: Sanatçı arama
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");

$q = trim($_GET['q'] ?? '');
if(!$q){
  header("Location: homepage.php");
  exit;
}

$mysqli = new mysqli('localhost','root','','fatma_terzi');
$stmt = $mysqli->prepare("SELECT artist_id FROM ARTISTS WHERE name LIKE ?");
$like = "%$q%";
$stmt->bind_param("s",$like);
$stmt->execute();
$stmt->bind_result($aid);
if($stmt->fetch()){
  header("Location: artistpage.php?id=$aid");
  exit;
}

header("Location: homepage.php?error=".urlencode("Sanatçı bulunamadı"));
