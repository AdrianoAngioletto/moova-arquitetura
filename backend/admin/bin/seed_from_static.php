<?php
/**
 * Script de uma vez só: importa os dados estáticos existentes
 * (backend/data/projects.php + os 3 slides do hero, transcritos manualmente
 * a partir de index.html) pras tabelas novas do MySQL.
 *
 * Uso: docker compose exec site php backend/admin/bin/seed_from_static.php
 */

require __DIR__ . '/../../db.php';

$pdo = get_pdo();

$existing = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
if ($existing > 0) {
  fwrite(STDERR, "Já existem $existing projeto(s) no banco — abortando pra não duplicar.\n");
  fwrite(STDERR, "Se quiser reimportar do zero: docker compose down -v && docker compose up -d --build\n");
  exit(1);
}

$projects = require __DIR__ . '/../../data/projects.php';

// Slides do hero, transcritos de index.html (não existiam em nenhum arquivo PHP).
$heroSlides = [
  [
    'image_path'  => 'hero-escritorio-vista.jpg',
    'title'       => 'Transforme seu espaço',
    'description' => 'Reformas residenciais e corporativas que unem arquitetura, funcionalidade e sofisticação para criar ambientes únicos, pensados em cada detalhe.',
    'align'       => 'left',
  ],
  [
    'image_path'  => 'hero-cozinha-adega.jpg',
    'title'       => 'Projetos sob medida.',
    'description' => 'Criamos soluções exclusivas para residências e escritórios, planejadas em cada detalhe para traduzir suas necessidades em um projeto único.',
    'align'       => 'right',
  ],
  [
    'image_path'  => 'hero-lounge-copa.jpg',
    'title'       => 'Planejamento e Gerenciamento',
    'description' => 'Coordenamos todas as etapas da obra, do planejamento à execução, garantindo organização, eficiência e controle em todo o processo.',
    'align'       => 'left',
  ],
];

$pdo->beginTransaction();

try {
  $insertProject = $pdo->prepare(
    'INSERT INTO projects (slug, title, category, summary, cover, status, sort_order)
     VALUES (:slug, :title, :category, :summary, :cover, "published", :sort_order)'
  );
  $insertImage = $pdo->prepare(
    'INSERT INTO project_images (project_id, path, alt, sort_order)
     VALUES (:project_id, :path, :alt, :sort_order)'
  );

  $projectCount = 0;
  $imageCount = 0;

  foreach ($projects as $i => $p) {
    $insertProject->execute([
      'slug'       => $p['slug'],
      'title'      => $p['title'],
      'category'   => $p['category'],
      'summary'    => $p['summary'],
      'cover'      => $p['cover'],
      'sort_order' => $i,
    ]);
    $projectId = (int) $pdo->lastInsertId();
    $projectCount++;

    foreach ($p['images'] as $j => $img) {
      $insertImage->execute([
        'project_id' => $projectId,
        'path'       => $img['file'],
        'alt'        => $img['alt'],
        'sort_order' => $j,
      ]);
      $imageCount++;
    }
  }

  $insertSlide = $pdo->prepare(
    'INSERT INTO hero_slides (image_path, title, description, align, sort_order, is_active)
     VALUES (:image_path, :title, :description, :align, :sort_order, 1)'
  );
  $slideCount = 0;
  foreach ($heroSlides as $i => $s) {
    $insertSlide->execute([
      'image_path'  => $s['image_path'],
      'title'       => $s['title'],
      'description' => $s['description'],
      'align'       => $s['align'],
      'sort_order'  => $i,
    ]);
    $slideCount++;
  }

  $pdo->commit();

  echo "Importado: $projectCount projeto(s), $imageCount imagem(ns), $slideCount slide(s) do hero.\n";
} catch (Throwable $e) {
  $pdo->rollBack();
  fwrite(STDERR, 'Erro ao importar: ' . $e->getMessage() . "\n");
  exit(1);
}
