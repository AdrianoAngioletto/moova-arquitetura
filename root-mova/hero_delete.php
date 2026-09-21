<?php
require __DIR__ . '/_auth.php';
require_login();
require __DIR__ . '/../backend/lib/hero_repo.php';
require __DIR__ . '/../backend/lib/uploads.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index');
  exit;
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$slide = $id ? get_hero_slide_by_id($id) : null;

if ($slide) {
  get_pdo()->prepare('DELETE FROM hero_slides WHERE id = :id')->execute(['id' => $id]);
  delete_image_file($slide['image']);
  flash_set('Slide excluído.');
} else {
  flash_set('Slide não encontrado.');
}

header('Location: index');
