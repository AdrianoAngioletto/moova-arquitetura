<?php
require __DIR__ . '/_auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index');
  exit;
}
csrf_check();

$imageId = (int) ($_POST['image_id'] ?? 0);
$projectId = (int) ($_POST['project_id'] ?? 0);
$dir = ($_POST['dir'] ?? '') === 'down' ? 'down' : 'up';

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT id FROM project_images WHERE project_id = :pid ORDER BY sort_order ASC, id ASC');
$stmt->execute(['pid' => $projectId]);
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$idx = array_search($imageId, $ids, true);
if ($idx !== false) {
  if ($dir === 'up' && $idx > 0) {
    [$ids[$idx - 1], $ids[$idx]] = [$ids[$idx], $ids[$idx - 1]];
  } elseif ($dir === 'down' && $idx < count($ids) - 1) {
    [$ids[$idx + 1], $ids[$idx]] = [$ids[$idx], $ids[$idx + 1]];
  }

  $update = $pdo->prepare('UPDATE project_images SET sort_order = :o WHERE id = :id');
  foreach ($ids as $order => $imgId) {
    $update->execute(['o' => $order, 'id' => $imgId]);
  }
}

header('Location: project_form?id=' . $projectId . '#img-' . $imageId);
