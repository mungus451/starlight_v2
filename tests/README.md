# Architecture & Testing Suite

This project adheres to strict MVC-S standards. The testing suite provided ensures these standards are maintained during development.

## Directory Structure

| Directory | Type | Description |
|-----------|------|-------------|
| **Unit/** | PHPUnit | Pure unit tests with mocked dependencies. No Database, Redis, or File I/O. |
| **Integration/** | PHPUnit/Scripts | Tests involving the Service Container, Database, and real dependencies. |
| **Compliance/** | Static Analysis | Architectural audits, linting, and rule enforcement scripts. |
| **Simulations/** | Scripts | Game loop simulations and non-assertive exploration tools. |

## 1. Strict Architecture Audit

To verify that Controllers, Services, Repositories, Entities, and Views adhere to strict separation of concerns, run:

```bash
docker exec starlight_app php tests/Compliance/StrictArchitectureAudit.php
```

**What it checks:**
- **Controllers:** Cannot inject Repositories directly. Cannot emit raw SQL. Cannot echo output.
- **Services:** Cannot inject Session or Controllers. Cannot echo output. No HTML.
- **Repositories:** Must not depend on Services.
- **Entities:** Must be `readonly` classes (DTOs).
- **Views:** Cannot access DB. Cannot instantiate objects (`new Class`).

## 2. Dependency Injection Verification

To ensure all Controllers can be instantiated by the container (verifying all dependencies are resolvable):

```bash
docker exec starlight_app php tests/Compliance/verify_di_resolution.php
```

## 3. Feature Logic Tests

Individual feature tests ensure complex calculations (like game balance formulas) are working as expected. These are standalone scripts:

```bash
# Alliance Structure Bonuses
docker exec starlight_app php tests/Integration/AllianceStructureBonusCheck.php

# Armory Integration
docker exec starlight_app php tests/Integration/ArmoryIntegrationCheck.php

# Bank System
docker exec starlight_app php tests/Integration/BankSystemCheck.php

# Refactor Stability (End-to-End simulation)
docker exec starlight_app php tests/Compliance/verify_refactor.php
```

## 4. Simulations

To run game loop simulations:

```bash
docker exec starlight_app php tests/Simulations/BattleSimulation.php
docker exec starlight_app php tests/Simulations/GameLoopSimulation.php
```

## 5. Linting

For general MVC compliance checks (Regex/Token based):

```bash
docker exec starlight_app php tests/Compliance/mvc_lint.php
```

## 5. Compliance Suite

To run all compliance checks in one go:

```bash
docker exec starlight_app php tests/Compliance/run_compliance_suite.php
```
