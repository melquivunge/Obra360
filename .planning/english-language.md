# Inglês como idioma — 25/09/2026

## Escopo aprovado
Português e inglês no login, área interna, portal e relatórios; preferência por usuário. Português mantém-se padrão. Conteúdo cadastrado pelos usuários e registros históricos não são traduzidos automaticamente.

## Implementação
- Migration adiciona `users.locale` nullable (`pt_BR`/`en`). Contas sem preferência adotam o idioma do visitante no primeiro login; uma preferência existente prevalece.
- Middleware `SetLocale` resolve usuário, sessão e cookie com lista fechada, redefinindo o locale a cada request para evitar contaminação entre usuários.
- POST `/idioma` (`locale.update`) valida locale, grava sessão, cookie anual HttpOnly/SameSite=Lax e usuário autenticado. CSRF permanece no grupo web. `return_to` só aceita caminhos locais, rejeitando URLs externas, barras invertidas e caracteres de controle.
- Login sincroniza cookie e sessão com a preferência; logout preserva cookie do navegador.
- Catálogo nativo inglês de validação, autenticação, paginação e senhas. Flash messages, labels de modelos e notificações usam tradução; usuário implementa HasLocalePreference para notificações em fila.
- `money()` preserva moeda configurada e altera separadores conforme idioma.
- Cache do primeiro projeto inclui locale; labels das atualizações são traduzidos depois da leitura do cache.
- Seletor e templates ficam na implementação de interface em paralelo.

## Verificação
- Primeira execução focada: LocaleTest, 9 testes, 49 assertions, passou.
- Casos adicionais verificam texto do login, relatório, navegação cliente após troca e validação inglês. Validação integrada final será registrada pelo coordenador após consolidação dos catálogos.
- Fixture isolada de navegador em `/tmp/obra360-locale-browser.sqlite`; nenhum banco existente ou `.env` alterado.
- Deploy deve executar migrations antes de servir o código novo.
