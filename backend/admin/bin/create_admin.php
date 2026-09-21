<?php
/**
 * Cria (ou atualiza a senha de) uma conta de admin.
 * Necessário pra criar a PRIMEIRA conta — depois disso dá pra usar a tela
 * root-mova/users.php pra criar as demais.
 *
 * Uso: docker compose exec site php backend/admin/bin/create_admin.php email nome senha
 */

require __DIR__ . '/../../db.php';

if ($argc !== 4) {
  fwrite(STDERR, "Uso: php create_admin.php email nome senha\n");
  exit(1);
}

[, $email, $name, $password] = $argv;

if (strlen($password) < 8) {
  fwrite(STDERR, "A senha precisa ter pelo menos 8 caracteres.\n");
  exit(1);
}

$pdo = get_pdo();
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
  'INSERT INTO admin_users (email, name, password_hash) VALUES (:email, :name, :hash)
   ON DUPLICATE KEY UPDATE name = :name2, password_hash = :hash2'
);
$stmt->execute([
  'email' => $email,
  'name'  => $name,
  'hash'  => $hash,
  'name2' => $name,
  'hash2' => $hash,
]);

echo "OK: conta de admin pronta para $email\n";
