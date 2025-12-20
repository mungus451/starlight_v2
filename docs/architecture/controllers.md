# Controllers

HTTP-only layer: validates CSRF, parses input, calls Services, sets flash messages, redirects, or renders views.

```php
class TrainingController extends BaseController
{
    public function __construct(
        private TrainingService $trainingService
    ) { parent::__construct(); }

    public function train(): void
    {
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');
        $unitType = $_POST['unit_type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);

        $result = $this->trainingService->trainUnits(
            $this->session->get('user_id'),
            $unitType,
            $quantity
        );

        $this->session->setFlash('success', $result['message']);
        $this->redirect('/training');
    }
}
```

Rules:
- No SQL queries
- No business logic calculations
- No direct DB access
- Only orchestrates calls to Services; handles redirects/flash
