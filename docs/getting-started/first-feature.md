# Your First Feature

This guide walks through adding a complete vertical slice feature to StarlightDominion V2 using the MVC-Service-Repository pattern.

## Example: Adding a "Bank Deposit" Feature

Let's follow the actual implementation of the bank deposit feature as a reference.

### Step 1: Define the Entity

Create `app/Models/Entities/UserResource.php` for data structure:

```php
<?php
namespace App\Models\Entities;

readonly class UserResource {
    public function __construct(
        public int $id,
        public int $user_id,
        public int $credits,
        public int $banked_credits,
        // ... other properties
    ) {}
}
```

### Step 2: Create the Repository

Add `app/Models/Repositories/ResourceRepository.php` for database access:

```php
<?php
namespace App\Models\Repositories;

use PDO;
use App\Models\Entities\UserResource;

class ResourceRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findByUserId(int $userId): ?UserResource {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_resources WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? new UserResource(...$row) : null;
    }

    public function deposit(int $userId, int $amount): void {
        $stmt = $this->db->prepare("
            UPDATE user_resources 
            SET credits = credits - :amount,
                banked_credits = banked_credits + :amount
            WHERE user_id = :user_id
        ");
        $stmt->execute([
            'user_id' => $userId,
            'amount' => $amount
        ]);
    }
}
```

### Step 3: Implement the Service

Create `app/Models/Services/BankService.php` with business logic:

```php
<?php
namespace App\Models\Services;

use PDO;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;

class BankService {
    private PDO $db;
    private ResourceRepository $resourceRepo;
    private StatsRepository $statsRepo;

    public function __construct(
        PDO $db,
        ResourceRepository $resourceRepo,
        StatsRepository $statsRepo
    ) {
        $this->db = $db;
        $this->resourceRepo = $resourceRepo;
        $this->statsRepo = $statsRepo;
    }

    public function deposit(int $userId, int $amount): ServiceResponse {
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            $resources = $this->resourceRepo->findByUserId($userId);
            $stats = $this->statsRepo->findByUserId($userId);

            // Validate deposit charges
            if ($stats->deposit_charges <= 0) {
                $this->db->rollback();
                return ServiceResponse::error('No deposit charges available.');
            }

            // Validate amount
            if ($amount > $resources->credits) {
                $this->db->rollback();
                return ServiceResponse::error('Insufficient credits.');
            }

            // Execute deposit
            $this->resourceRepo->deposit($userId, $amount);
            $this->statsRepo->useDepositCharge($userId);

            $this->db->commit();
            return ServiceResponse::success('Deposited successfully!');
            
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
```

### Step 4: Create the Controller

Add `app/Controllers/BankController.php`:

```php
<?php
namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\BankService;
use App\Models\Services\ViewContextService;

class BankController extends BaseController {
    private BankService $bankService;

    public function __construct(
        BankService $bankService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->bankService = $bankService;
    }

    public function handleDeposit(): void {
        // 1. Validate input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/bank');
            return;
        }

        // 3. Execute logic
        $userId = $this->session->get('user_id');
        $response = $this->bankService->deposit($userId, $data['amount']);
        
        // 4. Handle response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }

        $this->redirect('/bank');
    }
}
```

### Step 5: Add the Route

Edit `public/index.php` to register the route:

```php
$r->addRoute('POST', '/bank/deposit', [BankController::class, 'handleDeposit']);
```

### Step 6: Create the View

Add `views/bank/show.php`:

```php
<h1>Bank</h1>
<form action="/bank/deposit" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    
    <label for="amount">Amount to Deposit</label>
    <input type="number" name="amount" id="amount" required>
    
    <button type="submit">Deposit</button>
</form>
```

## Key Principles

1. **Separation of Concerns**: Controllers handle HTTP, Services handle logic, Repositories handle SQL
2. **Transaction Safety**: Wrap multi-table operations in `beginTransaction()` / `commit()` / `rollback()`
3. **Dependency Injection**: Pass dependencies via constructor
4. **CSRF Protection**: Always validate `csrf_token` in forms
5. **ServiceResponse Pattern**: Return structured success/error messages

## Testing Your Feature

1. **Manual Testing**: Visit `/bank` and try depositing
2. **Check Logs**: Review `logs/` for errors
3. **Database Verification**: Query the database to verify changes

## Next Steps

- Review [Features](../features/index.md) for more examples
- Read [Architecture](../architecture/index.md) for pattern details
- Check [Contributing Guide](../CONTRIBUTING.md) for code standards
