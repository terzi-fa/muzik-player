<?php
/**
 * Author: Fatma Terzi (20190702041)
 * search_artist.php – Part III: Sanatçı arama
 */
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.html");

$mysqli = new mysqli('localhost','root','','fatma_terzi');
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$artists = [];
if ($q !== '') {
    $stmt = $mysqli->prepare("SELECT artist_id, name FROM ARTISTS WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
    $like = $q . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        $artists[] = $row;
    }
    $stmt->close();
}
header('Content-Type: application/json');
echo json_encode($artists);
