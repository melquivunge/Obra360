# Serviços de arquitectura e design de interiores

**Definido em:** 2026-08-23
**Origem:** muitos clientes contratam só o projecto — arquitectura ou design de
interiores — sem obra associada. Quando decidem avançar para a construção, o
trabalho já feito tem de os acompanhar em vez de recomeçar.

## Decisões estruturais

Tomadas com o utilizador a 2026-08-23, cada uma com a alternativa que foi rejeitada:

| Decisão | Escolha | Alternativa rejeitada e porquê |
|---|---|---|
| Onde vive um serviço de projecto | Um **tipo** de projecto na tabela `projects` | Entidade separada: duplicaria documentos, pagamentos, mensagens, portal e relatórios — quase um segundo produto dentro do mesmo sistema |
| Como se guardam as plantas | **Entidade própria com versões e aprovação** | Documentos do tipo `planta`: sem versões ninguém sabe qual é a planta boa, e é aí que está o valor |
| Ficheiros 3D | Descarregar sempre; **visualizar só formatos leves** | Visualizador a sério: exigiria converter formatos proprietários no servidor, inviável em alojamento partilhado |
| Passagem para a obra | A obra **fica ligada** ao projecto de origem | Copiar ficheiros: duas cópias do mesmo desenho, que divergem sem ninguém dar por isso |

## Modelo de dados

**`projects.type`** — `construcao` (valor por omissão para as obras existentes),
`remodelacao`, `arquitetura`, `design_interiores`. O tipo governa o vocabulário e
o que a interface mostra, não uma tabela nova.

**`projects.source_project_id`** — ligação opcional de uma obra ao projecto de
arquitectura que lhe deu origem. É por aqui que as plantas aprovadas aparecem na
obra, sem cópias.

**`plans`** — uma planta é um desenho identificado (por exemplo "Planta baixa —
piso 0"), não um ficheiro. Pertence a um projecto, tem nome, disciplina e estado.

**`plan_versions`** — cada revisão é uma versão numerada da mesma planta, com o
ficheiro, quem a enviou, quando foi enviada ao cliente, e se e quando o cliente a
aprovou. O histórico nunca é apagado: uma versão substituída continua legível.

## Requisitos

### Tipo de projecto

- **TIPO-01**: um projecto tem um tipo entre construção, remodelação, arquitectura e design de interiores
- **TIPO-02**: todos os projectos existentes ficam como construção, sem intervenção manual
- **TIPO-03**: o tipo é escolhido na criação e pode ser alterado na edição
- **TIPO-04**: o tipo é visível na lista de projectos e na página do projecto, no portal do cliente inclusive
- **TIPO-05**: a lista de projectos pode ser filtrada por tipo

### Plantas

- **PLAN-01**: o arquitecto cria uma planta num projecto, com nome e disciplina (2D ou 3D)
- **PLAN-02**: cada planta tem versões numeradas a partir de 1; carregar um ficheiro novo cria a versão seguinte
- **PLAN-03**: uma versão guarda o ficheiro, o nome original, o formato, o tamanho, quem a carregou e uma nota de alterações
- **PLAN-04**: a versão mais recente é a corrente; as anteriores ficam acessíveis e marcadas como substituídas
- **PLAN-05**: uma planta tem estado — em elaboração, enviada ao cliente, aprovada, substituída
- **PLAN-06**: os ficheiros vivem no disco privado e só saem por rota autenticada, como o resto da media
- **PLAN-07**: formatos aceites — PDF, JPG, PNG, WEBP, DWG, DXF, SKP, RVT, IFC, GLB, GLTF, ZIP
- **PLAN-08**: uma versão pode, em alternativa ao ficheiro, guardar um endereço externo, para desenhos que excedam o limite do servidor
- **PLAN-09**: apagar uma planta é reversível (soft delete) e não apaga o histórico de versões

### Portal do cliente

- **PORT-01**: o cliente vê as plantas que lhe foram enviadas, nunca as que estão em elaboração
- **PORT-02**: o cliente descarrega a versão corrente e as anteriores
- **PORT-03**: PDF e imagens são vistos no browser sem descarregar
- **PORT-04**: o cliente aprova a versão corrente; a aprovação regista quem aprovou e quando
- **PORT-05**: a aprovação entra no histórico do projecto e no registo de auditoria
- **PORT-06**: uma versão aprovada não pode ser aprovada outra vez nem alterada

### Ligação à obra

- **OBRA-01**: ao criar uma obra, se o cliente escolhido tiver projectos de arquitectura, é possível ligá-la a um deles
- **OBRA-02**: a obra mostra as plantas aprovadas do projecto de origem, sem cópia dos ficheiros
- **OBRA-03**: a partir do projecto de arquitectura vê-se a obra que dele resultou
- **OBRA-04**: o cliente vê a continuidade entre o projecto e a obra no portal

### Limites do servidor

- **LIM-01**: o limite real de upload (64 MB) é apresentado a quem carrega, antes de o upload falhar
- **LIM-02**: um ficheiro acima do limite dá uma mensagem que explica o limite e sugere o endereço externo
- **LIM-03**: fica documentado como aumentar o limite no cPanel, com o custo e o risco

## Fora do âmbito desta fase — todos entregues a seguir

*Atualizado em 2026-08-24, em português do Brasil.*

Os quatro itens adiados dependiam do que esta fase construiu, e foram entregues
nos dias seguintes, assim que essa base existiu:

| Item adiado | Entregue em | Onde vive no código |
|---|---|---|
| Comentários sobre uma versão concreta da planta | `cc566bf` (24/08) | `App\Models\PlanComment`, migração `2026_08_24_210000_create_plan_comments_table`, `<x-plan-comments>`, `storeComment()` na área interna e no portal, `tests/Feature/PlanCommentTest.php` |
| Contagem de revisões incluídas no contrato | `cc566bf` (24/08) | `projects.revisions_included` (migração `2026_08_24_200000`), `Project::revisionsUsed()`, `revisionsRemaining()`, `hasRevisionLimit()`, `revisionLimitExceeded()`, `<x-revision-counter>`, `tests/Feature/RevisionAllowanceTest.php` |
| Visualizador 3D no navegador para GLB | `9baed6c` (24/08) | `<x-model-viewer>`, `@google/model-viewer` importado sob demanda em `resources/js/app.js`, `PlanVersion::VIEWABLE_3D_FORMATS` (GLB e GLTF), `tests/Feature/Model3dViewerTest.php`. A CSP precisou de `blob:` em `img-src` e `worker-src` para as texturas e o worker de descompressão |
| Etapas de entrega pré-definidas por tipo de projeto | `d93782c` (24/08) | `App\Support\PhaseTemplates`, aplicadas por `Admin\ProjectPhaseController`, `tests/Feature/PhaseTemplateTest.php`. As de arquitetura seguem a NBR 13532 |

Nada continua fora do âmbito desta funcionalidade. O estado de cada requisito
TIPO, PLAN, PORT, OBRA e LIM está na tabela de rastreabilidade de
`.planning/REQUIREMENTS.md`.
