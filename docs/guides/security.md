# Security Guide

StarlightDominion V2 implements multiple layers of security to protect user data and prevent common vulnerabilities.

## Authentication

### Middleware Protection

All protected routes use `AuthMiddleware` to verify session authentication:

```php
// From AuthMiddleware.php
public function handle(): void {
    if ($this->session->has('user_id')) {
        return;  // Authenticated
    }

    $this->session->setFlash('error', 'You must be logged in.');
    throw new RedirectException('/login');
}
```

### Registration Route

```php
// From public/index.php
$r->addRoute('POST', '/register', [AuthController::class, 'handleRegister']);
```

The controller calls `AuthService` which:
1. Validates input (email format, password strength)
2. Checks for existing users
3. Hashes password with `password_hash()`
4. Creates user record atomically

### Login Flow

```php
public function login(string $email, string $password): ServiceResponse {
    $user = $this->userRepo->findByEmail($email);
    
    if (!$user || !password_verify($password, $user->password)) {
        return ServiceResponse::error('Invalid credentials.');
    }

    $this->session->set('user_id', $user->id);
    $this->session->set('empire_name', $user->empire_name);
    
    return ServiceResponse::success('Logged in successfully!');
}
```

## CSRF Protection

### Token Generation

`BaseController` automatically generates CSRF tokens for all views:

```php
protected function render(string $view, array $data = []): void {
    $data['csrf_token'] = $this->csrfService->generateToken();
    // ... render view
}
```

### Token Validation

All state-changing requests must validate CSRF tokens:

```php
// From BankController.php
public function handleDeposit(): void {
    $data = $this->validate($_POST, [
        'csrf_token' => 'required',
        'amount' => 'required|int|min:1'
    ]);

    if (!$this->csrfService->validateToken($data['csrf_token'])) {
        $this->session->setFlash('error', 'Invalid security token.');
        $this->redirect('/bank');
        return;
    }
    
    // ... proceed with deposit
}
```

### View Implementation

```php
<form action="/bank/deposit" method="POST">
    <input type="hidden" name="csrf_token" 
           value="<?= htmlspecialchars($csrf_token) ?>">
    <!-- form fields -->
</form>
```

## Role-Based Access Control (RBAC)

Alliance actions are protected by granular permissions:

```php
// From AllianceRole entity
readonly class AllianceRole {
    public function __construct(
        public int $id,
        public int $alliance_id,
        public string $name,
        public bool $can_invite,
        public bool $can_kick,
        public bool $can_promote,
        public bool $can_manage_roles,
        public bool $can_declare_war,
        public bool $can_manage_diplomacy,
        public bool $can_access_treasury,
        public bool $can_manage_structures,
    ) {}
}
```

### Permission Checks

```php
// From AllianceService.php
public function kickMember(int $actorId, int $targetId): ServiceResponse {
    $actor = $this->memberRepo->findByUserId($actorId);
    
    if (!$actor->role->can_kick) {
        return ServiceResponse::error('Insufficient permissions.');
    }
    
    // ... proceed with kick
}
```

## SQL Injection Prevention

All database queries use prepared statements with PDO:

```php
// From ResourceRepository.php
public function findByUserId(int $userId): ?UserResource {
    $stmt = $this->db->prepare(
        "SELECT * FROM user_resources WHERE user_id = :user_id"
    );
    $stmt->execute(['user_id' => $userId]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? new UserResource(...$row) : null;
}
```

### Never Use String Interpolation

```php
// ❌ NEVER do this
$query = "SELECT * FROM users WHERE id = {$userId}";

// ✅ ALWAYS use prepared statements
$stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
```

## Password Security

### Hashing

```php
// Registration
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Login verification
if (password_verify($inputPassword, $user->password)) {
    // Valid
}
```

### Password Requirements

Enforced via validator:

```php
$data = $this->validate($_POST, [
    'email' => 'required|email',
    'password' => 'required|min:8',
    'password_confirmation' => 'required|same:password'
]);
```

## Session Security

### Redis Session Handler

Sessions are stored in Redis with appropriate timeouts:

```php
// From RedisSessionHandler
public function read(string $id): string {
    $data = $this->redis->get("session:{$id}");
    return $data ?: '';
}
```

### Session Fixation Prevention

Session ID is regenerated on login:

```php
session_regenerate_id(true);
```

## Input Validation

### Centralized Validator

All user input is validated through `App\Core\Validator`:

```php
$data = $this->validate($_POST, [
    'amount' => 'required|int|min:1|max:1000000',
    'target_name' => 'required|string|max:50',
]);
```

### Output Escaping

All user-generated content is escaped in views:

```php
<h2><?= htmlspecialchars($user->empire_name) ?></h2>
```

## Security Checklist

- [ ] All forms include CSRF tokens
- [ ] All routes use `AuthMiddleware` except public pages
- [ ] All SQL queries use prepared statements
- [ ] All passwords are hashed with `password_hash()`
- [ ] All user input is validated
- [ ] All output is escaped with `htmlspecialchars()`
- [ ] Sensitive actions require permission checks
- [ ] Sessions are stored securely in Redis

## Related Topics

- [Authentication Feature](../features/authentication.md)
- [Alliance Permissions](../features/alliances/roles-permissions.md)
- [Architecture Guide](../architecture/index.md)
