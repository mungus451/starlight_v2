# Views

Dumb templates responsible for presentation only.

```php
<!-- views/training/index.php -->
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<h1>Training Center</h1>
<form method="POST" action="/training/train">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <label>Quantity:<input type="number" name="quantity" min="1" required></label>
  <button type="submit">Train Units</button>
</form>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

Rules:
- No business logic or SQL
- Variables are provided by controllers
- Always include CSRF tokens in forms
