test:
	@vendor/bin/grumphp run --testsuite=default --no-interaction

ecs:
	@vendor/bin/grumphp run --testsuite=ecs --no-interaction

fix:
	@vendor/bin/ecs check --fix
