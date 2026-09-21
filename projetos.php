<?php
$projects = require __DIR__ . '/backend/data/projects.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Projetos — MOVA Arquitetura</title>
<meta name="description" content="Portfólio completo de projetos da MOVA Arquitetura — arquitetura corporativa de alto padrão.">
<meta name="theme-color" content="#0E0E0F">
<meta name="robots" content="noindex, nofollow">

<meta property="og:type" content="website">
<meta property="og:title" content="Projetos — MOVA Arquitetura">
<meta property="og:description" content="Portfólio completo de projetos da MOVA Arquitetura.">
<meta property="og:image" content="src/assets/img/escritorio-mesa-plantas.jpg">
<meta property="og:locale" content="pt_BR">

<link rel="icon" href="src/assets/logo/favicon.svg" type="image/svg+xml">
<link rel="preload" href="src/assets/fonts/Ranade-Light.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="src/assets/fonts/Chillax-Light.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="src/styles/style.css?v=5">
</head>
<body>

<?php require __DIR__ . '/src/views/header.php'; ?>

<!-- Projetos -->
<section class="section projects-page">
  <div class="wrap">
    <header class="sec-head">
      <p class="eyebrow reveal">Projetos</p>
      <h2 class="h2 reveal">Projetos que transformam<br>espaços e experiências.</h2>
      <p class="sec-head__lead reveal">Uma seleção de projetos corporativos, residenciais e studios desenvolvidos pela MOVA.</p>
    </header>

    <div class="proj-toolbar reveal">
      <div class="proj-filter-wrap">
        <div class="proj-filter" id="projFilter" role="tablist" aria-label="Filtrar projetos">
          <button class="proj-filter__btn is-active" type="button" data-filter="all">Todos</button>
          <button class="proj-filter__btn" type="button" data-filter="corporativo">Corporativo</button>
          <button class="proj-filter__btn" type="button" data-filter="residencial">Residencial</button>
          <button class="proj-filter__btn" type="button" data-filter="studio">Studios</button>
        </div>
        <span class="proj-filter__hint" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
        </span>
      </div>
      <div class="proj-search">
        <input type="search" id="projSearch" placeholder="Buscar" aria-label="Buscar">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      </div>
    </div>

    <div class="proj-grid" id="projGrid">
      <?php foreach ($projects as $p): ?>
      <figure class="proj-card reveal" data-category="<?= htmlspecialchars($p['category']) ?>">
        <a href="projeto?<?= urlencode($p['slug']) ?>">
          <img src="src/assets/img/<?= htmlspecialchars($p['cover']) ?>" alt="<?= htmlspecialchars($p['images'][0]['alt'] ?? $p['title']) ?>" loading="lazy">
          <figcaption><span><?= htmlspecialchars($p['category_label']) ?></span><b><?= htmlspecialchars($p['title']) ?></b></figcaption>
        </a>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/src/views/footer.php'; ?>
</body>
</html>
