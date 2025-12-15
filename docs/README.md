---
layout: default
title: Quick Navigation
---

# AI Agents Documentation

Complete documentation for all AI agents involved in StarlightDominion V2 development.

## Quick Navigation

- **[Main Overview](index.md)** - Start here for overview of all agents
- **[Backend Agent](agents/backend-agent.md)** - PHP/MariaDB backend development
- **[Database Architect](agents/database-architect.md)** - Schema design and migrations
- **[Frontend Agent](agents/frontend-agent.md)** - UI/UX and JavaScript development
- **[Game Balance Architect](agents/game-balance-architect.md)** - Game mechanics and economy
- **[Security Agent](agents/security-agent.md)** - Vulnerability prevention and security
- **[Testing Agent](agents/testing-agent.md)** - Quality assurance and test coverage
- **[Code Review Agent](agents/review-agent.md)** - Architecture and pattern review
- **[Documentation Agent](agents/docs-agent.md)** - Technical writing and docs

## What Are These Agents?

These are specialized AI personalities designed to assist with different aspects of StarlightDominion V2 development. Each agent has deep knowledge of a specific domain and follows tailored best practices.

When you're working on the project, you can invoke the appropriate agent based on your task:

| Task | Agent |
|------|-------|
| Implementing backend features | Backend Agent |
| Database schema changes | Database Architect |
| Building UI components | Frontend Agent |
| Balancing game mechanics | Game Balance Architect |
| Security reviews | Security Agent |
| Writing tests | Testing Agent |
| Code review/refactoring | Code Review Agent |
| Writing documentation | Documentation Agent |

## Project Overview

**StarlightDominion V2** is a browser-based space strategy MMO built with:

- **Backend:** PHP 8.4 with strict MVC architecture
- **Database:** MariaDB with PDO
- **Frontend:** Vanilla JavaScript with HTML/CSS
- **Architecture:** Service/Repository pattern with dependency injection

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
   - Multi-table operations in transactions
   - Rollback on error
   - Consistent state guaranteed

4. **Centralized Configuration**
   - All game balance in `/config/game_balance.php`
   - No hardcoded values
   - Easy to adjust mechanics

5. **Comprehensive Testing**
   - Unit tests for business logic
   - Integration tests for systems
   - Architecture validation
   - Game mechanic simulation

## Getting Started

### Prerequisites

- PHP 8.4+
- MariaDB or MySQL 5.7+
- Composer
- Docker (optional, for containerization)

### Quick Setup

```bash
# Clone repository
git clone https://github.com/mungus451/starlight_v2.git
cd starlight_v2

# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with database credentials

# Start development server
php -S localhost:8000 -t public
```

### First Steps

1. Read the [Main Overview](index.md)
2. Identify what you're working on
3. Read the relevant agent documentation
4. Follow the patterns and best practices documented
5. Run tests and validation before committing

## Development Commands

```bash
# Development server
php -S localhost:8000 -t public

# Run tests
php tests/verify_mvc_compliance.php
php tests/StrictArchitectureAudit.php

# Process game turns
php cron/process_turn.php

# Database migrations
php migrations/filename.php

# Check for issues
php tests/mvc_lint.php
```

## Architecture Overview

```
StarlightDominion V2
├── app/
│   ├── Controllers/      # HTTP request handlers
│   ├── Models/
│   │   ├── Services/     # Business logic
│   │   ├── Repositories/ # Database access
│   │   └── Entities/     # Data objects
│   ├── Core/             # Framework utilities
│   └── ...
├── config/               # Game balance & settings
├── public/               # Web-accessible files
├── views/                # PHP templates
├── migrations/           # Database migrations
├── cron/                 # Scheduled tasks
├── tests/                # Test suite
└── docs/                 # This documentation
```

## Common Workflows

### Adding a New Game Feature

1. Define constants in `/config/game_balance.php`
2. Use Backend Agent for Service/Repository implementation
3. Add controller actions and routes
4. Use Frontend Agent for UI/JavaScript
5. Use Testing Agent to write tests
6. Use Security Agent for review
7. Use Documentation Agent for docs

### Fixing a Bug

1. Use Code Review Agent to understand the issue
2. Use Testing Agent to write a failing test
3. Use appropriate specialist agent to fix
4. Verify tests pass
5. Use Code Review Agent for review

### Refactoring Code

1. Plan with Code Review Agent
2. Implement with specialist agent
3. Ensure tests pass with Testing Agent
4. Review with Code Review Agent

## Resources

- **Repository:** [github.com/mungus451/starlight_v2](https://github.com/mungus451/starlight_v2)
- **Main README:** [../README.md](../README.md)
- **Docker Setup:** [../DOCKER.md](../DOCKER.md)
- **Database Schema:** [../database.sql](../database.sql)

## Documentation Maintenance

This documentation is maintained as the project evolves. When code changes:

1. Update relevant agent documentation
2. Keep examples current
3. Fix broken links
4. Add new patterns
5. Remove deprecated information

## Questions & Issues

If you have questions about:

- **Backend development** → See [Backend Agent](agents/backend-agent.md)
- **Database design** → See [Database Architect](agents/database-architect.md)
- **Frontend development** → See [Frontend Agent](agents/frontend-agent.md)
- **Game balance** → See [Game Balance Architect](agents/game-balance-architect.md)
- **Security** → See [Security Agent](agents/security-agent.md)
- **Testing** → See [Testing Agent](agents/testing-agent.md)
- **Code quality** → See [Code Review Agent](agents/review-agent.md)
- **Documentation** → See [Documentation Agent](agents/docs-agent.md)

## Last Updated

December 2025

---

**StarlightDominion V2** - A browser-based space strategy MMO
