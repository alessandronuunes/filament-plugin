# Changelog

Todas as alterações notáveis do pacote `filament-plugin` serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

## [1.0.0] - Lançamento inicial

### Adicionado

- **Comando Artisan `make:filament-plugin`** — gera o scaffolding completo de um plugin Filament (v4+) a partir do nome em PascalCase.
- **Tipos de plugin** — escolha entre:
  - `panel` — para integração com um Filament Panel (pages, resources, widgets, tenancy); gera classe Plugin.
  - `standalone` — para componentes reutilizáveis (form fields, table columns) em qualquer contexto; sem classe Plugin.
- **Prompts interativos** — vendor (namespace), slug (kebab-case), descrição, autor, e-mail e opções de inclusão.
- **Opções de scaffolding** (tudo configurável):
  - ServiceProvider e, para tipo panel, classe Plugin.
  - Arquivo de configuração e `ConfigHelper` em `Support/`.
  - Estrutura de views (`resources/views/filament/pages`, `livewire`).
  - Traduções em inglês e português brasileiro (`resources/lang/en`, `resources/lang/pt_BR`).
  - Diretório de migrations (opcional).
  - Comando de instalação em `src/Console/Commands/` (opcional).
- **Arquivos de projeto** — `.gitignore`, `pint.json`, `CHANGELOG.md`, `LICENSE.md`, `README.md` e stubs para `.github/` (CONTRIBUTING, FUNDING, SECURITY).
- **Opções de linha de comando**:
  - `--path=packages` — diretório base onde o plugin será criado (padrão: `packages`).
  - `--force` — sobrescreve o diretório se já existir.
- **Validação** — nome do plugin deve estar em PascalCase (ex.: `FilamentMember`).
- **Próximos passos** — após a geração, exibe no terminal os passos para registrar o plugin no PanelProvider, publicar config/migrations e limpar caches.
