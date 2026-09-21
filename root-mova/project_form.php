<?php
require __DIR__ . '/_auth.php';
require_login();
require __DIR__ . '/../backend/lib/projects_repo.php';
require __DIR__ . '/../backend/lib/uploads.php';

// Mesmo padrão de tamanho usado nos slides do hero, pra não quebrar o card em /projetos.
const PROJECT_TITLE_MAX = 40;
const PROJECT_SUMMARY_MAX = 150;

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$project = $id ? get_project_by_id($id) : null;
if ($id && !$project) {
  flash_set('Projeto não encontrado.');
  header('Location: index');
  exit;
}

$categories = ['corporativo' => 'Corporativo', 'residencial' => 'Residencial', 'studio' => 'Studio'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $pdo = get_pdo();

  $title = trim($_POST['title'] ?? '');
  $category = $_POST['category'] ?? '';
  $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
  $summary = trim($_POST['summary'] ?? '');

  if ($title === '' || $summary === '' || !isset($categories[$category])) {
    $error = 'Preencha título, categoria e descrição.';
  } elseif (mb_strlen($title) > PROJECT_TITLE_MAX) {
    $error = 'Nome muito grande — máximo de ' . PROJECT_TITLE_MAX . ' caracteres (tem ' . mb_strlen($title) . ').';
  } elseif (mb_strlen($summary) > PROJECT_SUMMARY_MAX) {
    $error = 'Descrição muito grande — máximo de ' . PROJECT_SUMMARY_MAX . ' caracteres (tem ' . mb_strlen($summary) . ').';
  }

  $coverPath = $project['cover'] ?? null;
  $oldCoverToDelete = null;
  if (!$error && !empty($_FILES['cover']['name'])) {
    $upload = upload_image($_FILES['cover']);
    if (!$upload['ok']) {
      $error = $upload['error'];
    } else {
      if ($coverPath) {
        $oldCoverToDelete = $coverPath;
      }
      $coverPath = $upload['path'];
    }
  }
  if (!$error && !$coverPath) {
    $error = 'Envie uma foto de capa.';
  }

  if (!$error) {
    if ($project) {
      $projectId = $project['id'];
      $stmt = $pdo->prepare(
        'UPDATE projects SET title=:title, category=:category, summary=:summary,
         cover=:cover, status=:status WHERE id=:id'
      );
      $stmt->execute([
        'title' => $title, 'category' => $category, 'summary' => $summary,
        'cover' => $coverPath, 'status' => $status, 'id' => $projectId,
      ]);
      if ($oldCoverToDelete) {
        delete_image_file($oldCoverToDelete);
      }
    } else {
      $slug = slugify_unique($title);
      $stmt = $pdo->prepare(
        'INSERT INTO projects (slug, title, category, summary, cover, status, sort_order)
         VALUES (:slug, :title, :category, :summary, :cover, :status, 0)'
      );
      $stmt->execute([
        'slug' => $slug, 'title' => $title, 'category' => $category,
        'summary' => $summary, 'cover' => $coverPath, 'status' => $status,
      ]);
      $projectId = (int) $pdo->lastInsertId();
    }

    // Fotos existentes: atualizar legenda, ou excluir as marcadas (a ordem é
    // mexida à parte, pelas setas de subir/descer — ver project_image_reorder.php).
    if ($project) {
      $existingIds = array_column($project['images'], 'id');
      foreach ($existingIds as $imgId) {
        if (!empty($_POST['delete_image'][$imgId])) {
          $row = $pdo->prepare('SELECT path FROM project_images WHERE id=:id AND project_id=:pid');
          $row->execute(['id' => $imgId, 'pid' => $projectId]);
          $path = $row->fetchColumn();
          if ($path) {
            $pdo->prepare('DELETE FROM project_images WHERE id=:id')->execute(['id' => $imgId]);
            delete_image_file($path);
          }
          continue;
        }
        $alt = trim($_POST['image_alt'][$imgId] ?? '');
        $pdo->prepare('UPDATE project_images SET alt=:alt WHERE id=:id AND project_id=:pid')
          ->execute(['alt' => $alt, 'id' => $imgId, 'pid' => $projectId]);
      }
    }

    // Novas fotos da galeria.
    if (!empty($_FILES['new_images']['name'][0] ?? null)) {
      $count = count($_FILES['new_images']['name']);
      $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM project_images WHERE project_id = :pid');
      $orderStmt->execute(['pid' => $projectId]);
      $nextOrder = (int) $orderStmt->fetchColumn();

      $insertImg = $pdo->prepare(
        'INSERT INTO project_images (project_id, path, alt, sort_order) VALUES (:pid, :path, :alt, :o)'
      );
      for ($i = 0; $i < $count; $i++) {
        if ($_FILES['new_images']['error'][$i] !== UPLOAD_ERR_OK) {
          continue;
        }
        $file = [
          'name' => $_FILES['new_images']['name'][$i],
          'type' => $_FILES['new_images']['type'][$i],
          'tmp_name' => $_FILES['new_images']['tmp_name'][$i],
          'error' => $_FILES['new_images']['error'][$i],
          'size' => $_FILES['new_images']['size'][$i],
        ];
        $upload = upload_image($file);
        if ($upload['ok']) {
          $insertImg->execute([
            'pid' => $projectId, 'path' => $upload['path'],
            'alt' => $title . ' — foto', 'o' => $nextOrder++,
          ]);
        }
      }
    }

    flash_set($project ? 'Projeto atualizado.' : 'Projeto criado.');
    header('Location: index');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $project ? 'Editar projeto' : 'Novo projeto' ?> — Painel MOVA</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="topbar">
  <h1><?= $project ? 'Editar projeto' : 'Novo projeto' ?></h1>
  <nav><a href="index">Voltar</a></nav>
</header>
<div class="wrap">
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <form class="stack" method="post" enctype="multipart/form-data" data-resize-images>
    <?= csrf_field() ?>
    <?php if ($project): ?><input type="hidden" name="id" value="<?= (int) $project['id'] ?>"><?php endif; ?>

    <div class="field">
      <label for="title">Nome da empresa (máximo <?= PROJECT_TITLE_MAX ?> caracteres)</label>
      <input type="text" id="title" name="title" required maxlength="<?= PROJECT_TITLE_MAX ?>"
             value="<?= htmlspecialchars($project['title'] ?? '') ?>" data-counter="title-count">
      <small><span id="title-count">0</span>/<?= PROJECT_TITLE_MAX ?></small>
    </div>

    <div class="field">
      <label for="category">Categoria</label>
      <select id="category" name="category">
        <?php foreach ($categories as $key => $label): ?>
          <option value="<?= $key ?>" <?= ($project['category'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="published" <?= ($project['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publicado (aparece no site)</option>
        <option value="draft" <?= ($project['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho (fica escondido)</option>
      </select>
    </div>

    <div class="field">
      <label for="summary">Descrição (máximo <?= PROJECT_SUMMARY_MAX ?> caracteres)</label>
      <textarea id="summary" name="summary" required maxlength="<?= PROJECT_SUMMARY_MAX ?>"
                data-counter="summary-count"><?= htmlspecialchars($project['summary'] ?? '') ?></textarea>
      <small><span id="summary-count">0</span>/<?= PROJECT_SUMMARY_MAX ?></small>
    </div>

    <div class="field">
      <label>Foto de capa (aparece no card em /projetos)</label>
      <?php if ($project): ?>
        <img src="../src/assets/img/<?= htmlspecialchars($project['cover']) ?>" alt="" style="width:100%;max-width:280px;border-radius:8px;margin-bottom:8px">
      <?php endif; ?>
      <input type="file" name="cover" accept="image/*" <?= $project ? '' : 'required' ?>>
      <small><?= $project ? 'Deixe em branco pra manter a atual.' : '' ?></small>
    </div>

    <?php if ($project && $project['images']): ?>
    <div class="field">
      <label>Fotos da galeria (aparecem na página da empresa)</label>
      <div class="thumb-list">
        <?php foreach ($project['images'] as $i => $img): ?>
          <div class="thumb-row">
            <img src="../src/assets/img/<?= htmlspecialchars($img['file']) ?>" alt="">
            <input type="text" name="image_alt[<?= (int) $img['id'] ?>]" value="<?= htmlspecialchars($img['alt']) ?>" placeholder="Legenda (alt)">
            <label style="display:flex;align-items:center;gap:4px;font-size:.8rem">
              <input type="checkbox" name="delete_image[<?= (int) $img['id'] ?>]" value="1"> Excluir
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <p><small>Pra reordenar as fotos, salve esta página primeiro — as setas de mover aparecem logo abaixo.</small></p>
    </div>
    <?php endif; ?>

    <div class="field">
      <label>Adicionar fotos novas à galeria</label>
      <input type="file" name="new_images[]" accept="image/*" multiple>
      <small>Pode selecionar várias fotos de uma vez, direto da galeria do celular.</small>
    </div>

    <div class="btn-row">
      <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
  </form>

  <?php if ($project && $project['images']): ?>
  <div class="section-title"><h2>Ordem das fotos</h2></div>
  <div class="thumb-list">
    <?php foreach ($project['images'] as $i => $img): ?>
      <div class="thumb-row" id="img-<?= (int) $img['id'] ?>">
        <img src="../src/assets/img/<?= htmlspecialchars($img['file']) ?>" alt="">
        <span style="flex:1;font-size:.85rem;color:var(--muted)"><?= htmlspecialchars($img['alt'] ?: 'sem legenda') ?></span>
        <form method="post" action="project_image_reorder" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
          <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
          <input type="hidden" name="dir" value="up">
          <button type="submit" class="btn btn-ghost" aria-label="Subir" <?= $i === 0 ? 'disabled' : '' ?>>▲</button>
        </form>
        <form method="post" action="project_image_reorder" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
          <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
          <input type="hidden" name="dir" value="down">
          <button type="submit" class="btn btn-ghost" aria-label="Descer" <?= $i === count($project['images']) - 1 ? 'disabled' : '' ?>>▼</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($project): ?>
  <form method="post" action="project_delete" onsubmit="return confirm('Excluir este projeto e todas as fotos dele?')" style="margin-top:12px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
    <button type="submit" class="btn btn-danger">Excluir projeto</button>
  </form>
  <?php endif; ?>
</div>
<script src="assets/admin.js"></script>
<script>
  document.querySelectorAll('[data-counter]').forEach(function (el) {
    var out = document.getElementById(el.dataset.counter);
    function update() { out.textContent = el.value.length; }
    el.addEventListener('input', update);
    update();
  });
</script>
</body>
</html>
