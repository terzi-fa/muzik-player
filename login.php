<?php
/**
 * Author: Fatma Terzi (20190702041)
 * login.php – Part I/III: Kullanıcı doğrulama
 */
session_start();

$host = 'localhost';
$db   = 'fatma_terzi';
$user = 'root';
$pass = '';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn,$user,$pass,[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    header("Location: login.html?error=".urlencode("DB bağlantı hatası"));
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT user_id,name,username,password FROM USERS WHERE username=?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if($user && password_verify($password,$user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name']    = $user['name'];
    header("Location: homepage.php");
    exit;
} else {
    header("Location: login.html?error=".urlencode("Geçersiz kullanıcı veya şifre"));
    exit;
}
