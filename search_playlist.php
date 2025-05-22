<?php
$mysqli = new mysqli('localhost','root','','fatma_terzi');
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$playlists = [];
if ($q !== '') {
    $stmt = $mysqli->prepare("SELECT playlist_id, title FROM PLAYLISTS WHERE title LIKE ? ORDER BY title ASC LIMIT 10");
    $like = $q . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        $playlists[] = $row;
    }
    $stmt->close();
}
header('Content-Type: application/json');
echo json_encode($playlists); 