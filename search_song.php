<?php
/**
 * Author: Fatma Terzi (20190702041)
 * search_song.php – Part III: Şarkı arama
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");

$mysqli = new mysqli('localhost','root','','fatma_terzi');
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$songs = [];
if ($q !== '') {
    $stmt = $mysqli->prepare("SELECT song_id, title FROM SONGS WHERE title LIKE ? ORDER BY title ASC LIMIT 10");
    $like = $q . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        $songs[] = $row;
    }
    $stmt->close();
}
header('Content-Type: application/json');
echo json_encode($songs);
