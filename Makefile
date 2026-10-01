DC = docker compose

.PHONY: help up down restart build logs shell composer migrate rollback init clean

help: ## Показать справку
@echo ""
@echo "Доступные команды:"
@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
@echo ""

up: ## Поднять контейнеры
$(DC) up -d

down: ## Остановить контейнеры
$(DC) down

restart: down up ## Перезапустить контейнеры

build: ## Пересобрать образ php без кэша
$(DC) build --no-cache php

logs: ## Логи в реальном времени
$(DC) logs -f

shell: ## Зайти в контейнер php
$(DC) exec php sh

composer: ## Установить Composer-зависимости
$(DC) exec php composer install

migrate: ## Применить миграции
$(DC) exec php php bin/migrate.php

rollback: ## Откатить последнюю миграцию
$(DC) exec php php bin/migrate.php rollback

init: up composer migrate ## Первый запуск: контейнеры, composer, миграции
@echo ""
@echo "Готово. Открыть: http://localhost:8080"
@echo ""

clean: ## Остановить контейнеры и удалить volume с MySQL
$(DC) down -v
