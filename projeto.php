<?php
$projects = require __DIR__ . '/backend/data/projects.php';

$slug = isset($_SERVER['QUERY_STRING']) ? urldecode($_SERVER['QUERY_STRING']) : '';
$project = null;
foreach ($projects as $p) {
  if ($p['slug'] === $slug) { $project = $p; break; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $project ? htmlspecialchars($project['title']) . ' — MOVA Arquitetura' : 'Projeto não encontrado — MOVA Arquitetura' ?></title>
<meta name="description" content="<?= $project ? htmlspecialchars($project['summary']) : 'Projeto não encontrado.' ?>">
<meta name="theme-color" content="#0E0E0F">
<meta name="robots" content="noindex, nofollow">

<meta property="og:type" content="website">
<meta property="og:title" content="<?= $project ? htmlspecialchars($project['title']) : 'Projeto não encontrado' ?> — MOVA Arquitetura">
<meta property="og:image" content="src/assets/img/<?= $project ? htmlspecialchars($project['cover']) : '' ?>">
<meta property="og:locale" content="pt_BR">

<link rel="icon" href="src/assets/logo/favicon.svg" type="image/svg+xml">
<link rel="preload" href="src/assets/fonts/Ranade-Light.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="src/assets/fonts/Chillax-Light.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="src/styles/style.css">
</head>
<body>

<?php require __DIR__ . '/src/views/header.php'; ?>

<?php if ($project): ?>
<!-- Projeto -->
<section class="section proj-detail">
  <div class="wrap">
    <header class="sec-head">
      <p class="eyebrow reveal"><a class="link" href="projetos">Projetos</a> — <?= htmlspecialchars($project['category_label']) ?></p>
      <h2 class="h2 reveal"><?= htmlspecialchars($project['title']) ?></h2>
      <p class="sec-head__lead reveal"><?= htmlspecialchars($project['summary']) ?></p>
    </header>

    <div class="proj-detail__gallery">
      <?php foreach ($project['images'] as $img): ?>
      <figure class="proj-detail__img">
        <img src="src/assets/img/<?= htmlspecialchars($img['file']) ?>" alt="<?= htmlspecialchars($img['alt']) ?>" loading="lazy">
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php else: ?>
<!-- Projeto não encontrado -->
<section class="section proj-detail">
  <div class="wrap">
    <header class="sec-head">
      <p class="eyebrow reveal">Projetos</p>
      <h2 class="h2 reveal">Projeto não encontrado.</h2>
      <p class="sec-head__lead reveal">Esse projeto não existe ou foi removido. <a class="link" href="projetos">Volte para a lista de projetos</a>.</p>
    </header>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/src/views/footer.php'; ?>
</body>
</html>
