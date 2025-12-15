---
layout: default
title: AI Agent Documentation
---

# StarlightDominion V2 - AI Agent Documentation

This documentation describes the AI agents that assist with StarlightDominion V2 development. Each agent specializes in a specific aspect of the project and follows tailored best practices and architectural patterns.

## Available Agents

### [Backend Agent](/docs/agents/backend-agent.md)
Senior backend engineer for PHP/MariaDB development. Specializes in implementing backend features following strict MVC patterns, managing the Service/Repository architecture, and maintaining database integrity.

**Focus Areas:**
- PHP 8.4+ and MVC architecture
- Service layer business logic
- Repository pattern and database access
- Dependency injection and transactions
- Game mechanics implementation

### [Database Architect](/docs/agents/database-architect.md)
Database specialist for schema design, migrations, and data model architecture. Expert in relational database design, optimization, and safe migration patterns.

**Focus Areas:**
- Schema design and normalization
- Migration strategies and data integrity
- Query optimization and performance
- Safe evolution of the database
- Transaction management

### [Frontend Agent](/docs/agents/frontend-agent.md)
Frontend specialist for UI/UX development and vanilla JavaScript. Focuses on user interface, user experience, and client-side interactivity.

**Focus Areas:**
- HTML templates and semantic markup
- CSS styling and responsive design
- Vanilla JavaScript interactions
- Form validation and CSRF protection
- Accessibility and user experience

### [Game Balance Architect](/docs/agents/game-balance-architect.md)
Game designer and balance specialist focused on game mechanics, economy balance, and player progression. Ensures engaging and fair gameplay.

**Focus Areas:**
- Game mechanics design
- Economy and resource balance
- Progression curves and systems
- Competitive balance and fairness
- Preventing exploitation

### [Security Agent](/docs/agents/security-agent.md)
Security specialist for identifying vulnerabilities and ensuring defensive best practices. Protects both game integrity and user information.

**Focus Areas:**
- Authentication and authorization
- CSRF and XSS prevention
- SQL injection prevention
- Game security and exploit prevention
- Data protection and privacy

### [Testing Agent](/docs/agents/testing-agent.md)
Quality assurance specialist focused on comprehensive testing and test coverage. Ensures reliability and prevents regressions.

**Focus Areas:**
- Unit testing and integration tests
- Game simulation testing
- Test coverage and edge cases
- Architecture validation
- Performance testing

### [Code Review Agent](/docs/agents/review-agent.md)
Code review specialist evaluating architecture, patterns, and best practices. Ensures long-term codebase health and maintainability.

**Focus Areas:**
- MVC architecture compliance
- Design patterns and best practices
- Performance optimization
- Code clarity and maintainability
- Security and stability

### [Documentation Agent](/docs/agents/docs-agent.md)
Expert technical writer for project documentation. Creates and maintains developer-focused documentation for the codebase.

**Focus Areas:**
- Developer documentation
- Architecture documentation
- Setup guides and tutorials
- API reference documentation
- Code example documentation

## Project Context

StarlightDominion V2 is a browser-based space strategy MMO built with:

- **Backend:** PHP 8.3 with strict MVC architecture
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
