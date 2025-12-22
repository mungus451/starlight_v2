---
layout: default
title: StarlightDominion V2 Documentation
---

# StarlightDominion V2

A browser-based space strategy MMO built with PHP 8.4, MariaDB, and vanilla JavaScript. Features turn-based mechanics, alliances, combat, espionage, and resource management.

## Quick Start

- **Development Guide** — See [Getting Started](getting-started/index.md)
- **Architecture** — See [Architecture Overview](architecture/index.md)
- **Docker Guide** — See [Docker Setup](DOCKER.md)

## Core Concepts

### Tech Stack
- **Backend:** PHP 8.4, MariaDB, PDO
- **Frontend:** Vanilla JavaScript, semantic HTML/CSS
- **Architecture:** Strict MVC with Service/Repository pattern
- **Routing:** FastRoute for URL handling

### Key Components

- **Controllers** — HTTP request handlers in `app/Controllers/`
- **Services** — Business logic layer in `app/Models/Services/`
- **Repositories** — Database access in `app/Models/Repositories/`
- **Entities** — Data objects in `app/Models/Entities/`
- **Views** — PHP templates in `views/`
- **Configuration** — Game balance and settings in `config/`

## Key Principles

1. **Strict MVC Architecture**
   - Controllers: HTTP handlers only
   - Services: Business logic coordination
   - Repositories: Database queries
   - Entities: Immutable data objects

2. **Security First**
   - CSRF protection on all forms
   - Prepared statements for all SQL
   - Session-based authentication
   - Role-based authorization

3. **Transaction Safety**
   - Multi-table operations wrapped in transactions
   - Rollback on failure
   - Consistent application state

4. **Game Balance Centralization**
   - All constants in `/config/game_balance.php`
   - No hardcoded values in application code
   - Rational progression curves

5. **Testing and Validation**
   - Comprehensive test coverage
   - Architecture compliance validation
   - Security auditing

## Development

Agents available to assist with specific development areas:

- **Backend Agent** — [agents/backend/index.md](agents/backend/index.md)
- **Database Architect** — [agents/database/index.md](agents/database/index.md)
- **Frontend Agent** — [agents/frontend/index.md](agents/frontend/index.md)
- **Game Balance Architect** — [agents/game-balance/index.md](agents/game-balance/index.md)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development workflow, code standards, and how to add features.

## Game Balance

Explore the comprehensive game balance documentation:

- **Overview:** [game-balance/index.md](game-balance/index.md)
- **Overhaul Proposal:** [balance-overhaul/00-INDEX.md](balance-overhaul/00-INDEX.md)

## Features

Browse game features by category:

- **[Authentication](features/authentication.md)** — Secure login and session management
- **[Economy](features/economy/index.md)** — Banking, structures, and training
- **[Combat](features/combat/index.md)** — Attack, defense, and war systems
- **[Alliances](features/alliances/index.md)** — Team play and governance
- **[Espionage](features/espionage.md)** — Intelligence operations
- **[Armory](features/armory.md)** — Equipment manufacturing
- **[Black Market](features/black-market.md)** — Special trading

## Guides

Cross-cutting concerns:

- **[Security](guides/security.md)** — Authentication, CSRF, RBAC, and safe practices
- **[Accessibility](guides/accessibility.md)** — Building inclusive interfaces
- **[Performance](guides/performance.md)** — Optimization strategies

## Resources

- **[Repository](https://github.com/mungus451/starlight_v2)** — Source code on GitHub
- **[Development](DEVELOPMENT.md)** — Setup and workflow guide
- **[Architecture](architecture/index.md)** — System design and patterns

**Focus Areas:**
- Developer documentation
- Architecture documentation
- Setup guides and tutorials
- API reference documentation
- Code example documentation

**Ready to start?** See [Getting Started](getting-started/index.md) for installation and setup instructions.
