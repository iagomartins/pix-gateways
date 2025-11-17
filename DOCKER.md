# 🐳 Guia Docker

Este guia fornece informações detalhadas sobre a configuração Docker do projeto.

## Estrutura Docker

O projeto utiliza Docker Compose com os seguintes serviços:

### Serviços

1. **app** - Container PHP-FPM 8.2 com Laravel
   - Porta: 9000 (interno)
   - Volumes: Código da aplicação montado

2. **webserver** - Nginx Alpine
   - Porta: 8000 (externa)
   - Serve a aplicação Laravel

3. **db** - MySQL 8.0
   - Porta: 3306 (externa)
   - Database: `pix_gateways`
   - Usuário: `pix_gateways`
   - Senha: `root`

4. **redis** - Redis Alpine
   - Porta: 6379 (externa)
   - Usado para cache e filas

5. **queue** - Worker de filas Laravel
   - Processa jobs assíncronos
   - Reconecta automaticamente em caso de falha

## Início Rápido

```bash
# 1. Clone o repositório
git clone <url>
cd pix-gateways

# 2. Copie o arquivo .env
cp .env.example .env

# 3. Inicie os containers
docker-compose up -d --build

# 4. Acesse a aplicação
# http://localhost:8000
```

## Comandos Úteis

### Gerenciamento de Containers

```bash
# Iniciar containers
docker-compose up -d

# Parar containers
docker-compose stop

# Parar e remover containers
docker-compose down

# Parar, remover containers e volumes (limpa banco)
docker-compose down -v

# Reconstruir containers
docker-compose up -d --build

# Ver status
docker-compose ps
```

### Logs

```bash
# Todos os serviços
docker-compose logs -f

# Serviço específico
docker-compose logs -f app
docker-compose logs -f queue
docker-compose logs -f db
```

### Executar Comandos

```bash
# Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan tinker

# Acessar shell do container
docker-compose exec app bash

# Acessar MySQL
docker-compose exec db mysql -u pix_gateways -proot pix_gateways
```

### Usando Makefile

```bash
# Ver todos os comandos disponíveis
make help

# Comandos principais
make build    # Constrói as imagens
make up       # Inicia os containers
make down     # Para os containers
make logs     # Mostra os logs
make shell    # Acessa o shell
make migrate  # Executa migrations
make seed     # Executa seeders
make fresh    # Recria banco e executa migrations/seeders
```

## Configuração

### Variáveis de Ambiente

O arquivo `.env` já está configurado para Docker:

```env
DB_HOST=db
DB_DATABASE=pix_gateways
DB_USERNAME=pix_gateways
DB_PASSWORD=root

REDIS_HOST=redis
QUEUE_CONNECTION=redis
```

### Portas

- **8000** - Aplicação web (Nginx)
- **3306** - MySQL
- **6379** - Redis

Para alterar as portas, edite o arquivo `docker-compose.yml`.

## Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker-compose logs app

# Reconstruir do zero
docker-compose down -v
docker-compose up -d --build
```

### Erro de permissões

```bash
# Ajustar permissões
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

### Banco de dados não conecta

```bash
# Verificar se o MySQL está rodando
docker-compose ps db

# Ver logs do MySQL
docker-compose logs db

# Testar conexão
docker-compose exec app php artisan db:monitor
```

### Filas não processam

```bash
# Verificar logs do worker
docker-compose logs queue

# Reiniciar worker
docker-compose restart queue
```

## Desenvolvimento

### Hot Reload

O código é montado como volume, então mudanças são refletidas imediatamente. Apenas reinicie o container se necessário:

```bash
docker-compose restart app
```

### Instalar Nova Dependência

```bash
# Via container
docker-compose exec app composer require vendor/package

# Ou localmente (se tiver composer instalado)
composer require vendor/package
```

### Debug

Para habilitar debug, edite o `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Produção

Para produção, considere:

1. Usar variáveis de ambiente seguras
2. Configurar SSL/HTTPS
3. Usar volumes nomeados para persistência
4. Configurar backup automático do banco
5. Usar supervisor para workers
6. Configurar limites de recursos

## Recursos

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker](https://laravel.com/docs/deployment#docker)

