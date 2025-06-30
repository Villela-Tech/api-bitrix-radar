# Slim Framework 4 Skeleton Application

[![Coverage Status](https://coveralls.io/repos/github/slimphp/Slim-Skeleton/badge.svg?branch=master)](https://coveralls.io/github/slimphp/Slim-Skeleton?branch=master)

Use this skeleton application to quickly setup and start working on a new Slim Framework 4 application. This application uses the latest Slim 4 with Slim PSR-7 implementation and PHP-DI container implementation. It also uses the Monolog logger.

This skeleton application was built for Composer. This makes setting up a new Slim Framework application quick and easy.

## Install the Application

Run this command from the directory in which you want to install your new Slim Framework application. You will require PHP 7.4 or newer.

```bash
composer create-project slim/slim-skeleton [my-app-name]
```

Replace `[my-app-name]` with the desired directory name for your new application. You'll want to:

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writable.

To run the application in development, you can run these commands 

```bash
cd [my-app-name]
composer start
```

Or you can use `docker-compose` to run the app with `docker`, so you can run these commands:
```bash
cd [my-app-name]
docker-compose up -d
```
After that, open `http://localhost:8080` in your browser.

Run this command in the application directory to run the test suite

```bash
composer test
```

That's it! Now go build something cool.

# Integração Bitrix24 - Radar

Sistema de integração entre o CRM Bitrix24 e o sistema Radar para gerenciamento de contratos.

## Campos Obrigatórios no Bitrix24

### Dados do Negócio
| Campo Bitrix | ID do Campo | Descrição | Exemplo |
|-------------|-------------|-----------|---------|
| Título do Negócio | TITLE | Nome/Razão Social do cliente | "Teste Bitrix no Radar novo" |
| Número do Contrato | UF_CRM_1746140348 | Identificador único do contrato | "83334" |
| Data de Adesão | UF_CRM_1746140492 | Data inicial do contrato/faturamento | "2025-05-21" |
| Data de Vencimento | UF_CRM_1746140206 | Data de vencimento das parcelas | "2028-05-24" |
| Valor do Contrato | OPPORTUNITY | Valor total do contrato | "3000.00" |
| Quantidade de Parcelas | UF_CRM_1746139160 | Número de parcelas do contrato | "4" |
| CNPJ/CPF | UF_CRM_1745494235 | Documento do cliente | "40.353.528/0001-69" |
| Data Inicial | BEGINDATE | Data de início do negócio | "2025-05-21" |
| Data Final | CLOSEDATE | Data de fechamento prevista | "2025-05-28" |

### Dados de Célula/Corner/Franqueado
| Campo Bitrix | ID do Campo | Descrição | Exemplo |
|-------------|-------------|-----------|---------|
| Célula | UF_CRM_1746140099 | Código da célula (lista) | "Célula 1.17 - ANA PAULA PAZZIN BITTENCOURT - 18448 | 15" |
| Produto | UF_CRM_1748604707924 | Código do produto em formato BBCode | "[table]...[/table]" |
| Franqueado | UF_CRM_1750679348 | Código do franqueado (lista) | "ALTAIR ROBERTO FERREIRA JUNIOR | 943760" |

### Dados de Contato
| Campo Bitrix | ID do Campo | Descrição | Exemplo |
|-------------|-------------|-----------|---------|
| Telefone | UF_CRM_1746140456 | Telefone do cliente | "910326072" |
| DDD | UF_CRM_1746140439 | DDD do telefone | "11" |
| Endereço | UF_CRM_1746146377 | Endereço completo | "R. Andrea Paulinetti, 406" |
| Bairro | UF_CRM_1746146387 | Bairro | "JARDIM DAS ACACIAS" |
| Cidade | UF_CRM_1746146397 | Cidade | "São Paulo" |
| Estado | UF_CRM_1748021665 | UF do estado | "SP" |
| CEP | UF_CRM_1748021587 | CEP | "90810-160" |
| Email | - | Email do contato (se disponível) | "exemplo@email.com" |

### Dados do InfoPlus
| Campo | Descrição | Obrigatório | Exemplo |
|-------|-----------|-------------|---------|
| Tipo Contrato | Tipo do contrato | Não | "" |
| Valor no Exito (%) | Percentual de êxito | Não | "" |
| Franqueado | Código do franqueado | Sim | "943760" |
| Mês Venda | Mês/Ano da venda | Sim | "05/2025" |
| Valor da Parcela | Valor de cada parcela | Sim | Calculado do valor total |
| Qtde Parcelas | Número de parcelas | Sim | "4" |
| Mês Protocolo | Mês/Ano do protocolo | Sim | "05/2025" |

## Estrutura dos Rateios

### Rateio Contábil
```json
{
    "CodigoConta": "857367",      // Código do produto
    "CodigoDepartamento": "857367",// Código do produto
    "CodigoFilial": "15",         // Código do corner
    "Quantidade": 1,
    "ValorRateio": "3000,00"
}
```

### Rateio Gerencial
```json
{
    "CodigoDepartamento": "18448", // Código da célula
    "CodigoFilial": "15",         // Código do corner
    "CodigoConta": "18448",       // Código da célula
    "Quantidade": 1,
    "ValorRateio": "3000,00"
}
```

## Formato das Datas
- Todas as datas no Bitrix são armazenadas no formato: "YYYY-MM-DDThh:mm:ss+03:00"
- Ao enviar para o Radar, são convertidas para: "DD/MM/YYYY"

## Regras de Negócio
1. A data de vencimento DEVE ser posterior à data de faturamento
2. O código da célula é extraído do campo UF_CRM_1746140099 que é um campo de lista
3. O código do corner é extraído do mesmo campo, após o caractere "|"
4. O código do produto é extraído do campo UF_CRM_1748604707924 em formato BBCode
5. O código do franqueado é extraído do campo UF_CRM_1750679348 após o caractere "|"

## Exemplos de Valores

### Campo de Célula (UF_CRM_1746140099)
Formato no Bitrix: "Célula 1.17 - ANA PAULA PAZZIN BITTENCOURT - 18448 | 15"
- Código da Célula: 18448
- Código do Corner: 15

### Campo de Produto (UF_CRM_1748604707924)
```
[table][tr][th]Produto[/th][th]Valor[/th][/tr][tr][td]VERDE REGULARIZE - 857367[/td][td]R$ 3.000[/td][/tr][/table]
```
- Código do Produto: 857367

### Campo de Franqueado (UF_CRM_1750679348)
Formato no Bitrix: "ALTAIR ROBERTO FERREIRA JUNIOR | 943760"
- Código do Franqueado: 943760

## Possíveis Erros e Soluções

| Erro | Causa | Solução |
|------|-------|---------|
| "Conta gerencial não cadastrada" | Códigos de célula/corner incorretos | Verificar extração dos códigos do campo UF_CRM_1746140099 |
| "Data de Vencimento deve ser superior à Data de Faturamento" | Data de vencimento anterior à data de faturamento | Ajustar UF_CRM_1746140206 para data posterior a UF_CRM_1746140492 |
| "Este número de contrato já existe para este cliente" | Tentativa de criar contrato duplicado | Gerar novo número de contrato em UF_CRM_1746140348 |

## Dependências
- PHP 7.4+
- Extensões PHP: json, curl
- Acesso à API do Bitrix24
- Acesso ao webservice do Radar
