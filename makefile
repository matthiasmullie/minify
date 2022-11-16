PHP ?= '8.1'
UP ?= 1
DOWN ?= 1
TEST ?=

docs:
	docker run --rm -v $$(pwd)/src:/data/src -v $$(pwd)/docs:/data/docs -w /data php:cli bash -c "\
		curl -s -L -O https://phpdoc.org/phpDocumentor.phar;\
		php phpDocumentor.phar --directory=src --target=docs --visibility=public --defaultpackagename=Minify --title=Minify;"

image:
	docker build -t matthiasmullie/minify .

up:
	docker-compose up -d php-$(PHP)

down:
	docker-compose stop -t0 php-$(PHP)

test:
	[ $(UP) -eq 1 ] && make up || true
	$(eval cmd='docker-compose run php-$(PHP) env XDEBUG_MODE=coverage vendor/bin/phpunit $(TEST)')
	eval $(cmd); status=$$?; [ $(DOWN) -eq 1 ] && make down; exit $$status

.PHONY: docs
