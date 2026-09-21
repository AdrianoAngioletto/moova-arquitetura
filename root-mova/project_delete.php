<?php
require __DIR__ . '/_auth.php';
require_login();
require __DIR__ . '/../backend/lib/projects_repo.php';
require __DIR__ . '/../backend/lib/uploads.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index');
  exit;
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$project = $id ? get_project_by_id($id) : null;

if ($project) {
  // FK ON DELETE CASCADE cuida das linhas de project_images; os arquivos no
  // disco precisam ser removidos manualmente.
  get_pdo()->prepare('DELETE FROM projects WHERE id = :id')->execute(['id' => $id]);
  delete_image_file($project['cover']);
  foreach ($project['images'] as $img) {
    delete_image_file($img['file']);
  }
  flash_set('Projeto excluído.');
} else {
  flash_set('Projeto não encontrado.');
}

header('Location: index');
