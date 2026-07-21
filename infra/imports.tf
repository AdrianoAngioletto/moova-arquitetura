##############################################################
# O bucket foi criado fora do Terraform. Estes blocos o adotam
# para o state em vez de tentar criar um novo.
#
# Sao idempotentes: depois do primeiro apply podem continuar aqui
# sem efeito nenhum.
##############################################################

import {
  to = aws_s3_bucket.site
  id = var.bucket_name
}

import {
  to = aws_s3_bucket_public_access_block.site
  id = var.bucket_name
}

import {
  to = aws_s3_bucket_ownership_controls.site
  id = var.bucket_name
}

import {
  to = aws_s3_bucket_policy.site
  id = var.bucket_name
}
