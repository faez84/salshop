
default: help

pwd := $(shell pwd)

dc = docker compose  -f $(pwd)/docker-compose.yml --env-file $(pwd)/app/.env
oc := $(dc) exec -T php  vendor/bin/oe-console

.PHONY: up ## Start the development environment
up:
	$(dc) up --build -d

.PHONY: up-with-recreate ## Create development environment and force creation of new docker
up-with-recreate:
	$(dc) up --build -d --force-recreate

.PHONY: down ## Stop the development environment
down:
	$(dc) down --remove-orphans
