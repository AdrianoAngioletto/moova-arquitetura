---
name: mova-arq
description: Contexto completo da marca MOVA Arquitetura (paleta, tipografia, logos, tom de voz, dados de contato) e as regras técnicas do site. Use SEMPRE que for criar, editar ou revisar qualquer parte do site — HTML, CSS, JS, PHP, textos, imagens ou assets.
---

# MOVA Arquitetura — contexto de marca e regras do site

Fonte da verdade da identidade: pasta `Smash/` (não versionada — está no `.gitignore`).
Os assets já foram extraídos e otimizados para `assets/`. **Não reprocessar** salvo pedido explícito.

---

## 1. Regras inegociáveis do projeto

- **Sem frameworks pesados.** Nada de React, Vue, Next, Tailwind, jQuery, bundlers. Só **HTML + CSS + JavaScript vanilla**. Site leve e rápido.
- **Backend só quando necessário** (envio de e-mail do formulário) e em **PHP puro**, na pasta `backend/`.
- **Proibido commit com `Co-Authored-By: Claude`.** Já desligado em `.claude/settings.json` (`includeCoAuthoredBy: false`). Nunca reativar, nunca adicionar a linha manualmente.
- **Não commitar sem o usuário pedir.**
- Organização de pastas obrigatória (camadas em `src/`, `index.html` na raiz por exigência do S3):

```
index.html            entrada (precisa ficar na raiz)
src/
  assets/             img, logo (svg), fonts (woff2), music, pattern
  styles/style.css
  scripts/main.js
  views/              partials .html (reservado)
infra/                terraform (S3 + CloudFront)
Smash/                identidade original (ignorada pelo git)
```

- Referências no HTML sempre com prefixo `src/` (`src/styles/…`, `src/assets/…`).
- Deploy do site (nunca subir `infra/`, `Smash/`, `.claude/`, `.git/`, `.md`):

```
aws s3 sync . s3://mova-test-deploy --region us-east-1 --delete \
  --exclude "infra/*" --exclude ".git/*" --exclude ".claude/*" \
  --exclude "Smash/*" --exclude ".gitignore" --exclude "*.md" \
  --exclude ".DS_Store" --exclude "*/.DS_Store"
```
Depois invalidar o CloudFront: `aws cloudfront create-invalidation --distribution-id E3LW4WG2LDZ07E --paths "/*"`.
Preview no ar: https://d1sn7bnd5p5iv7.cloudfront.net

- Git remoto: `git@github.com:AdrianoAngioletto/moova-arquitetura.git`, branch `main`.

### Ambiente de preview (não é produção)

Bucket S3 usado só para **mostrar o site ao cliente durante a aprovação**:
`http://mova-test-deploy.s3-website-us-east-1.amazonaws.com/`

- É site estático puro (S3 website hosting): **não roda PHP**. O formulário só funciona de verdade quando o site for para uma hospedagem com PHP.
- Como é HTTP e temporário, não indexar: manter `robots.txt` bloqueando e `noindex` enquanto estiver nesse endereço.
- Nunca tratar esse bucket como produção nem apontar domínio/materiais do cliente para ele.

---

## 2. Posicionamento

Arquitetura **corporativa** de médio e alto padrão. Público **empresarial com alto poder aquisitivo**.
O site deve transmitir **luxo, riqueza, sofisticação e precisão** — com efeitos elegantes, nunca espalhafatosos.

- **Slogan:** *Projetar. Executar. Mover.*
- **Frase-chave:** *Mover é transformar.*
- **Arquétipos:** O Guardião (principal) com traços da Criadora — segurança, método, proteção do investimento + estética e fluidez.
- **Diferencial:** fluxo único que une arquitetura, engenharia e gestão (turnkey), sem pontas soltas.

**Pilares:** Precisão técnica · Clareza e organização · Sofisticação estética funcional · Controle de obra e execução · Soluções integradas · Relação de confiança.

**Tom de voz:** claro, técnico e acessível; firme sem ser frio; sofisticado sem rebuscar. Frases curtas. Nada de jargão vazio, exclamação ou promessa exagerada.

---

## 3. Paleta oficial (extraída de `Smash/Paleta de Cor`)

| Nome | HEX | Uso no site |
|---|---|---|
| Terracota | `#8C421F` | acento profundo, hover |
| Siena | `#B66D48` | **acento principal** (links, detalhes, CTA) |
| Areia | `#DACFC3` | fundos claros alternativos |
| Greige | `#BCB7B1` | textos secundários sobre claro |
| Marfim | `#F4F0EA` | **fundo claro / texto sobre escuro** |
| Sálvia | `#9BA186` | acento secundário, discreto |
| Oliva | `#505B40` | acento secundário escuro |
| Grafite | `#414143` | superfícies escuras, texto sobre claro |

Tons de apoio criados para o site (derivados, não da marca): `--preto: #0E0E0F` (fundo hero/dark), `--carvao: #1A1A1B`.

**Regra:** nenhum outro hex além destes. Sem azul, sem gradiente colorido, sem sombra colorida.

---

## 4. Tipografia

- **Ranade** — títulos e chamadas (Light/Regular/Medium/Bold + Italic). Italic só para a palavra de ênfase (ex.: *Mover.*).
- **Chillax** — textos corridos, UI, labels (Light/Regular/Medium/Semibold).

Já convertidas em `assets/fonts/*.woff2` com `@font-face` em `css/style.css`. **Não usar Google Fonts.**
Eyebrows/labels: Chillax, uppercase, `letter-spacing: .18em`, tamanho pequeno.

---

## 5. Logotipo (`assets/logo/*.svg`)

Todos usam `fill="currentColor"` — colorir via CSS `color`.

| Arquivo | Quando usar |
|---|---|
| `mova-horizontal.svg` | MO\|VA ARQ — header |
| `mova-vertical.svg` | MOVA + ARQUITETURA empilhado |
| `mova-vertical-ponto.svg` | MO·VA + ARQUITETURA — footer, peças nobres |
| `mova-horizontal-arq.svg` | MOVA com ARQ à direita |
| `mova-empilhado.svg` | versão compacta em dois níveis |
| `mova-lettermark.svg` | só "MOVA" |
| `mova-simbolo.svg` | ícone V/Λ — favicon, marca d'água, badges |

**Nunca** distorcer, rotacionar, aplicar contorno ou recolorir fora da paleta.

## 6. Estampa (pattern)

`assets/pattern/mova-pattern.svg` — zigzag derivado do símbolo V/Λ, com `currentColor`.
Uso sempre discreto: opacidade baixa, como textura de fundo. Nunca protagonista.

---

## 7. Dados reais (não inventar outros)

- **Sócias:** Anne Vasconcelos (CAU A1739921) e Fernanda Mota
- **E-mails:** contato@movaarq.com.br · anne@movaarq.com.br
- **Telefones:** (11) 98341-9654 (Anne) · (11) 95030-2970 (Fernanda)
- **Instagram:** @mo.voarq · @movaarq.gestao
- **Fundação:** outubro de 2025

---

## 8. Padrões técnicos do site

- Mobile-first, responsivo, sem scroll horizontal.
- Acessibilidade: contraste AA, `:focus-visible` visível, `aria-*` nos componentes interativos, respeitar `prefers-reduced-motion`.
- Animações: só `transform` e `opacity`. Reveal via `IntersectionObserver`. Nada de biblioteca de animação.
- Imagens: `loading="lazy"` (exceto a do hero), `.jpg` já otimizado em `assets/img/`.
- JS: um único `javascript/main.js`, sem dependências, carregado com `defer`.
- Formulário: POST para `backend/contato.php` via `fetch`, com honeypot anti-spam e fallback sem JS.
