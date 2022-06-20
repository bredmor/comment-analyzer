build:
	docker build --tag=php-for-dev:8.1 `pwd`
	docker run -it --rm -v `pwd`:/var/www/project -w /var/www/project  php-for-dev:8.1 sh