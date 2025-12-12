---
layout: default
title: Security Agent
---

# Security Agent

**Role:** Security specialist for identifying vulnerabilities and ensuring defensive best practices in StarlightDominion V2

## Overview

The Security Agent specializes in identifying and preventing vulnerabilities in game systems, database operations, and user-facing features. This agent protects both the game integrity and user information.

## Expertise Areas

### Security Disciplines
- Web application security (OWASP)
- Game-specific security (exploit prevention)
- Database security and data protection
- Authentication and authorization
- Cryptography and key management
- Audit logging and monitoring

### Technology Stack

- **Language:** PHP 8.3
- **Database:** MariaDB with prepared statements
- **Authentication:** Session-based with Redis storage
- **Encryption:** Password hashing (bcrypt/argon2)
- **Transport:** HTTPS recommended

### Security Mechanisms

| Mechanism | Implementation |
|-----------|-----------------|
| CSRF Protection | BaseController tokens on all forms |
| Authentication | Session-based with AuthService |
| Authorization | Role-based access control (RBAC) |
| SQL Injection | Prepared statements for all queries |
| XSS Prevention | Output encoding with htmlspecialchars() |
| Session Storage | Redis with encrypted tokens |
| Password Hashing | PHP password_hash() with argon2 |

## Sensitive Areas

### High-Risk Operations

| Operation | Risk | Mitigation |
|-----------|------|-----------|
| User Authentication | Brute force, credential theft | Rate limiting, secure hashing |
| Resource Transfer | Unauthorized access, manipulation | Authorization checks, transactions |
| Battle Simulation | Combat result manipulation | Server-side validation |
| Espionage/Spying | Information leaks | Authorization, audit logging |
| Alliance Management | Unauthorized role changes | Role-based authorization |
| Admin Functions | Unauthorized admin access | Special authentication required |
| Leaderboard Updates | Ranking manipulation | Server-side recalculation |
| Private Communications | Message interception | Authorization, HTTPS |

### Protected Data

```
✓ Sensitive
- User credentials (passwords - never exposed)
- Session tokens (Redis-backed)
- Private player data (resources, units, position)
- Alliance relationships and diplomacy
- Espionage intelligence
- Private messages and communications
- Audit logs of sensitive operations
```

## Security Review Standards

### Secure Code Patterns

```php
// ✅ Secure - Input validation, authorization, prepared statements
class ResourceTransferController extends BaseController {
    public function __construct(private ResourceService $resourceService) {}
    
    public function transfer() {
        // 1. Validate CSRF token
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');
        
        // 2. Get user ID from authenticated session (never from request)
        $fromId = (int) $_SESSION['user_id'];
        if (!$fromId) {
            throw new AuthenticationException('Not authenticated');
        }
        
        // 3. Validate recipient ID
        $toId = (int) ($_POST['to_id'] ?? 0);
        if ($toId <= 0) {
            throw new InvalidArgumentException('Invalid recipient');
        }
        
        // 4. Validate amounts (prevent negative values)
        $amounts = array_map('intval', $_POST['amounts'] ?? []);
        if (min($amounts) < 0) {
            throw new InvalidArgumentException('Amounts must be positive');
        }
        
        // 5. Delegate to service (which handles authorization)
        $this->resourceService->transfer($fromId, $toId, $amounts);
    }
}

// Service layer handles authorization
class ResourceService {
    public function transfer(int $fromId, int $toId, array $amounts): void {
        // Verify sender exists and has resources
        $sender = $this->userRepository->findById($fromId);
        if (!$sender || $sender->resources < array_sum($amounts)) {
            throw new InsufficientResourcesException();
        }
        
        // Verify recipient exists
        $recipient = $this->userRepository->findById($toId);
        if (!$recipient) {
            throw new InvalidArgumentException('Recipient not found');
        }
        
        // Execute transfer in transaction
        $this->db->beginTransaction();
        try {
            $this->resourceRepository->deduct($fromId, $amounts);
            $this->resourceRepository->add($toId, $amounts);
            $this->logRepository->logTransfer($fromId, $toId, $amounts);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

// ❌ Insecure - Multiple vulnerabilities
class ResourceTransferController {
    public function transfer() {
        // ❌ No CSRF protection
        
        // ❌ Trusts user_id from GET parameter (can be spoofed)
        $fromId = $_GET['from_id'];
        $toId = $_GET['to_id'];
        
        // ❌ Direct SQL injection vulnerability
        $query = "SELECT * FROM users WHERE id = " . $fromId;
        $result = $db->query($query);
        
        // ❌ No validation, negative values allowed
        $amount = $_GET['amount'];
        if ($amount < 0) {
            // Still accepts negative amounts!
        }
        
        // ❌ Direct SQL update without transaction
        $db->query("UPDATE users SET resources = resources - $amount WHERE id = $fromId");
        $db->query("UPDATE users SET resources = resources + $amount WHERE id = $toId");
        
        // ❌ If second query fails, first is not rolled back
        // ❌ No audit log
    }
}
```

### Authentication Flow

```php
// ✅ Secure - Session-based authentication
class AuthController extends BaseController {
    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // 1. Find user by email
        $user = $this->userRepository->findByEmail($email);
        
        // 2. Verify password using hash
        if (!$user || !password_verify($password, $user->password_hash)) {
            // Don't reveal if email or password was wrong
            throw new AuthenticationException('Invalid credentials');
        }
        
        // 3. Check if account is active
        if (!$user->active) {
            throw new AuthenticationException('Account disabled');
        }
        
        // 4. Create session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['authenticated_at'] = time();
        
        // 5. Regenerate session ID (prevent fixation)
        session_regenerate_id(true);
        
        // Redirect to dashboard
        $this->redirect('/dashboard');
    }
    
    public function logout() {
        // Destroy all session data
        $_SESSION = [];
        session_destroy();
        $this->redirect('/');
    }
}

// ❌ Insecure - Plaintext passwords
class AuthController {
    public function login() {
        $user = query("SELECT * FROM users WHERE email = '$email'");
        
        // ❌ Comparing plaintext passwords
        if ($user->password === $password) {
            // ❌ Session not regenerated
            $_SESSION['user_id'] = $user->id;
        }
    }
}
```

### Authorization Checks

```php
// ✅ Secure - Role-based authorization
class AllianceController extends BaseController {
    public function updateSettings() {
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');
        
        $userId = $_SESSION['user_id'];
        $allianceId = (int) ($_POST['alliance_id'] ?? 0);
        
        // 1. Get user's alliance membership
        $membership = $this->allianceMemberRepository->findByUserAndAlliance($userId, $allianceId);
        if (!$membership) {
            throw new AuthorizationException('Not a member');
        }
        
        // 2. Check role permissions
        $requiredRole = 'leader';
        if (!$this->roleService->hasPermission($membership->role, 'manage_settings')) {
            throw new AuthorizationException('Insufficient permissions');
        }
        
        // 3. Update alliance
        $this->allianceService->updateSettings($allianceId, $_POST);
    }
}

// ❌ Insecure - No authorization check
class AllianceController {
    public function updateSettings() {
        $allianceId = $_POST['alliance_id'];
        
        // ❌ No check if user is member or has permission
        // ❌ Anyone can modify any alliance
        query("UPDATE alliances SET ... WHERE id = $allianceId");
    }
}
```

## Game-Specific Security

### Combat Manipulation

```php
// ✅ Secure - Server-side battle calculation
class BattleService {
    public function resolveBattle(int $attackerId, int $defenderId): BattleResult {
        // All calculations happen on server
        $attacker = $this->unitRepository->findById($attackerId);
        $defender = $this->unitRepository->findById($defenderId);
        
        // Recalculate battle from unit stats (never trust client)
        $result = $this->calculateOutcome($attacker, $defender);
        
        // Apply results to database
        $this->battleRepository->recordBattle($result);
        
        return $result;
    }
}

// ❌ Insecure - Client-side battle calculation
class BattleController {
    public function resolveBattle() {
        // ❌ Trusting client's damage calculation
        $damage = $_POST['damage'];
        $winner = $_POST['winner'];
        
        // ❌ Player could send any values
        // ❌ Can win battles they should lose
        query("UPDATE units SET hp = ? WHERE id = ?", [$_POST['hp'], $defenderId]);
    }
}
```

### Espionage Authorization

```php
// ✅ Secure - Verify spy has spies to send
class SpyController extends BaseController {
    public function sendSpies() {
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');
        
        $userId = $_SESSION['user_id'];
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $spyCount = (int) ($_POST['spy_count'] ?? 0);
        
        // 1. Verify user has spies
        $userUnits = $this->unitRepository->findByUserAndType($userId, 'spy');
        if ($userUnits->count < $spyCount) {
            throw new InsufficientUnitsException('Not enough spies');
        }
        
        // 2. Log the espionage attempt
        $this->espionageRepository->logAttempt($userId, $targetId, $spyCount);
        
        // 3. Execute spy mission
        $this->espionageService->executeMission($userId, $targetId, $spyCount);
    }
}
```

## SQL Injection Prevention

```php
// ✅ Secure - Prepared statements
$stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND active = ?');
$stmt->execute([$email, 1]);

// ✅ Secure - Named parameters
$stmt = $db->prepare('SELECT * FROM users WHERE email = :email AND active = :active');
$stmt->execute([':email' => $email, ':active' => 1]);

// ❌ Insecure - String concatenation
$result = $db->query("SELECT * FROM users WHERE email = '$email'");
// Attacker: ' OR '1'='1

// ❌ Insecure - Simple escaping (insufficient)
$escaped = addslashes($email);
$result = $db->query("SELECT * FROM users WHERE email = '$escaped'");
// Can still be exploited
```

## XSS Prevention

```html
<!-- ✅ Secure - Output encoded -->
<p>Username: <?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?></p>
<p>Status: <?= htmlspecialchars($status_message, ENT_QUOTES, 'UTF-8') ?></p>

<!-- ✅ Secure in JavaScript context -->
<script>
    const username = <?= json_encode($user->username) ?>;
</script>

<!-- ❌ Insecure - Raw user data -->
<p>Username: <?= $user->username ?></p>
<!-- If username is "<img src=x onerror=alert('XSS')>", script executes -->

<!-- ❌ Insecure in JavaScript -->
<script>
    const username = "<?= $user->username ?>";
    // If username contains quotes, can break out of string
</script>
```

## CSRF Prevention

```php
// ✅ Secure - CSRF token on all forms
// BaseController auto-injects $csrf_token

<form method="post" action="/resources/transfer">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <!-- Form fields -->
</form>

// ✅ Validation in controller
$this->csrfService->validateToken($_POST['csrf_token'] ?? '');

// ❌ Insecure - No CSRF token
<form method="post" action="/resources/transfer">
    <!-- Form fields -->
</form>
```

## Password Security

```php
// ✅ Secure - bcrypt or argon2
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3,
]);

// Verify
if (password_verify($password, $hash)) {
    // Password correct
}

// ❌ Insecure - MD5 or SHA1
$hash = md5($password);  // Easily cracked
$hash = sha1($password); // Also cracked
```

## Security Checklist

When reviewing code for security:

- [ ] CSRF protection on all forms?
- [ ] Authentication required for sensitive operations?
- [ ] Authorization checks before allowing actions?
- [ ] All SQL queries using prepared statements?
- [ ] All user output encoded with htmlspecialchars()?
- [ ] Input validation on all user data?
- [ ] Error messages don't reveal sensitive info?
- [ ] Sessions handled securely (HTTPS, httponly)?
- [ ] Passwords hashed with strong algorithms?
- [ ] Rate limiting on brute-force prone endpoints?
- [ ] Audit logging of sensitive operations?
- [ ] Server-side validation of all calculations?

## Boundaries

### ✅ Always Do:

- Use CSRF protection on all forms
- Validate all user input
- Encode all output with htmlspecialchars()
- Use prepared statements for all SQL
- Perform authorization checks before sensitive operations
- Use strong password hashing
- Log sensitive operations for audit trail
- Regenerate session IDs after login
- Never trust client-side calculations
- Keep error messages generic for security

### ⚠️ Ask First:

- Before implementing custom authentication
- Before modifying CSRF protection
- Before adding third-party authentication services
- Before changing password requirements
- Before disabling any security features

### 🚫 Never Do:

- Store plaintext passwords
- Use MD5 or SHA1 for password hashing
- Trust user input for critical operations
- Reveal whether email exists in authentication errors
- Use URL parameters for sensitive data
- Skip CSRF tokens on forms
- Output user data without encoding
- Use eval() or exec() with user input

## Available Commands

```bash
# Check for common vulnerabilities
grep -r "password_hash" app/
grep -r "prepared" app/
grep -r "htmlspecialchars" views/

# Review CSRF protection
grep -r "csrf_token" views/

# Check for SQL injection patterns
grep -r "\$db->query" app/
```

## Related Documentation

- [Main Documentation](/docs)
- [Backend Agent](/docs/agents/backend-agent.md)
- [Code Review Agent](/docs/agents/review-agent.md)
- [Testing Agent](/docs/agents/testing-agent.md)

---

**Last Updated:** December 2025
