terraform {
  required_version = ">= 1.5"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 6.0"
    }
  }
}

# CloudFront e certificados ACM globais vivem obrigatoriamente em us-east-1.
provider "aws" {
  region = "us-east-1"
}
