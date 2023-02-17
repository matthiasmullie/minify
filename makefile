PHP ?=
TEST ?=

docs:
	docker run --rm -v $$(pwd)/src:/data/src -v $$(pwd)/docs:/data/docs -w /data php:cli bash -c "\
		curl -s -L -O https://phpdoc.org/phpDocumentor.phar;\
		php phpDocumentor.phar --directory=src --target=docs --visibility=public --defaultpackagename=Minify --title=Minify;"

test:
	VERSION=$$(echo "$(PHP)-cli" | sed "s/^-//");\
	test $$(docker images -q matthiasmullie/minify:$$VERSION) || docker build -t matthiasmullie/minify:$$VERSION . --build-arg VERSION=$$VERSION;\
	docker run -v $$(pwd)/src:/var/www/src -v $$(pwd)/tests:/var/www/tests -v $$(pwd)/build:/var/www/build matthiasmullie/minify:$$VERSION env XDEBUG_MODE=coverage vendor/bin/phpunit $(TEST) --coverage-clover build/coverage-$(PHP)-$(TEST).clover

format:
	test $$(docker images -q matthiasmullie/minify:cli) || docker build -t matthiasmullie/minify:cli .
	docker run -v $$(pwd)/src:/var/www/src -v $$(pwd)/tests:/var/www/tests matthiasmullie/minify:cli sh -c "PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix && vendor/bin/phpcbf --standard=ruleset.xml"

.PHONY: docs
