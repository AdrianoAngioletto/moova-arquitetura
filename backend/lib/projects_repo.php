<?php
/**
 * Leitura de projetos do banco, sempre no mesmo formato que
 * projetos.php/projeto.php já esperavam do antigo backend/data/projects.php:
 * {slug, title, category, category_label, summary, cover, images:[{file,alt}]}
 */

require_once __DIR__ . '/../db.php';

function category_label(string $category): string {
  static $labels = [
    'corporativo' => 'Corporativo',
    'residencial' => 'Residencial',
    'studio'      => 'Studio',
  ];
  return $labels[$category] ?? ucfirst($category);
}

/**
 * @param array{slug?: string, status?: string} $filters
 */
function get_projects(array $filters = []): array {
  $pdo = get_pdo();

  $status = $filters['status'] ?? 'published';
  $sql = 'SELECT * FROM projects';
  $params = [];
  $where = [];

  if ($status !== 'all') {
    $where[] = 'status = :status';
    $params['status'] = $status;
  }
  if (!empty($filters['slug'])) {
    $where[] = 'slug = :slug';
    $params['slug'] = $filters['slug'];
  }
  if (!empty($filters['id'])) {
    $where[] = 'id = :id';
    $params['id'] = $filters['id'];
  }
  if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
  }
  $sql .= ' ORDER BY sort_order ASC, id ASC';

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  if (!$rows) {
    return [];
  }

  $ids = array_column($rows, 'id');
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $imgStmt = $pdo->prepare(
    "SELECT * FROM project_images WHERE project_id IN ($placeholders) ORDER BY sort_order ASC, id ASC"
  );
  $imgStmt->execute($ids);

  $imagesByProject = [];
  foreach ($imgStmt->fetchAll() as $img) {
    $imagesByProject[$img['project_id']][] = [
      'id'         => $img['id'],
      'file'       => $img['path'],
      'alt'        => $img['alt'],
      'sort_order' => $img['sort_order'],
    ];
  }

  $projects = [];
  foreach ($rows as $row) {
    $projects[] = [
      'id'             => $row['id'],
      'slug'           => $row['slug'],
      'title'          => $row['title'],
      'category'       => $row['category'],
      'category_label' => category_label($row['category']),
      'summary'        => $row['summary'],
      'cover'          => $row['cover'],
      'status'         => $row['status'],
      'images'         => $imagesByProject[$row['id']] ?? [],
    ];
  }

  return $projects;
}

function get_project_by_slug(string $slug): ?array {
  $projects = get_projects(['slug' => $slug]);
  return $projects[0] ?? null;
}

function get_project_by_id(int $id): ?array {
  $projects = get_projects(['id' => $id, 'status' => 'all']);
  return $projects[0] ?? null;
}

/** Gera um slug único a partir do título (usado ao criar um projeto novo). */
function slugify_unique(string $title, ?int $excludeId = null): string {
  $base = strtolower(trim($title));
  $base = preg_replace('/[^a-z0-9]+/', '-', $base);
  $base = trim($base, '-');
  if ($base === '') {
    $base = 'projeto';
  }

  $pdo = get_pdo();
  $slug = $base;
  $suffix = 2;
  while (true) {
    $sql = 'SELECT COUNT(*) FROM projects WHERE slug = :slug';
    $params = ['slug' => $slug];
    if ($excludeId) {
      $sql .= ' AND id != :id';
      $params['id'] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ((int) $stmt->fetchColumn() === 0) {
      return $slug;
    }
    $slug = $base . '-' . $suffix;
    $suffix++;
  }
}
