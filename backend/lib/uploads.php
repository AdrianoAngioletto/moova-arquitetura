<?php
/**
 * Upload de imagem: valida no servidor (nunca confia em redimensionamento
 * feito no navegador) e salva com nome gerado em src/assets/img/uploads/.
 * Retorna o caminho relativo a src/assets/img/ (ex: "uploads/ab12cd34.jpg"),
 * pronto pra gravar direto nas colunas cover/path/image_path do banco.
 */

function upload_image(array $file): array {
  if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    return ['ok' => false, 'error' => 'Falha no envio do arquivo.'];
  }

  $info = @getimagesize($file['tmp_name']);
  if ($info === false) {
    return ['ok' => false, 'error' => 'Envie apenas .jpg, .png ou .webp.'];
  }

  $extByMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  $mime = $info['mime'];
  if (!isset($extByMime[$mime])) {
    return ['ok' => false, 'error' => 'Formato não suportado. Envie .jpg, .png ou .webp.'];
  }

  $destDir = __DIR__ . '/../../src/assets/img/uploads';
  if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
  }

  $filename = bin2hex(random_bytes(8)) . '.' . $extByMime[$mime];
  $destPath = $destDir . '/' . $filename;

  if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    return ['ok' => false, 'error' => 'Não foi possível salvar o arquivo no servidor.'];
  }

  return ['ok' => true, 'path' => 'uploads/' . $filename];
}

/** Remove um arquivo de src/assets/img/<path>, sem falhar se já não existir. */
function delete_image_file(string $path): void {
  $full = __DIR__ . '/../../src/assets/img/' . $path;
  if (is_file($full)) {
    @unlink($full);
  }
}
