<?php
require __DIR__ . '/_auth.php';
require_login();

$me = current_admin();
$pdo = get_pdo();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$name || strlen($password) < 8) {
      $error = 'Preencha e-mail, nome e uma senha com pelo menos 8 caracteres.';
    } else {
      $exists = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE email = :email');
      $exists->execute(['email' => $email]);
      if ($exists->fetchColumn() > 0) {
        $error = 'Já existe uma conta com esse e-mail.';
      } else {
        $stmt = $pdo->prepare('INSERT INTO admin_users (email, name, password_hash) VALUES (:email, :name, :hash)');
        $stmt->execute(['email' => $email, 'name' => $name, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
        flash_set('Conta criada para ' . $name . '.');
        header('Location: users');
        exit;
      }
    }
  }

  if ($action === 'reset_password') {
    $id = (int) ($_POST['id'] ?? 0);
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 8) {
      $error = 'A nova senha precisa ter pelo menos 8 caracteres.';
    } else {
      $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id')
        ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
      flash_set('Senha atualizada.');
      header('Location: users');
      exit;
    }
  }

  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $total = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($id === (int) $me['id']) {
      $error = 'Você não pode excluir a própria conta enquanto estiver logado nela.';
    } elseif ($total <= 1) {
      $error = 'Precisa existir pelo menos uma conta de admin.';
    } else {
      $pdo->prepare('DELETE FROM admin_users WHERE id = :id')->execute(['id' => $id]);
      flash_set('Conta removida.');
      header('Location: users');
      exit;
    }
  }
}

$admins = $pdo->query('SELECT id, email, name, created_at FROM admin_users ORDER BY name ASC')->fetchAll();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contas de admin — Painel MOVA</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="topbar">
  <h1>Contas de admin</h1>
  <nav><a href="index">Voltar</a></nav>
</header>
<div class="wrap">
  <?php if ($flash): ?><p class="flash"><?= htmlspecialchars($flash) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <div class="section-title"><h2>Nova conta</h2></div>
  <form class="stack card" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="field">
      <label for="new_name">Nome</label>
      <input type="text" id="new_name" name="name" required>
    </div>
    <div class="field">
      <label for="new_email">E-mail</label>
      <input type="email" id="new_email" name="email" required>
    </div>
    <div class="field">
      <label for="new_password">Senha</label>
      <input type="password" id="new_password" name="password" required minlength="8">
      <small>Pelo menos 8 caracteres.</small>
    </div>
    <button type="submit" class="btn btn-primary">Criar conta</button>
  </form>

  <div class="section-title"><h2>Contas existentes (<?= count($admins) ?>)</h2></div>
  <div class="card-list">
    <?php foreach ($admins as $a): ?>
      <div class="card">
        <strong><?= htmlspecialchars($a['name']) ?></strong>
        <?php if ((int) $a['id'] === (int) $me['id']): ?><span class="badge">Você</span><?php endif; ?>
        <div style="color:var(--muted);font-size:.88rem;margin:4px 0 12px"><?= htmlspecialchars($a['email']) ?></div>

        <details>
          <summary style="cursor:pointer;color:var(--acc-dark);font-size:.9rem">Trocar senha</summary>
          <form class="stack" method="post" style="margin-top:10px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <div class="field">
              <input type="password" name="password" placeholder="Nova senha" required minlength="8">
            </div>
            <button type="submit" class="btn">Salvar nova senha</button>
          </form>
        </details>

        <?php if ((int) $a['id'] !== (int) $me['id']): ?>
          <form method="post" onsubmit="return confirm('Remover a conta de <?= htmlspecialchars(addslashes($a['name'])) ?>?')" style="margin-top:10px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <button type="submit" class="btn btn-danger">Excluir conta</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
