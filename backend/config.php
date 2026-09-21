<?php
/**
 * Config do banco de dados.
 * No Docker, os valores vêm das variáveis de ambiente (ver docker-compose.yml).
 * Numa hospedagem sem controle de env vars, crie backend/config.local.php
 * (fora do git) retornando um array com as mesmas chaves pra sobrescrever.
 */

$config = [
  'db_host' => getenv('DB_HOST') ?: 'db',
  'db_name' => getenv('DB_NAME') ?: 'mova',
  'db_user' => getenv('DB_USER') ?: 'mova',
  'db_pass' => getenv('DB_PASS') ?: 'mova_dev',
];

$localConfigFile = __DIR__ . '/config.local.php';
if (is_file($localConfigFile)) {
  $config = array_merge($config, require $localConfigFile);
}

return $config;
