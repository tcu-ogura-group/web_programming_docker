<?php
$dsnHost = getenv('DB_HOST');
$dbName  = getenv('DB_NAME');
$dbUser  = getenv('DB_USER');
$dbPass  = getenv('DB_PASSWORD');

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
<h1>ssp 動作確認</h1>
<ul>
  <li>apache : <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '?') ?></li>
  <li>php    : <?= PHP_VERSION ?> (<?= php_sapi_name() ?> / <?= gethostname() ?>)</li>
  <li>mysql  : <?= htmlspecialchars($dbStatus) ?></li>
</ul>
<p><a href="/~sspuser/">/~sspuser/ を開く</a></p>
