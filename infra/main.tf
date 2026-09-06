##############################################################
# MOVA Arquitetura — preview estatico
#
# S3 privado + CloudFront com HTTPS (certificado *.cloudfront.net
# fornecido pela AWS, sem custo e sem dominio proprio).
#
# O acesso ao bucket passa a ser exclusivo do CloudFront via OAC.
# O endpoint http://<bucket>.s3-website-... deixa de responder —
# era justamente ele que os celulares bloqueavam, por ser HTTP puro.
##############################################################

locals {
  tags = {
    Project     = var.project
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ------------------------------------------------------------
# Bucket (ja existia; adotado via bloco import em imports.tf)
# ------------------------------------------------------------
resource "aws_s3_bucket" "site" {
  bucket = var.bucket_name
  tags   = local.tags
}

resource "aws_s3_bucket_ownership_controls" "site" {
  bucket = aws_s3_bucket.site.id

  rule {
    object_ownership = "BucketOwnerEnforced"
  }
}

resource "aws_s3_bucket_versioning" "site" {
  bucket = aws_s3_bucket.site.id

  versioning_configuration {
    status = "Enabled"
  }
}

# Fecha o bucket para o mundo. Quem serve o conteudo agora e' o CloudFront.
resource "aws_s3_bucket_public_access_block" "site" {
  bucket = aws_s3_bucket.site.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# Somente esta distribuicao CloudFront pode ler os objetos.
data "aws_iam_policy_document" "site" {
  statement {
    sid     = "AllowCloudFrontServicePrincipalReadOnly"
    effect  = "Allow"
    actions = ["s3:GetObject"]

    resources = ["${aws_s3_bucket.site.arn}/*"]

    principals {
      type        = "Service"
      identifiers = ["cloudfront.amazonaws.com"]
    }

    condition {
      test     = "StringEquals"
      variable = "AWS:SourceArn"
      values   = [aws_cloudfront_distribution.site.arn]
    }
  }
}

resource "aws_s3_bucket_policy" "site" {
  bucket = aws_s3_bucket.site.id
  policy = data.aws_iam_policy_document.site.json

  # Sem isso o apply pode tentar gravar a policy antes do bloqueio publico
  # estar valendo, e a AWS recusa.
  depends_on = [aws_s3_bucket_public_access_block.site]
}

# ------------------------------------------------------------
# CloudFront
# ------------------------------------------------------------
resource "aws_cloudfront_origin_access_control" "site" {
  name                              = "${var.bucket_name}-oac"
  description                       = "OAC para o bucket do preview da MOVA"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

# Politicas gerenciadas pela AWS — evitam manter config propria de cache.
data "aws_cloudfront_cache_policy" "optimized" {
  name = "Managed-CachingOptimized"
}

data "aws_cloudfront_response_headers_policy" "security" {
  name = "Managed-SecurityHeadersPolicy"
}

resource "aws_cloudfront_distribution" "site" {
  enabled             = true
  is_ipv6_enabled     = true
  comment             = "${var.project} — ${var.environment}"
  default_root_object = "index.html"
  price_class         = var.price_class
  tags                = local.tags

  origin {
    domain_name              = aws_s3_bucket.site.bucket_regional_domain_name
    origin_id                = "s3-${var.bucket_name}"
    origin_access_control_id = aws_cloudfront_origin_access_control.site.id
  }

  default_cache_behavior {
    target_origin_id       = "s3-${var.bucket_name}"
    viewer_protocol_policy = "redirect-to-https"
    allowed_methods        = ["GET", "HEAD", "OPTIONS"]
    cached_methods         = ["GET", "HEAD"]
    compress               = true

    cache_policy_id            = data.aws_cloudfront_cache_policy.optimized.id
    response_headers_policy_id = data.aws_cloudfront_response_headers_policy.security.id
  }

  # Origem S3 REST nao resolve index.html sozinha; devolvemos a pagina
  # para qualquer rota inexistente.
  custom_error_response {
    error_code            = 403
    response_code         = 200
    response_page_path    = "/index.html"
    error_caching_min_ttl = 10
  }

  custom_error_response {
    error_code            = 404
    response_code         = 200
    response_page_path    = "/index.html"
    error_caching_min_ttl = 10
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  # Certificado padrao *.cloudfront.net. Ao migrar para dominio proprio,
  # trocar por acm_certificate_arn + aliases.
  viewer_certificate {
    cloudfront_default_certificate = true
    minimum_protocol_version       = "TLSv1.2_2021"
  }
}
