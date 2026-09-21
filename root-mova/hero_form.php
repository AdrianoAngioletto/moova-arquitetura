<?php
require __DIR__ . '/_auth.php';
require_login();
require __DIR__ . '/../backend/lib/hero_repo.php';
require __DIR__ . '/../backend/lib/uploads.php';

// Limite baseado no maior título/descrição já usado nos slides atuais da home,
// pra nenhum texto novo quebrar o layout do hero.
const HERO_TITLE_MAX = 27;
const HERO_DESC_MAX = 45;

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$slide = $id ? get_hero_slide_by_id($id) : null;
if ($id && !$slide) {
  flash_set('Slide não encontrado.');
  header('Location: index');
  exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $align = ($_POST['align'] ?? 'left') === 'right' ? 'right' : 'left';
  $isActive = isset($_POST['is_active']) ? 1 : 0;

  if ($title === '' || $description === '') {
    $error = 'Preencha título e descrição.';
  } elseif (mb_strlen($title) > HERO_TITLE_MAX) {
    $error = 'Título muito grande — máximo de ' . HERO_TITLE_MAX . ' caracteres (tem ' . mb_strlen($title) . ').';
  } elseif (mb_strlen($description) > HERO_DESC_MAX) {
    $error = 'Descrição muito grande — máximo de ' . HERO_DESC_MAX . ' caracteres (tem ' . mb_strlen($description) . ').';
  }

  $imagePath = $slide['image'] ?? null;
  $oldImageToDelete = null;
  if (!$error && !empty($_FILES['image']['name'])) {
    $upload = upload_image($_FILES['image']);
    if (!$upload['ok']) {
      $error = $upload['error'];
    } else {
      if ($imagePath) {
        $oldImageToDelete = $imagePath;
      }
      $imagePath = $upload['path'];
    }
  }
  if (!$error && !$imagePath) {
    $error = 'Envie uma foto pro slide.';
  }

  if (!$error) {
    $pdo = get_pdo();
    if ($slide) {
      $stmt = $pdo->prepare(
        'UPDATE hero_slides SET image_path=:image_path, title=:title, description=:description,
         align=:align, is_active=:is_active WHERE id=:id'
      );
      $stmt->execute([
        'image_path' => $imagePath, 'title' => $title, 'description' => $description,
        'align' => $align, 'is_active' => $isActive, 'id' => $slide['id'],
      ]);
      if ($oldImageToDelete) {
        delete_image_file($oldImageToDelete);
      }
      flash_set('Slide atualizado.');
    } else {
      $nextOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM hero_slides')->fetchColumn();
      $stmt = $pdo->prepare(
        'INSERT INTO hero_slides (image_path, title, description, align, sort_order, is_active)
         VALUES (:image_path, :title, :description, :align, :sort_order, :is_active)'
      );
      $stmt->execute([
        'image_path' => $imagePath, 'title' => $title, 'description' => $description,
        'align' => $align, 'sort_order' => $nextOrder, 'is_active' => $isActive,
      ]);
      flash_set('Slide criado.');
    }
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
<title><?= $slide ? 'Editar slide' : 'Novo slide' ?> — Painel MOVA</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="topbar">
  <h1><?= $slide ? 'Editar slide' : 'Novo slide' ?></h1>
  <nav><a href="index">Voltar</a></nav>
</header>
<div class="wrap">
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <form class="stack" method="post" enctype="multipart/form-data" data-resize-images>
    <?= csrf_field() ?>
    <?php if ($slide): ?><input type="hidden" name="id" value="<?= (int) $slide['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>Foto do slide</label>
      <?php if ($slide): ?>
        <img src="../src/assets/img/<?= htmlspecialchars($slide['image']) ?>" alt="" style="width:100%;max-width:280px;border-radius:8px;margin-bottom:8px">
      <?php endif; ?>
      <input type="file" name="image" accept="image/*" <?= $slide ? '' : 'required' ?>>
      <small>Tire a foto direto do celular ou escolha da galeria. <?= $slide ? 'Deixe em branco pra manter a atual.' : '' ?></small>
    </div>

    <div class="field">
      <label for="title">Título (máximo <?= HERO_TITLE_MAX ?> caracteres)</label>
      <input type="text" id="title" name="title" required maxlength="<?= HERO_TITLE_MAX ?>"
             value="<?= htmlspecialchars($slide['title'] ?? '') ?>" data-counter="title-count">
      <small><span id="title-count">0</span>/<?= HERO_TITLE_MAX ?></small>
    </div>

    <div class="field">
      <label for="description">Descrição (máximo <?= HERO_DESC_MAX ?> caracteres)</label>
      <textarea id="description" name="description" required maxlength="<?= HERO_DESC_MAX ?>"
                data-counter="desc-count"><?= htmlspecialchars($slide['description'] ?? '') ?></textarea>
      <small><span id="desc-count">0</span>/<?= HERO_DESC_MAX ?></small>
    </div>

    <div class="field">
      <label for="align">Alinhamento do texto</label>
      <select id="align" name="align">
        <option value="left" <?= ($slide['align'] ?? 'left') === 'left' ? 'selected' : '' ?>>Esquerda</option>
        <option value="right" <?= ($slide['align'] ?? '') === 'right' ? 'selected' : '' ?>>Direita</option>
      </select>
    </div>

    <div class="field">
      <label><input type="checkbox" name="is_active" <?= ($slide['is_active'] ?? true) ? 'checked' : '' ?>> Ativo (aparece no site)</label>
    </div>

    <?php if (!$slide): ?>
      <p><small>A ordem do slide é definida na lista do painel, com as setas de subir/descer.</small></p>
    <?php endif; ?>

    <div class="btn-row">
      <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
  </form>

  <?php if ($slide): ?>
  <form method="post" action="hero_delete" onsubmit="return confirm('Excluir este slide?')" style="margin-top:12px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
    <button type="submit" class="btn btn-danger">Excluir slide</button>
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
