docs:
	wget http://apigen.org/apigen.phar
	chmod +x apigen.phar
	php apigen.phar generate --source=src --destination=docs --template-theme=bootstrap
	rm apigen.phar

image:
	docker build -t matthiasmullie/minify .

up:
	docker-compose up -d php

down:
	docker-compose stop -t0 php

test:
	[ $(UP) -eq 1 ] && make up || true
	$(eval cmd='docker-compose run php vendor/bin/phpunit')
	eval $(cmd); status=$$?; [ $(DOWN) -eq 1 ] && make down; exit $$status
