# Architecture & Testing Suite

This project adheres to strict MVC-S standards. The testing suite provided ensures these standards are maintained during development.

## 1. Strict Architecture Audit

To verify that Controllers, Services, Repositories, Entities, and Views adhere to strict separation of concerns, run:

```bash
docker exec starlight_app php tests/StrictArchitectureAudit.php
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
docker exec starlight_app php tests/verify_di_resolution.php
```

## 3. Feature Logic Tests

Individual feature tests ensure complex calculations (like game balance formulas) are working as expected:

```bash
# Alliance Structure Bonuses
docker exec starlight_app php tests/AllianceStructureBonusTest.php

# Refactor Stability (End-to-End simulation)
docker exec starlight_app php tests/verify_refactor.php
```

## 4. Linting

For general MVC compliance checks (Regex/Token based):

```bash
docker exec starlight_app php tests/mvc_lint.php
```