variable "bucket_name" {
  description = "Bucket S3 que hospeda os arquivos do site."
  type        = string
  default     = "mova-test-deploy"
}

variable "project" {
  description = "Nome do projeto, usado em tags."
  type        = string
  default     = "mova-arquitetura"
}

variable "environment" {
  description = "Ambiente. Este bucket e' apenas preview de aprovacao, nao producao."
  type        = string
  default     = "preview"
}

# South America (edge de Sao Paulo) so' existe em PriceClass_All.
# Como o cliente acessa do Brasil, vale a pena. Trocar para PriceClass_100
# se em algum momento o custo importar mais que a latencia.
variable "price_class" {
  description = "Classe de precos do CloudFront."
  type        = string
  default     = "PriceClass_All"

  validation {
    condition     = contains(["PriceClass_All", "PriceClass_200", "PriceClass_100"], var.price_class)
    error_message = "Use PriceClass_All, PriceClass_200 ou PriceClass_100."
  }
}
