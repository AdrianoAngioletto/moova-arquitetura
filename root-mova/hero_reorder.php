<?php
require __DIR__ . '/_auth.php';
require_login();
require __DIR__ . '/../backend/lib/hero_repo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index');
  exit;
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$dir = ($_POST['dir'] ?? '') === 'down' ? 'down' : 'up';

$ids = array_column(get_hero_slides(false), 'id');
$idx = array_search($id, $ids, true);

if ($idx !== false) {
  if ($dir === 'up' && $idx > 0) {
    [$ids[$idx - 1], $ids[$idx]] = [$ids[$idx], $ids[$idx - 1]];
  } elseif ($dir === 'down' && $idx < count($ids) - 1) {
    [$ids[$idx + 1], $ids[$idx]] = [$ids[$idx], $ids[$idx + 1]];
  }

  // Renumera tudo em sequência — corrige de vez qualquer empate/ordem antiga
  // que possa ter sobrado de edições manuais anteriores.
  $pdo = get_pdo();
  $stmt = $pdo->prepare('UPDATE hero_slides SET sort_order = :o WHERE id = :id');
  foreach ($ids as $order => $slideId) {
    $stmt->execute(['o' => $order, 'id' => $slideId]);
  }
}

header('Location: index#slide-' . $id);
