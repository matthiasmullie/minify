docs:
	wget http://apigen.org/apigen.phar
	chmod +x apigen.phar
	php apigen.phar generate --source=src --destination=docs --template-theme=bootstrap
	rm apigen.phar

image:
	docker build -t matthiasmullie/minify .

test:
	[ ! -z `docker images -q matthiasmullie/minify` ] || make image
	docker run --rm --name minify -v `pwd`:/var/www matthiasmullie/minify vendor/bin/phpunit
