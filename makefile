PHP ?= 8.1
TEST ?=

docs:
	docker run --rm -v $$(pwd)/src:/data/src -v $$(pwd)/docs:/data/docs -w /data php:cli bash -c "\
		curl -s -L -O https://phpdoc.org/phpDocumentor.phar;\
		php phpDocumentor.phar --directory=src --target=docs --visibility=public --defaultpackagename=Minify --title=Minify;"

test:
	docker build -t matthiasmullie/minify:$(PHP) . --build-arg VERSION=$(PHP)-cli
	docker run -v $$(pwd)/build:/var/www/build matthiasmullie/minify:$(PHP) env XDEBUG_MODE=coverage vendor/bin/phpunit $(TEST) --coverage-clover build/coverage-$(PHP)-$(TEST).clover

.PHONY: docs
