install:
	composer install
lint:
	composer run-script phpcs -- --standard=PSR12 src bin tests
test:
	composer run-script phpunit tests
test_coverage:
	composer phpunit -- --coverage-clover ./clover.xml tests