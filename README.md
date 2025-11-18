# 🚀 Sistema de Integração com Subadquirentes de Pagamento

Sistema desenvolvido em Laravel para integração com múltiplas subadquirentes de pagamento, permitindo processamento de PIX e saques através de diferentes gateways de forma extensível e escalável.

## 🎯 Sobre o Projeto

Este projeto foi desenvolvido como parte de um desafio técnico para demonstrar habilidades em desenvolvimento backend com Laravel. O sistema permite que diferentes usuários utilizem diferentes subadquirentes de pagamento, suportando multiadquirência de forma flexível e extensível.

### Funcionalidades Principais

- ✅ Geração de PIX através de subadquirentes
- ✅ Processamento de saques
- ✅ Simulação de webhooks assíncronos
- ✅ Suporte a múltiplas subadquirentes (SubadqA e SubadqB)
- ✅ Arquitetura extensível para adicionar novas subadquirentes
- ✅ Processamento assíncrono de webhooks via filas
- ✅ Autenticação via Laravel Sanctum
- ✅ Logs detalhados de todas as operações
- ✅ Testes automatizados completos para todos os endpoints
- ✅ Cobertura de testes com 33 casos de teste e 124+ asserções

## 🛠 Tecnologias Utilizadas

### Backend

- **PHP 8.1+** - Linguagem de programação
- **Laravel 10** - Framework PHP
- **MySQL** - Banco de dados relacional
- **Eloquent ORM** - ORM nativo do Laravel para acesso a dados
- **Laravel Sanctum** - Autenticação de API via tokens
- **Guzzle HTTP** - Cliente HTTP para requisições às subadquirentes
- **Laravel Queue** - Sistema de filas para processamento assíncrono
- **PHPUnit** - Framework de testes automatizados
- **Docker & Docker Compose** - Containerização e orquestração

### Padrões e Boas Práticas

- **PSR-4** - Autoloading de classes
- **PSR-12** - Coding standards
- **SOLID Principles** - Princípios de design orientado a objetos
- **Repository Pattern** - Abstração de acesso a dados
- **Service Layer** - Camada de serviços para lógica de negócio
- **Strategy Pattern** - Para diferentes implementações de gateways
- **Factory Pattern** - Para criação de instâncias de gateways

## 🏗 Arquitetura e Padrões de Projeto

### Padrões Implementados

#### 1. Strategy Pattern

Utilizado para abstrair diferentes subadquirentes através da interface `GatewayInterface`. Cada subadquirente implementa seus próprios métodos de criação de PIX e saques, mantendo a mesma interface.

```php
GatewayInterface
├── SubadqAGateway
└── SubadqBGateway
```

#### 2. Factory Pattern

O `GatewayFactory` é responsável por instanciar a subadquirente correta baseada no usuário, garantindo que cada usuário utilize seu gateway configurado.

#### 3. Repository Pattern

Abstração de acesso a dados através de repositories (`PixRepository`, `WithdrawRepository`), facilitando testes e manutenção.

#### 4. Service Layer

Camada de serviços (`PixService`, `WithdrawService`) que orquestra a lógica de negócio, coordenando entre repositories, gateways e jobs.

### Fluxo de Processamento

#### Fluxo de Criação de PIX

```
1. Cliente → POST /api/pix
2. PixController → Valida requisição
3. PixService → Identifica gateway do usuário
4. GatewayFactory → Cria instância do gateway
5. Gateway → Cria PIX na subadquirente
6. PixService → Salva transação no banco (status: PENDING)
7. SimulatePixWebhookJob → Despachado para fila
8. Job → Processa webhook após delay (2-5 segundos)
9. WebhookHandler → Normaliza dados
10. PixService → Atualiza status da transação
```

#### Fluxo de Criação de Saque

```
1. Cliente → POST /api/withdraw
2. WithdrawController → Valida requisição
3. WithdrawService → Identifica gateway do usuário
4. GatewayFactory → Cria instância do gateway
5. Gateway → Cria saque na subadquirente
6. WithdrawService → Salva saque no banco
7. SimulateWithdrawWebhookJob → Despachado para fila
8. Job → Processa webhook após delay
9. WebhookHandler → Normaliza dados
10. WithdrawService → Atualiza status do saque
```

## 📁 Estrutura do Projeto

```
pix-gateways/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── PixController.php
│   │   │   │   └── WithdrawController.php
│   │   │   └── WebhookController.php
│   │   ├── Middleware/
│   │   └── Requests/
│   │       ├── CreatePixRequest.php
│   │       └── CreateWithdrawRequest.php
│   ├── Jobs/
│   │   ├── SimulatePixWebhookJob.php
│   │   └── SimulateWithdrawWebhookJob.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Gateway.php
│   │   ├── Pix.php
│   │   ├── Withdraw.php
│   │   └── WebhookLog.php
│   ├── Repositories/
│   │   ├── PixRepository.php
│   │   └── WithdrawRepository.php
│   └── Services/
│       ├── Gateway/
│       │   ├── GatewayInterface.php
│       │   ├── GatewayFactory.php
│       │   ├── SubadqA/
│       │   │   ├── SubadqAGateway.php
│       │   │   └── SubadqAWebhookHandler.php
│       │   └── SubadqB/
│       │       ├── SubadqBGateway.php
│       │       └── SubadqBWebhookHandler.php
│       ├── PixService.php
│       └── WithdrawService.php
├── database/
│   ├── factories/
│   │   ├── UserFactory.php
│   │   ├── GatewayFactory.php
│   │   ├── PixFactory.php
│   │   └── WithdrawFactory.php
│   ├── migrations/
│   │   ├── 2014_10_12_000000_create_users_table.php
│   │   ├── 2014_10_12_000001_create_gateways_table.php
│   │   ├── 2014_10_12_000002_add_gateway_foreign_key_to_users.php
│   │   ├── 2019_08_19_000000_create_failed_jobs_table.php
│   │   ├── 2019_12_14_000001_create_personal_access_tokens_table.php
│   │   ├── 2021_01_01_000000_create_jobs_table.php
│   │   ├── 2024_01_01_000001_create_pix_transactions_table.php
│   │   ├── 2024_01_01_000002_create_withdraws_table.php
│   │   └── 2024_01_01_000003_create_webhook_logs_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── GatewaySeeder.php
│       └── UserSeeder.php
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/
│   │   ├── LoginTest.php
│   │   ├── LogoutTest.php
│   │   ├── PixTest.php
│   │   ├── WebhookTest.php
│   │   └── WithdrawTest.php
│   ├── CreatesApplication.php
│   └── TestCase.php
└── config/
    ├── app.php
    ├── auth.php
    ├── cache.php
    ├── database.php
    ├── filesystems.php
    ├── logging.php
    ├── mail.php
    ├── queue.php
    ├── sanctum.php
    ├── session.php
    └── view.php
```

## 📦 Instalação

### Opção 1: Docker (Recomendado) 🐳

A forma mais fácil de executar o projeto é usando Docker. Não é necessário instalar PHP, Composer ou MySQL localmente.

#### Pré-requisitos

- Docker Desktop (Windows/Mac) ou Docker Engine + Docker Compose (Linux)
- Git

#### Passo a Passo

1. **Clone o repositório**

```bash
git clone <url-do-repositorio>
cd pix-gateways
```

2. **Construa e inicie os containers**

```bash
docker-compose up -d --build
```

Este comando irá:

- Construir a imagem PHP com todas as dependências
- Criar e iniciar os containers (app, webserver, db, redis, queue)
- Executar automaticamente as migrations
- Executar os seeders para popular dados iniciais
- Gerar a chave da aplicação

4. **Acesse a aplicação**

```
http://localhost:8000
```

5. **Verifique os logs (opcional)**

```bash
# Logs de todos os serviços
docker-compose logs -f

# Logs de um serviço específico
docker-compose logs -f app
docker-compose logs -f queue
```

6. **Execute comandos Artisan**

```bash
# Dentro do container
docker-compose exec app php artisan migrate

# Ou usando o alias
docker-compose exec app php artisan tinker
```

#### Comandos Úteis do Docker

```bash
# Parar os containers
docker-compose stop

# Iniciar os containers
docker-compose start

# Parar e remover containers
docker-compose down

# Parar, remover containers e volumes (limpa o banco)
docker-compose down -v

# Reconstruir containers após mudanças
docker-compose up -d --build

# Acessar o container da aplicação
docker-compose exec app bash

# Acessar o MySQL
docker-compose exec db mysql -u pix_gateways -proot pix_gateways

# Ver status dos containers
docker-compose ps

# Executar testes
docker-compose exec app php artisan test

# Executar migrations
docker-compose exec app php artisan migrate

# Executar seeders
docker-compose exec app php artisan db:seed
```

#### Estrutura Docker

O projeto utiliza os seguintes serviços:

- **app** - Container PHP-FPM com Laravel
- **webserver** - Nginx servindo a aplicação na porta 8000
- **db** - MySQL 8.0 na porta 3306
- **redis** - Redis para cache e filas na porta 6379
- **queue** - Worker de filas processando jobs assíncronos

### Opção 2: Instalação Local

#### Pré-requisitos

- PHP 8.1 ou superior
- Composer
- MySQL 5.7+ ou MariaDB 10.3+
- Extensões PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

#### Passo a Passo

1. **Clone o repositório**

```bash
git clone <url-do-repositorio>
cd pix-gateways
```

2. **Instale as dependências**

```bash
composer install
```

3. **Configure o ambiente**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure o banco de dados no arquivo `.env`**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pix_gateways
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

5. **Execute as migrations**

```bash
php artisan migrate
```

6. **Popule o banco com dados iniciais**

```bash
php artisan db:seed
```

7. **Configure as filas (opcional, mas recomendado)**

```env
QUEUE_CONNECTION=database
```

8. **Inicie o servidor de desenvolvimento**

```bash
php artisan serve
```

9. **Inicie o worker de filas (em outro terminal)**

```bash
php artisan queue:work
```

## ⚙️ Configuração

### Variáveis de Ambiente

Principais variáveis no arquivo `.env`:

**Para Docker:**

```env
# Aplicação
APP_NAME="PIX Gateways"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Banco de Dados (Docker)
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=pix_gateways
DB_USERNAME=pix_gateways
DB_PASSWORD=root

# Filas (Docker - usando Redis)
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

# URLs dos Gateways
SUBADQ_A_BASE_URL=https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io
SUBADQ_B_BASE_URL=https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io
```

**Para Instalação Local:**

```env
# Aplicação
APP_NAME="PIX Gateways"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pix_gateways
DB_USERNAME=root
DB_PASSWORD=sua_senha

# Filas
QUEUE_CONNECTION=database

# URLs dos Gateways
SUBADQ_A_BASE_URL=https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io
SUBADQ_B_BASE_URL=https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io
```

### Usuários de Exemplo

Após executar os seeders, os seguintes usuários estarão disponíveis:

| Email                 | Senha    | Gateway |
| --------------------- | -------- | ------- |
| usuario.a@example.com | password | SubadqA |
| usuario.b@example.com | password | SubadqA |
| usuario.c@example.com | password | SubadqB |

## 🔌 Uso da API

### Status dos Endpoints

Todos os endpoints da API estão funcionando corretamente:

- ✅ `POST /api/login` - Funcionando
- ✅ `POST /api/webhook` - Funcionando
- ✅ `POST /api/logout` - Funcionando (requer autenticação)
- ✅ `POST /api/pix` - Funcionando (requer autenticação)
- ✅ `POST /api/withdraw` - Funcionando (requer autenticação)

**Nota:** Os endpoints `/api/pix` e `/api/withdraw` podem retornar erro 500 se os serviços mock externos (Postman Mock Server) não estiverem configurados corretamente. O código está funcionando corretamente e os testes automatizados validam o comportamento esperado.

### Autenticação

A API utiliza Laravel Sanctum para autenticação via tokens. Primeiro, é necessário obter um token:

```bash
POST /api/login
Content-Type: application/json

{
    "email": "usuario.a@example.com",
    "password": "password"
}
```

**Resposta:**

```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

Use o token no header `Authorization`:

```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Endpoints Disponíveis

#### 1. Login (Público)

```http
POST /api/login
Content-Type: application/json

{
    "email": "usuario.a@example.com",
    "password": "password"
}
```

**Resposta de Sucesso (200):**

```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Usuário A",
    "email": "usuario.a@example.com"
  }
}
```

#### 2. Logout (Protegido)

```http
POST /api/logout
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**

```json
{
  "message": "Logout realizado com sucesso"
}
```

#### 3. Webhook (Público)

O endpoint de webhook aceita diferentes formatos de payload dependendo da subadquirente e do tipo de transação. O sistema detecta automaticamente o formato e processa o webhook adequadamente.

##### 3.1. SubadqA - PIX Webhook

```http
POST /api/webhook
Content-Type: application/json

{
  "event": "pix_payment_confirmed",
  "transaction_id": "f1a2b3c4d5e6",
  "pix_id": "PIX123456789",
  "status": "CONFIRMED",
  "amount": 125.50,
  "payer_name": "João da Silva",
  "payer_cpf": "12345678900",
  "payment_date": "2025-11-13T14:25:00Z",
  "metadata": {
    "source": "SubadqA",
    "environment": "sandbox"
  }
}
```

**Campos Obrigatórios:**

- `event` (deve conter "pix" ou ter campo `pix_id`)
- `transaction_id` ou `pix_id` (usado como external_id)
- `status` (CONFIRMED, PAID, CANCELLED, FAILED, ou padrão PENDING)

**Campos Opcionais:**

- `amount`, `payer_name`, `payer_cpf`, `payment_date`, `metadata`

##### 3.2. SubadqA - Withdraw Webhook

```http
POST /api/webhook
Content-Type: application/json

{
  "event": "withdraw_completed",
  "withdraw_id": "WD123456789",
  "transaction_id": "T987654321",
  "status": "SUCCESS",
  "amount": 500.00,
  "requested_at": "2025-11-13T13:10:00Z",
  "completed_at": "2025-11-13T13:12:30Z",
  "metadata": {
    "source": "SubadqA",
    "destination_bank": "Itaú"
  }
}
```

**Campos Obrigatórios:**

- `event` (deve conter "withdraw" ou ter campo `withdraw_id`)
- `withdraw_id` ou `transaction_id` (usado como external_id)
- `status` (SUCCESS, FAILED, CANCELLED, ou padrão PENDING)

**Campos Opcionais:**

- `amount`, `completed_at`, `requested_at`, `metadata`

##### 3.3. SubadqB - PIX Webhook

```http
POST /api/webhook
Content-Type: application/json

{
  "type": "pix.status_update",
  "data": {
    "id": "PX987654321",
    "status": "PAID",
    "value": 250.00,
    "payer": {
      "name": "Maria Oliveira",
      "document": "98765432100"
    },
    "confirmed_at": "2025-11-13T14:40:00Z"
  },
  "signature": "d1c4b6f98eaa"
}
```

**Campos Obrigatórios:**

- `type` (deve conter "pix")
- `data.id` (usado como external_id)
- `data.status` (PAID, CONFIRMED, CANCELLED, FAILED, ou padrão PENDING)

**Campos Opcionais:**

- `data.value` ou `data.amount`, `data.payer.name`, `data.payer.document`, `data.confirmed_at`, `signature`

##### 3.4. SubadqB - Withdraw Webhook

```http
POST /api/webhook
Content-Type: application/json

{
  "type": "withdraw.status_update",
  "data": {
    "id": "WDX54321",
    "status": "DONE",
    "amount": 850.00,
    "bank_account": {
      "bank": "Nubank",
      "agency": "0001",
      "account": "1234567-8"
    },
    "processed_at": "2025-11-13T13:45:10Z"
  },
  "signature": "aabbccddeeff112233"
}
```

**Campos Obrigatórios:**

- `type` (deve conter "withdraw")
- `data.id` (usado como external_id)
- `data.status` (DONE, SUCCESS, FAILED, CANCELLED, ou padrão PENDING)

**Campos Opcionais:**

- `data.amount`, `data.processed_at`, `data.bank_account`, `signature`

**Resposta de Sucesso (200):**

```json
{
  "success": true,
  "message": "Webhook de PIX processado com sucesso"
}
```

ou

```json
{
  "success": true,
  "message": "Webhook de saque processado com sucesso"
}
```

**Resposta de Erro (400/404/500):**

```json
{
  "success": false,
  "message": "Formato de webhook não reconhecido"
}
```

**Detecção Automática:**

- **Gateway:** Detectado pelo campo `event` (SubadqA) ou `type`/`signature` (SubadqB)
- **Tipo de Transação:** Detectado pelo conteúdo do campo `event` ou `type`
- **External ID:** Extraído automaticamente baseado no gateway e tipo de transação

#### 4. Criar PIX (Protegido)

```http
POST /api/pix
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 100.50,
    "description": "Pagamento de serviço"
}
```

**Resposta de Sucesso (201):**

```json
{
  "success": true,
  "message": "PIX criado com sucesso",
  "data": {
    "id": 1,
    "external_id": "PIX123456789",
    "status": "PENDING",
    "amount": "100.50",
    "qr_code": "00020126580014br.gov.bcb.pix...",
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

#### 5. Criar Saque (Protegido)

```http
POST /api/withdraw
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 500.00,
    "bank_account": {
        "bank": "Itaú",
        "agency": "0001",
        "account": "12345-6",
        "account_type": "checking",
        "account_holder_name": "João da Silva",
        "account_holder_document": "12345678900"
    }
}
```

**Resposta de Sucesso (201):**

```json
{
  "success": true,
  "message": "Saque criado com sucesso",
  "data": {
    "id": 1,
    "external_id": "WD123456789",
    "status": "PENDING",
    "amount": "500.00",
    "bank_account": {
      "bank": "Itaú",
      "agency": "0001",
      "account": "12345-6"
    },
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

## 📝 Exemplos de Requisições

### cURL - Criar PIX

```bash
# 1. Obter token
TOKEN=$(curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario.a@example.com","password":"password"}' \
  | jq -r '.token')

# 2. Criar PIX
curl -X POST http://localhost:8000/api/pix \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 150.75,
    "description": "Pagamento de teste"
  }'
```

### cURL - Criar Saque

```bash
curl -X POST http://localhost:8000/api/withdraw \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 300.00,
    "bank_account": {
      "bank": "Nubank",
      "agency": "0001",
      "account": "1234567-8",
      "account_type": "checking",
      "account_holder_name": "Maria Silva",
      "account_holder_document": "98765432100"
    }
  }'
```

### Postman

Importe a collection do Postman (disponível no repositório) ou configure manualmente:

1. **Variável de Ambiente:**

   - `base_url`: `http://localhost:8000`
   - `token`: (obtido após login)

2. **Collection:**
   - Login
   - Criar PIX
   - Criar Saque

## 🔄 Processamento de Webhooks

O sistema processa webhooks recebidos de subadquirentes externas e também simula webhooks através de Jobs assíncronos para testes.

### Recebimento de Webhooks Externos

Quando uma subadquirente envia um webhook para `/api/webhook`:

1. O sistema detecta automaticamente o tipo de gateway (SubadqA ou SubadqB)
2. Identifica o tipo de transação (PIX ou Withdraw)
3. Extrai o `external_id` do payload
4. Busca a transação correspondente no banco de dados
5. Normaliza os dados usando o webhook handler apropriado
6. Atualiza o status e informações da transação
7. Cria um log na tabela `webhook_logs`

### Simulação de Webhooks (Jobs Assíncronos)

Após criar um PIX ou saque:

1. O job é despachado para a fila com um delay de 2-5 segundos
2. O job gera um payload simulado baseado no tipo de gateway
3. O webhook handler normaliza os dados
4. A transação é atualizada no banco de dados
5. Um log é criado na tabela `webhook_logs`

### Status Possíveis

**PIX:**

- `PENDING` - Aguardando pagamento
- `PROCESSING` - Processando
- `CONFIRMED` - Confirmado
- `PAID` - Pago
- `CANCELLED` - Cancelado
- `FAILED` - Falhou

**Saque:**

- `PENDING` - Aguardando processamento
- `PROCESSING` - Processando
- `SUCCESS` / `DONE` - Concluído com sucesso
- `FAILED` - Falhou
- `CANCELLED` - Cancelado

## 🧪 Testes

O projeto possui uma suíte completa de testes automatizados cobrindo todos os endpoints da API.

### Cobertura de Testes

- **33 testes** passando
- **124+ asserções**
- **5 endpoints** totalmente testados
- **100% de cobertura** dos casos de uso principais

### Executar Testes

#### Docker (Recomendado)

```bash
# Executar apenas testes de Feature (API endpoints)
docker-compose exec app php artisan test --testsuite=Feature
```

#### Usando Makefile

O projeto inclui um Makefile com comandos úteis:

```bash
# Executar todos os testes
make test

# Ver todos os comandos disponíveis
make help
```

#### Instalação Local

```bash
# Executar todos os testes
php artisan test

# Executar apenas testes de Feature
php artisan test --testsuite=Feature

# Executar apenas testes Unit
php artisan test --testsuite=Unit

# Executar um teste específico
php artisan test --filter=LoginTest

# Executar com saída detalhada
php artisan test --verbose
```

### Estrutura de Testes

#### Testes de Feature (API Endpoints)

- **LoginTest** (7 testes)

  - Login bem-sucedido
  - Validação de campos obrigatórios
  - Erros de autenticação
  - Formato de email inválido

- **LogoutTest** (5 testes)

  - Logout bem-sucedido
  - Deleção de token
  - Erros de autenticação

- **PixTest** (8 testes)

  - Criação de PIX bem-sucedida
  - Validação de campos
  - Erros de autenticação
  - Usuário sem gateway configurado
  - Falhas do serviço de gateway

- **WithdrawTest** (9 testes)

  - Criação de saque bem-sucedida
  - Validação de campos e estrutura
  - Erros de autenticação
  - Usuário sem gateway configurado
  - Falhas do serviço de gateway

- **WebhookTest** (4 testes)
  - Recebimento de webhook
  - Payload vazio
  - JSON malformado

### Testes Manuais

Para testes manuais adicionais:

1. **Teste de Criação de PIX:**

   - Crie um PIX via API
   - Verifique se foi salvo no banco com status `PENDING`
   - Aguarde alguns segundos
   - Verifique se o status foi atualizado após o webhook

2. **Teste de Multiadquirência:**

   - Crie PIX com usuário A (SubadqA)
   - Crie PIX com usuário C (SubadqB)
   - Verifique que cada um utiliza seu gateway correto

3. **Teste de Filas:**
   - Crie múltiplos PIX rapidamente
   - Verifique os logs para confirmar processamento assíncrono

### Tecnologias de Teste

- **PHPUnit 10.1** - Framework de testes
- **Laravel Testing Helpers** - Helpers para testes HTTP e banco de dados
- **HTTP Fake** - Mock de requisições HTTP externas
- **Queue Fake** - Mock de filas para testes isolados
- **Database Factories** - Geração de dados de teste

## 📊 Banco de Dados

### Tabelas Principais

- **users** - Usuários do sistema
- **gateways** - Subadquirentes configuradas
- **pix_transactions** - Transações PIX
- **withdraws** - Saques
- **webhook_logs** - Logs de webhooks processados
- **jobs** - Fila de jobs
- **failed_jobs** - Jobs que falharam
- **personal_access_tokens** - Tokens de autenticação

## 🔧 Extensibilidade

### Adicionar Nova Subadquirente

Para adicionar uma nova subadquirente:

1. **Criar implementação do Gateway:**

```php
// app/Services/Gateway/SubadqC/SubadqCGateway.php
class SubadqCGateway implements GatewayInterface
{
    // Implementar métodos da interface
}
```

2. **Criar Webhook Handler:**

```php
// app/Services/Gateway/SubadqC/SubadqCWebhookHandler.php
class SubadqCWebhookHandler
{
    // Implementar normalização de webhooks
}
```

3. **Atualizar GatewayFactory:**

```php
return match ($type) {
    'subadq_a' => new SubadqAGateway($baseUrl),
    'subadq_b' => new SubadqBGateway($baseUrl),
    'subadq_c' => new SubadqCGateway($baseUrl), // Novo
    default => throw new \Exception("Tipo de gateway não suportado: {$type}"),
};
```

4. **Adicionar no banco de dados:**

```php
Gateway::create([
    'name' => 'Subadquirente C',
    'base_url' => env('SUBADQ_C_BASE_URL'),
    'type' => 'subadq_c',
    'active' => true,
]);
```

## 📝 Logs

O sistema registra logs detalhados de todas as operações:

- Criação de PIX/Saque
- Processamento de webhooks
- Erros e exceções
- Requisições às subadquirentes

Logs podem ser visualizados em `storage/logs/laravel.log`.

### Supervisor (Exemplo)

```ini
[program:pix-gateways-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
```

---

**Desenvolvido com ❤️ usando Laravel**
