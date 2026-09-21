<?php
require __DIR__ . '/_auth.php';

if (!empty($_SESSION['admin_id'])) {
  header('Location: index');
  exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = get_pdo()->prepare('SELECT * FROM admin_users WHERE email = :email');
  $stmt->execute(['email' => $email]);
  $admin = $stmt->fetch();

  if ($admin && password_verify($password, $admin['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    header('Location: index');
    exit;
  }

  $error = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar — Painel MOVA</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="login-wrap">
  <h1>Painel MOVA</h1>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form class="stack" method="post">
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" required autofocus autocomplete="username">
    </div>
    <div class="field">
      <label for="password">Senha</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary">Entrar</button>
  </form>
</div>
</body>
</html>
