<?php
$dsnHost = getenv('DB_HOST') ?: 'db';
$dbName  = getenv('DB_NAME') ?: 'sspdb';
$dbUser  = getenv('DB_USER') ?: 'root';
$dbPass  = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dsnHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbStatus = 'OK - ' . $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (PDOException $e) {
    $dbStatus = 'NG - ' . $e->getMessage();
}
?>
<!doctype html>
<meta charset="utf-8">
<title>ssp container status</title>
<h1>コンテナ分離構成 動作確認</h1>
<ul>
  <li>nginx  : <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '?') ?></li>
  <li>php-fpm: <?= PHP_VERSION ?> (<?= gethostname() ?>)</li>
  <li>mysql  : <?= htmlspecialchars($dbStatus) ?></li>
</ul>
<p><a href="/~sspuser/">/~sspuser/ を開く</a></p>
