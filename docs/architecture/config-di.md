# Configuration & Dependency Injection

## Game Balance Configuration

All mechanics are centralized in config files like `config/game_balance.php`:

```php
return [
    'training' => [
        'soldiers' => ['cost' => 100, 'attack_power' => 5, 'defense_power' => 3],
        'guards'   => ['cost' => 150, 'attack_power' => 2, 'defense_power' => 8],
    ],
];
```

## Dependency Injection (PHP-DI)

Services and controllers are autowired with constructor injection:

```php
use App\Core\ContainerFactory;
use App\Models\Services\TrainingService;

$container = ContainerFactory::createContainer();
$service = $container->get(TrainingService::class);
```
