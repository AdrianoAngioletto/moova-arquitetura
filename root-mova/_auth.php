<?php
/**
 * Sessão + CSRF do painel. Todo arquivo protegido começa com:
 *   require __DIR__ . '/_auth.php';
 *   require_login();
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../backend/db.php';

function require_login(): void {
  if (empty($_SESSION['admin_id'])) {
    header('Location: login');
    exit;
  }
}

function current_admin(): ?array {
  static $admin = null;
  if ($admin !== null) {
    return $admin ?: null;
  }
  if (empty($_SESSION['admin_id'])) {
    $admin = false;
    return null;
  }
  $stmt = get_pdo()->prepare('SELECT id, email, name FROM admin_users WHERE id = :id');
  $stmt->execute(['id' => $_SESSION['admin_id']]);
  $row = $stmt->fetch();
  $admin = $row ?: false;
  return $admin ?: null;
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_field(): string {
  return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check(): void {
  $token = $_POST['csrf'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(403);
    exit('Sessão expirada, volte e tente novamente.');
  }
}

function flash_set(string $message): void {
  $_SESSION['flash'] = $message;
}

function flash_get(): ?string {
  if (empty($_SESSION['flash'])) {
    return null;
  }
  $msg = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $msg;
}
