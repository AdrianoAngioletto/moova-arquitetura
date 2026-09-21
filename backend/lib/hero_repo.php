<?php
/**
 * Leitura dos slides do hero da home, ordenados.
 */

require_once __DIR__ . '/../db.php';

function get_hero_slides(bool $onlyActive = true): array {
  $pdo = get_pdo();

  $sql = 'SELECT * FROM hero_slides';
  if ($onlyActive) {
    $sql .= ' WHERE is_active = 1';
  }
  $sql .= ' ORDER BY sort_order ASC, id ASC';

  $rows = $pdo->query($sql)->fetchAll();

  $slides = [];
  foreach ($rows as $row) {
    $slides[] = [
      'id'          => $row['id'],
      'image'       => $row['image_path'],
      'title'       => $row['title'],
      'description' => $row['description'],
      'align'       => $row['align'],
      'is_active'   => (bool) $row['is_active'],
    ];
  }

  return $slides;
}

function get_hero_slide_by_id(int $id): ?array {
  $stmt = get_pdo()->prepare('SELECT * FROM hero_slides WHERE id = :id');
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  if (!$row) {
    return null;
  }
  return [
    'id'          => $row['id'],
    'image'       => $row['image_path'],
    'title'       => $row['title'],
    'description' => $row['description'],
    'align'       => $row['align'],
    'sort_order'  => $row['sort_order'],
    'is_active'   => (bool) $row['is_active'],
  ];
}
