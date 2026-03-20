---
name: docker-unit-tests
description: 'Run PHPUnit unit tests in this repository using Docker Compose. Use when asked to run unit tests, PHPUnit, testsuite Unit, a specific unit test file, or a filtered unit test method inside the app container instead of the host machine. Starts or validates the app/db/redis containers, runs docker compose exec app ./vendor/bin/phpunit --testsuite Unit, and handles common container/bootstrap issues such as missing dependencies or host PHP version mismatch.'
argument-hint: 'What unit tests should be run in Docker? Examples: all unit tests, a specific file, or a --filter expression.'
user-invocable: true
---

# Docker Unit Tests

## When to Use
- Run the Unit PHPUnit suite for this project.
- Run a specific unit test file or a specific test method.
- Verify PHP changes without relying on the host PHP runtime.
- Avoid host environment problems such as the local PHP version being lower than the version required by Composer.

## Project Context
- The Docker Compose `app` service runs the PHP application.
- The Docker Compose stack also includes `db` and `redis`, which the app service depends on.
- The canonical unit test suite name is `Unit` in `phpunit.xml`.
- The standard host command in project docs is `./vendor/bin/phpunit --testsuite Unit`, but for this repo the preferred agent workflow is to run that command inside the `app` container.

## Procedure
1. Check whether the required containers are available.
   Preferred command:
   ```bash
   docker compose ps
   ```

2. If `app`, `db`, or `redis` are not running, start them first.
   Preferred command:
   ```bash
   docker compose up -d app db redis
   ```
   If the containers do not exist yet or the image changed, use:
   ```bash
   docker compose up -d --build app db redis
   ```

3. Run the requested unit tests inside the `app` container.
   For the full unit suite:
   ```bash
   docker compose exec app ./vendor/bin/phpunit --testsuite Unit
   ```

4. If the user asked for a narrower scope, prefer one of these forms:
   Specific test file:
   ```bash
   docker compose exec app ./vendor/bin/phpunit tests/Unit/Services/TrainingServiceTest.php
   ```
   Specific test method or pattern:
   ```bash
   docker compose exec app ./vendor/bin/phpunit --testsuite Unit --filter testTrainUnitsSuccessfullyTrainsSoldiers
   ```

5. Summarize the result clearly.
   Report:
   - whether the command passed or failed
   - the failing test names if any
   - the first actionable failure cause
   - whether the failure looks like an app/test issue or a container/bootstrap issue

## Decision Points
- If the user asks to "run unit tests", default to:
  ```bash
  docker compose exec app ./vendor/bin/phpunit --testsuite Unit
  ```
- If the user names a file under `tests/Unit/`, run that file directly.
- If the user names a test method or says "just this test", use `--filter`.
- If `docker compose exec` fails because containers are not running, start `app`, `db`, and `redis`, then retry.
- If `vendor/autoload.php` or PHPUnit is missing inside the container, run:
  ```bash
  docker compose exec app composer install
  ```
  Then retry the test command.
- If the request is for integration or compliance tests, do not silently use this workflow. Switch to the appropriate suite or ask whether they want a separate Docker testing workflow created.

## Completion Checks
- The test command ran inside the `app` container, not on the host.
- The command exit code is captured.
- The response includes the pass/fail outcome and key failing tests.
- Any container startup or dependency bootstrap steps used are mentioned.

## Common Issues
- `docker compose exec` says the service is not running:
  Start the stack with `docker compose up -d app db redis`.
- Composer platform mismatch on the host:
  Do not run host PHPUnit. Use the container workflow instead.
- Missing dependencies inside the container:
  Run `docker compose exec app composer install` and retry.
- The user asks for coverage:
  Coverage may require extra PHP extensions inside the container. Mention that constraint before assuming coverage is available.

## Examples
- `/docker-unit-tests all unit tests`
- `/docker-unit-tests tests/Unit/Services/TrainingServiceTest.php`
- `/docker-unit-tests --filter testTrainUnitsSuccessfullyTrainsSoldiers`
- `Run the unit suite in Docker`
- `Use the app container to run PHPUnit for this service change`
