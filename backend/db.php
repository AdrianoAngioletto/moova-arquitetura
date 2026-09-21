<?php
/**
 * Conexão PDO única (singleton) com o MySQL.
 */

function get_pdo(): PDO {
  static $pdo = null;
  if ($pdo !== null) {
    return $pdo;
  }

  $config = require __DIR__ . '/config.php';
  $dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $config['db_host'],
    $config['db_name']
  );

  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  // O MySQL reinicia internamente logo após o boot (fase de init do container),
  // podendo recusar conexão por um instante mesmo já "healthy". Poucas tentativas
  // curtas cobrem essa janela sem mascarar uma falha de conexão real.
  $attempts = 5;
  for ($i = 1; $i <= $attempts; $i++) {
    try {
      $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
      return $pdo;
    } catch (PDOException $e) {
      if ($i === $attempts) {
        throw $e;
      }
      usleep(300000);
    }
  }
}
