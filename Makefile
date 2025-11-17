.PHONY: help build up down restart logs shell migrate seed test queue

help: ## Mostra esta mensagem de ajuda
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Constrói as imagens Docker
	docker-compose build

up: ## Inicia os containers
	docker-compose up -d

down: ## Para e remove os containers
	docker-compose down

restart: ## Reinicia os containers
	docker-compose restart

logs: ## Mostra os logs dos containers
	docker-compose logs -f

shell: ## Acessa o shell do container da aplicação
	docker-compose exec app bash

migrate: ## Executa as migrations
	docker-compose exec app php artisan migrate

seed: ## Executa os seeders
	docker-compose exec app php artisan db:seed

fresh: ## Recria o banco de dados e executa migrations e seeders
	docker-compose exec app php artisan migrate:fresh --seed

test: ## Executa os testes
	docker-compose exec app php artisan test

queue: ## Mostra os logs do worker de filas
	docker-compose logs -f queue

route-clear: ## Limpa o cache de rotas
	docker-compose exec app php artisan route:clear

route-list: ## Lista todas as rotas
	docker-compose exec app php artisan route:list

clean: ## Para containers, remove volumes e limpa tudo
	docker-compose down -v
	docker system prune -f

