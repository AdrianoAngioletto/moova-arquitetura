<?php
require __DIR__ . '/_auth.php';
require_login();
require __DIR__ . '/../backend/lib/projects_repo.php';
require __DIR__ . '/../backend/lib/hero_repo.php';

$admin = current_admin();
$projects = get_projects(['status' => 'all']);
$slides = get_hero_slides(false);
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel — MOVA</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="topbar">
  <h1>Painel MOVA</h1>
  <nav>
    <a href="users">Contas</a>
    <a href="logout">Sair</a>
  </nav>
</header>

<div class="wrap">
  <?php if ($flash): ?><p class="flash"><?= htmlspecialchars($flash) ?></p><?php endif; ?>
  <p>Olá, <?= htmlspecialchars($admin['name']) ?>.</p>

  <div class="section-title">
    <h2>Slide da home (<?= count($slides) ?>)</h2>
  </div>
  <a class="btn btn-primary" href="hero_form">+ Novo slide</a>
  <div class="card-list">
    <?php if (!$slides): ?>
      <p class="empty">Nenhum slide cadastrado ainda.</p>
    <?php endif; ?>
    <?php foreach ($slides as $i => $s): ?>
      <div class="item-card" id="slide-<?= (int) $s['id'] ?>">
        <img src="../src/assets/img/<?= htmlspecialchars($s['image']) ?>" alt="">
        <div class="item-card__info">
          <strong><?= htmlspecialchars($s['title']) ?></strong>
          <span><?= $s['is_active'] ? 'Ativo' : 'Oculto' ?> · alinhado à <?= $s['align'] === 'right' ? 'direita' : 'esquerda' ?></span>
        </div>
        <div class="item-card__actions">
          <form method="post" action="hero_reorder" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <input type="hidden" name="dir" value="up">
            <button type="submit" class="btn btn-ghost" aria-label="Subir" <?= $i === 0 ? 'disabled' : '' ?>>▲</button>
          </form>
          <form method="post" action="hero_reorder" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <input type="hidden" name="dir" value="down">
            <button type="submit" class="btn btn-ghost" aria-label="Descer" <?= $i === count($slides) - 1 ? 'disabled' : '' ?>>▼</button>
          </form>
          <a class="btn btn-ghost" href="hero_form?id=<?= (int) $s['id'] ?>">Editar</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="section-title">
    <h2>Projetos (<?= count($projects) ?>)</h2>
  </div>
  <a class="btn btn-primary" href="project_form">+ Novo projeto</a>
  <div class="card-list">
    <?php if (!$projects): ?>
      <p class="empty">Nenhum projeto cadastrado ainda.</p>
    <?php endif; ?>
    <?php foreach ($projects as $p): ?>
      <div class="item-card">
        <img src="../src/assets/img/<?= htmlspecialchars($p['cover']) ?>" alt="">
        <div class="item-card__info">
          <strong><?= htmlspecialchars($p['title']) ?></strong>
          <span>
            <?= htmlspecialchars($p['category_label']) ?> · <?= count($p['images']) ?> foto(s)
            <?php if ($p['status'] === 'draft'): ?><span class="badge is-draft">Rascunho</span><?php endif; ?>
          </span>
        </div>
        <div class="item-card__actions">
          <a class="btn btn-ghost" href="project_form?id=<?= (int) $p['id'] ?>">Editar</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
