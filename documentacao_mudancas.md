# Relatório Técnico de Otimização de Banco de Dados (BD II)

Este documento registra as melhorias de arquitetura, performance e segurança implementadas nas consultas à base de dados MySQL através do Eloquent ORM no framework Laravel.

## Resumo das Otimizações Aplicadas

As alterações visaram mitigar três gargalos clássicos de infraestrutura em sistemas integrados a bancos de dados relacionais:
1. **Problema do $N+1$ (Lazy Loading vs. Eager Loading)**
2. **Desperdício de Memória e Banda (Falta de Projeção Linear / `SELECT *`)**
3. **Riscos de Esgotamento de Memória (Ausência de Paginação Física)**

---

## Análise por Controlador

### 1. `PessoaController.php`
* **Gargalo Original:** Uso de `Pessoa::all()` gerava um `SELECT *` implícito. Ao renderizar a view, o relacionamento com a tabela de bibliotecas disparava uma nova consulta por linha (Gargalo de I/O de rede $N+1$).
* **Solução Implementada:** * Aplicação de **Eager Loading** com `with()` para carregar os relacionamentos em tempo constante $O(2)$.
  * **Projeção estrita** de colunas (`select('id', 'name', 'email', 'telefone')`), expurgando campos sensíveis como o hash de senhas (`password`) da memória de transporte.
  * **Paginação Física** com `paginate(10)` delegando as cláusulas `LIMIT` e `OFFSET` nativas ao SGBD.

### 2. `BibliotecasController.php`
* **Gargalo Original:** Buscas com o operador `LIKE` geravam strings redundantes (`LIKE '%%'`) quando o campo de busca estava vazio. Além disso, a falta de projeção forçava o tráfego de metadados pesados por meio do método `->get()`.
* **Solução Implementada:**
  * Uso do método `filled()` para condicionar a execução da cláusula `LIKE` apenas quando houver entrada real de dados.
  * Integração de **Eager Loading** mapeando o relacionamento correto (`creator`) identificado no Model `Biblioteca`.
  * Paginação de registros integrada.

### 3. `BibliotecaPessoaController.php` (Tabela Associativa N:N)
* **Gargalo Original:** O método `whereDoesntHave` utilizava uma subquery eficiente (`NOT EXISTS`), mas pecava ao tentar dar carga em toda a coleção de pessoas sem critérios de corte.
* **Solução Implementada:** Restrição cirúrgica de colunas e uso de `take(50)` para limitar o consumo do buffer de rede na renderização de caixas de seleção da interface.

### 4. `UserController.php`
* **Gargalo Original:** Vulnerabilidade de segurança e desperdício de memória RAM ao trafegar hashes de senhas criptografadas em listagens gerais de usuários.
* **Solução Implementada:** Isolamento total da coluna `password` nas listagens gerais (`index`, `edit`, `show`) usando projeção seletiva.

---

## Impacto Prático Identificado (Evidências do Debugbar)

### Antes da Refatoração (Exemplo: Listagem de Pessoas com 10 registros)
* **Total de Queries:** 11 consultas ao banco de dados.
* **Comportamento:** `SELECT * FROM pessoas;` seguido de múltiplos `SELECT * FROM bibliotecas WHERE id = ...;` (um para cada linha — Lazy Loading).

### Após a Refatoração
* **Total de Queries:** 2 consultas principais estáveis.
* **Comportamento:**
```sql
SELECT `id`, `name`, `email`, `telefone` FROM `pessoas` LIMIT 10 OFFSET 0;
SELECT `id`, `name` FROM `bibliotecas` WHERE `bibliotecas`.`id` IN (1, 2);