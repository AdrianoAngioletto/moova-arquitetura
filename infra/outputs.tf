output "site_url" {
  description = "Endereco HTTPS para abrir no celular e enviar ao cliente."
  value       = "https://${aws_cloudfront_distribution.site.domain_name}"
}

output "cloudfront_distribution_id" {
  description = "ID da distribuicao, usado para invalidar o cache apos cada deploy."
  value       = aws_cloudfront_distribution.site.id
}

output "bucket_name" {
  description = "Bucket de origem."
  value       = aws_s3_bucket.site.id
}
