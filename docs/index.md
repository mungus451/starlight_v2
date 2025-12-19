---
layout: default
title: StarlightDominion V2 Documentation
---

# StarlightDominion V2

A browser-based space strategy MMO built with PHP 8.4, MariaDB, and vanilla JavaScript. Features turn-based mechanics, alliances, combat, espionage, and resource management.

## Quick Start

- **[Development Guide](DEVELOPMENT.md)** — Set up your local environment and run the project
- **[Architecture](ARCHITECTURE.md)** — Understand the MVC design and core components
- **[Docker Guide](DOCKER.md)** — Use Docker for consistent local development

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

## Development

Agents available to assist with specific development areas:

- **[Backend Agent](agents/backend-agent.md)** — PHP/MariaDB development and MVC patterns
- **[Database Architect](agents/database-architect.md)** — Schema design and migrations
- **[Frontend Agent](agents/frontend-agent.md)** — UI/UX and vanilla JavaScript
- **[Game Balance Architect](agents/game-balance-architect.md)** — Game mechanics and economy

## Game Balance

Explore the comprehensive game balance documentation:

- **[Overhaul Proposal](../BALANCE_OVERHAUL_PROPOSAL.md)** — Anti-oligarchy system design (proposal stage)
- **[Analysis & Improvements](../game-balance-analysis-improvements.md)** — Detailed balance analysis
- See more under the [Game Balance](game-balance-overview.md) section

## Resources

- **[Repository](https://github.com/mungus451/starlight_v2)** — Source code on GitHub
- **[Development](DEVELOPMENT.md)** — Setup and workflow guide
- **[Architecture](ARCHITECTURE.md)** — System design and patterns

**Focus Areas:**
- Developer documentation
- Architecture documentation
- Setup guides and tutorials
- API reference documentation
- Code example documentation

## Project Context

StarlightDominion V2 is a browser-based space strategy MMO built with:

- **Backend:** PHP 8.4 with strict MVC architecture
- **Database:** MariaDB with PDO prepared statements
- **Frontend:** Vanilla JavaScript with semantic HTML
- **Routing:** FastRoute (nikic/fast-route)
- **Sessions:** Redis with RedisSessionHandler
- **Architecture:** Service/Repository pattern with dependency injection

### Key Principles

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

## Getting Started

### Setting Up Your Environment

```bash
# Clone the repository
git clone https://github.com/mungus451/starlight_v2.git
cd starlight_v2

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env
```

### Running the Development Server

```bash
# From project root
php -S localhost:8000 -t public
```

### Processing Game Turns

```bash
# Manual turn processing (economy, income, etc.)
php cron/process_turn.php

# NPC processing
php cron/process_npcs.php
```

### Running Tests

```bash
# MVC compliance check
php tests/verify_mvc_compliance.php

# Architecture audit
php tests/StrictArchitectureAudit.php

# Lint check
php tests/mvc_lint.php
```

## Choosing the Right Agent

When working on StarlightDominion V2, select the appropriate agent based on your task:

| Task | Agent | Related Documentation |
|------|-------|----------------------|
| Implementing new backend feature | Backend Agent | [Backend](/docs/agents/backend-agent.md) |
| Database schema or migration | Database Architect | [Database](/docs/agents/database-architect.md) |
| UI component or JavaScript | Frontend Agent | [Frontend](/docs/agents/frontend-agent.md) |
| Game mechanic or balance | Game Balance Architect | [Balance](/docs/agents/game-balance-architect.md) |
| Security review or vulnerability | Security Agent | [Security](/docs/agents/security-agent.md) |
| Writing or updating tests | Testing Agent | [Testing](/docs/agents/testing-agent.md) |
| Code review or refactoring | Code Review Agent | [Review](/docs/agents/review-agent.md) |
| Documentation or guides | Documentation Agent | [Docs](/docs/agents/docs-agent.md) |

## Common Development Workflows

### Adding a New Game Feature

1. **Start with Balance**: Define constants in `/config/game_balance.php`
2. **Backend Implementation**: Use Backend Agent to implement Service/Repository logic
3. **Database**: Use Database Architect if schema changes needed
4. **Controller**: Add controller action and route
5. **Frontend**: Use Frontend Agent for UI and JavaScript
6. **Testing**: Use Testing Agent to write comprehensive tests
7. **Security**: Use Security Agent to review
8. **Documentation**: Use Documentation Agent to document

### Bug Fix Workflow

1. **Identify**: Use Code Review Agent to understand the issue
2. **Test**: Use Testing Agent to write a failing test
3. **Fix**: Use appropriate specialist agent (Backend, Frontend, etc.)
4. **Verify**: Ensure tests pass and no regressions
5. **Review**: Use Code Review Agent for final review

### Refactoring

1. **Plan**: Use Code Review Agent to identify areas
2. **Implement**: Use Backend Agent or appropriate specialist
3. **Test**: Use Testing Agent to ensure coverage
4. **Validate**: Run architecture tests
5. **Document**: Update Documentation Agent docs

## Additional Resources

- [Project Repository](https://github.com/mungus451/starlight_v2)
- [Main README](../README.md)
- [Docker Setup](../DOCKER.md)
- [Database Schema](../database.sql)

## Directory Structure

```
starlight_v2/
├── app/
│   ├── Controllers/          # HTTP request handlers
│   ├── Models/
│   │   ├── Services/         # Business logic layer
│   │   ├── Repositories/     # Database access layer
│   │   ├── Entities/         # Data objects
│   │   └── ...
│   ├── Core/                 # Framework utilities
│   └── ...
├── config/                   # Game balance and settings
├── cron/                     # Turn processing
├── public/                   # Web-accessible files
├── views/                    # PHP templates
├── migrations/               # Database migrations
├── tests/                    # Test files
├── docs/                     # This documentation
└── ...
```

---

**Last Updated:** December 2025

For questions or contributions, refer to the individual agent documentation pages.
