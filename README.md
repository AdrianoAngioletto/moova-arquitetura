<div align="center">

# MOVA Arquitetura

**Projetar. Executar. Mover.**

Site institucional do estúdio MOVA Arquitetura — arquitetura corporativa de alto padrão,
unindo projeto, execução, gerenciamento e manutenção em um fluxo único.

</div>

---

## Sobre

Landing page de página única construída sobre o manual de marca do estúdio. A identidade
visual — paleta, tipografia e o símbolo **V/Λ** — foi levada para o código sem intermediários:
as fontes vêm do arquivo original da marca, os logotipos são vetores extraídos do material
em `.ai` e as cores seguem a paleta oficial.

O foco é um público corporativo exigente, então o site prioriza sofisticação, desempenho e
uma navegação fluida, com movimento discreto e sem excessos.

## Stack

Sem frameworks, sem build, sem dependências em runtime.

| Camada | Tecnologia |
| --- | --- |
| Marcação | HTML5 semântico |
| Estilo | CSS moderno (custom properties, `clamp()`, grid, flexbox) |
| Comportamento | JavaScript (ES5+), um único arquivo |
| Tipografia | Ranade (títulos) e Chillax (texto), servidas localmente em `woff2` |
| Infraestrutura | Terraform · Amazon S3 + CloudFront |

A escolha por baunilha é deliberada: a página carrega rápido, funciona em qualquer navegador
moderno e não carrega peso desnecessário para o que é, essencialmente, um site de apresentação.

## Estrutura

```
.
├── index.html              Página principal
├── src/
│   ├── assets/
│   │   ├── fonts/           Ranade e Chillax (woff2)
│   │   ├── img/             Imagens do portfólio
│   │   ├── logo/            Logotipos vetoriais (svg)
│   │   ├── music/           Trilha ambiente
│   │   └── pattern/         Estampa da marca
│   ├── styles/style.css
│   ├── scripts/main.js
│   └── views/               Reservado para partials
├── infra/                  Terraform (S3 + CloudFront)
└── robots.txt
```

`index.html` permanece na raiz porque o hosting estático do S3 exige o documento de índice
nesse nível.

## Identidade visual

**Paleta**

| | Cor | Hex |
| --- | --- | --- |
| ![#8C421F](https://placehold.co/12x12/8C421F/8C421F.png) | Terracota | `#8C421F` |
| ![#B66D48](https://placehold.co/12x12/B66D48/B66D48.png) | Siena | `#B66D48` |
| ![#DACFC3](https://placehold.co/12x12/DACFC3/DACFC3.png) | Areia | `#DACFC3` |
| ![#BCB7B1](https://placehold.co/12x12/BCB7B1/BCB7B1.png) | Greige | `#BCB7B1` |
| ![#F4F0EA](https://placehold.co/12x12/F4F0EA/F4F0EA.png) | Marfim | `#F4F0EA` |
| ![#9BA186](https://placehold.co/12x12/9BA186/9BA186.png) | Sálvia | `#9BA186` |
| ![#505B40](https://placehold.co/12x12/505B40/505B40.png) | Oliva | `#505B40` |
| ![#414143](https://placehold.co/12x12/414143/414143.png) | Grafite | `#414143` |

**Tipografia** — *Ranade* nos títulos e chamadas, com itálico reservado para a palavra de
ênfase; *Chillax* nos textos corridos e na interface.

## Desenvolvimento

Por ser estático, basta servir a raiz do projeto:

```bash
python3 -m http.server 5173
# http://localhost:5173
```

Ou qualquer servidor estático de sua preferência (`npx serve`, extensão Live Server, etc.).

### Com Docker

```bash
docker compose up
# http://localhost:8080
```

O container roda em `php:8.3-apache` servindo a raiz do projeto (a pasta é montada como
volume, então qualquer edição nos arquivos aparece direto no navegador, sem rebuild). O PHP
já vem pronto para quando o `backend/contato.php` do formulário de contato for implementado.

## Painel administrativo

Projetos, categorias e o slide da home são editáveis por `/root-mova` (login com conta de
admin). Os dados ficam em MySQL — o `docker-compose.yml` já sobe um serviço `db` junto com
o `site`.

- **Mudou o schema** (`db/init/001_schema.sql`)? Ele só roda automaticamente num volume
  vazio. Pra reaplicar: `docker compose down -v && docker compose up -d --build`.
- **Primeira conta de admin** (ou recuperar acesso): `docker compose exec site php
  backend/admin/bin/create_admin.php email nome senha`. Depois disso, novas contas podem
  ser criadas direto pela tela `/root-mova/users.php`.
- **Importar dados de um `backend/data/projects.php` antigo**: `docker compose exec site
  php backend/admin/bin/seed_from_static.php` (só roda se as tabelas estiverem vazias).
- **Fora do Docker** (ex: Hostinger via SSH), sem variáveis de ambiente disponíveis: crie
  `backend/config.local.php` (fora do git) retornando `['db_host' => ..., 'db_name' => ...,
  'db_user' => ..., 'db_pass' => ...]` com as credenciais do MySQL da hospedagem.
- **Isso não funciona no preview estático em S3** — lá não tem PHP nem banco; o site
  continua mostrando o conteúdo estático de fallback (slide do hero, projetos daquele
  momento) até migrar pra uma hospedagem com PHP+MySQL de verdade.

## Infraestrutura

Toda a hospedagem é descrita em código, em `infra/`. O site vive em um bucket S3 **privado**,
servido exclusivamente pelo CloudFront com HTTPS — o acesso direto ao bucket fica bloqueado
via *Origin Access Control*.

```bash
cd infra
terraform init
terraform plan
terraform apply
```

## Deploy

Publicar os arquivos e limpar o cache da CDN:

```bash
aws s3 sync . s3://mova-test-deploy --region us-east-1 --delete \
  --exclude "infra/*" --exclude ".git/*" --exclude ".claude/*" \
  --exclude "Smash/*" --exclude ".gitignore" --exclude "*.md" \
  --exclude ".DS_Store" --exclude "*/.DS_Store"

aws cloudfront create-invalidation \
  --distribution-id <ID_DA_DISTRIBUICAO> --paths "/*"
```

## Contato

**MOVA Arquitetura**
Anne Vasconcelos · Fernanda Mota
[contato@movaarq.com.br](mailto:contato@movaarq.com.br) · [@mo.voarq](https://instagram.com/mo.voarq)

---

<div align="center">
<sub>© MOVA Arquitetura. Todos os direitos reservados.</sub>
</div>
