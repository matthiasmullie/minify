PHP ?= '8.1'
UP ?= 1
DOWN ?= 1
TEST ?=

docs:
	wget http://apigen.org/apigen.phar
	chmod +x apigen.phar
	php apigen.phar generate --source=src --destination=docs --template-theme=bootstrap
	rm apigen.phar

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
