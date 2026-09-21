<?php
/**
 * API pública (GET) usada por src/scripts/main.js pra montar o slide da home
 * a partir do banco. Se essa chamada falhar (banco fora do ar, ou o preview
 * estático em S3 que não roda PHP), o main.js usa o fallback estático que já
 * existe direto no index.html — ver startHeroSlideshow().
 */

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../lib/hero_repo.php';

try {
  echo json_encode(get_hero_slides(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([]);
}
