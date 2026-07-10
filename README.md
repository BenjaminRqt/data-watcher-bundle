# benjamin-rqt/data-watcher-bundle

Symfony bundle for database anomaly detection with email notifications and execution history.

<img width="500" height="500" alt="ChatGPT Image 5 juin 2026, 15_14_45" src="https://github.com/user-attachments/assets/83fd00cc-d63c-4c7f-869b-1b8093b993be" />


## Requirements

- PHP >= 8.2
- Symfony 6.3, 7.0 or 8.0

### Symfony components

- framework-bundle
- messenger
- mailer
- twig-bundle
- scheduler

### Doctrine

- doctrine/dbal ^3.0 or ^4.0
- (Optional) doctrine/orm (if you use `AbstractDoctrineCheck`)

### External dependencies

- dragonmantank/cron-expression (required for cron-based scheduler triggers)

---

## Installation

### 1. Declare the bundle in `composer.json`

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/BenjaminRqt/data-watcher-bundle"
        }
    ]
}
```

### 2. Install

```bash
composer require benjamin-rqt/data-watcher-bundle
```

### 3. Register the bundle (if Flex doesn't do it automatically)

```php
// config/bundles.php
return [
    // ...
    BenjaminRqt\DataWatcherBundle\DataWatcherBundle::class => ['all' => true],
];
```

### 4. Create the bundle configuration

```yaml
# config/packages/data_watcher.yaml
data_watcher:
    from_email: 'datawatcher@myapp.com'
    recipients:
        - 'admin@myapp.com'
    app_name: 'My Application' # Optional, used in email subjects and templates
```

### 5. Configure Messenger for the scheduler

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            scheduler_data_watcher:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            BenjaminRqt\DataWatcherBundle\Scheduler\Message\RunCheckMessage: scheduler_data_watcher
```

### 6. Environment variables (`.env.local`)

```env
MAILER_DSN=smtp://user:password@smtp.myserver.com:587
MESSENGER_TRANSPORT_DSN=doctrine://default
```

### 7. Setup the database table

This bundle uses a custom table `data_watcher_run` to store the history of checks. Run the following command to create it:

```bash
php bin/console data-watcher:setup
```

---

## Add a check in your application

Create a class in **your app** (not in the bundle):

### With raw SQL

```php
<?php

namespace App\DataWatcher;

use BenjaminRqt\DataWatcherBundle\Check\AbstractSqlCheck;

final class MyCheck extends AbstractSqlCheck
{
    public function getName(): string        { return 'my.sql.check'; }
    public function getDescription(): string { return 'Detects X'; }
    public function getSchedule(): string    { return '0 9 * * 1-5'; } // Mon-Fri at 9:00 AM

    protected function getSql(): string
    {
        return "SELECT id, name FROM my_table WHERE problem = 1";
    }
}
```

### With Doctrine ORM

```php
use BenjaminRqt\DataWatcherBundle\Check\AbstractDoctrineCheck;
use Doctrine\ORM\Query;

final class MyDoctrineCheck extends AbstractDoctrineCheck
{
    public function getName(): string        { return 'my.doctrine.check'; }
    public function getDescription(): string { return 'Detects Y via ORM'; }
    public function getSchedule(): string    { return '@daily'; }

    protected function buildQuery(): Query
    {
        return $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.id')
            ->where('u.email LIKE :s')
            ->setParameter('s', '%admin%')
            ->getQuery();
    }
}
```

### With a callable

```php
use BenjaminRqt\DataWatcherBundle\Check\AbstractCallableCheck;

final class MyCallableCheck extends AbstractCallableCheck
{
    public function __construct(private readonly MyCustomCallableHandler $callableHandler) {}

    public function getName(): string        { return 'my.callable.check'; }
    public function getDescription(): string { return 'Detects Z via callable'; }
    public function getSchedule(): string    { return '@daily'; }

    protected function getCallable(): ArrayCallableInterface
    {
        return $this->callableHandler;
    }
}
```

Then declare the handler containing the callable and the detection logic:

```php
use BenjaminRqt\DataWatcherBundle\Check\ArrayCallableInterface;

final class CallableHandler implements ArrayCallableInterface
{
    public function __invoke(): array
    {
        return [
            ['id' => 42],
        ];
    }
}
```

## Other possible configurations

### Increase the number of anomalies to trigger an alert

```php
public function getMaxAnomalies(): int
{
    return 5; // 1 by default, here at least 5 results are needed to send an alert
}
```

### Specific recipients for a check

```php
public function getRecipients(): array
{
    return ['manager@myapp.com']; // overrides global config
}
```

---

## Available commands

```bash
# Setup the database table for history
php bin/console data-watcher:setup

# List all registered checks
php bin/console data-watcher:run --list

# Test without email or history
php bin/console data-watcher:run my.check --dry-run

# Run a check (email + history)
php bin/console data-watcher:run my.check

# Run all checks
php bin/console data-watcher:run
```

## Start the worker (scheduler)
```bash
composer require symfony/scheduler
```

```bash
# Development
php bin/console messenger:consume scheduler_data_watcher -vv

# Production (with Supervisor or systemd)
php bin/console messenger:consume scheduler_data_watcher --time-limit=3600
```

No configuration is required for the scheduler, it automatically detects CRON expressions defined in checks and executes them at the right time.

---

## Quick CRON expressions

| Expression     | Meaning               |
|----------------|-----------------------|
| `0 8 * * *`    | Every day at 8:00 AM  |
| `*/30 * * * *` | Every 30 minutes      |
| `0 9 * * 1-5`  | Mon–Fri at 9:00 AM    |
| `@daily`       | Once a day            |
| `@hourly`      | Once an hour          |
