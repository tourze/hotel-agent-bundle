# hotel-agent-bundle

[![PHP Version Require](https://img.shields.io/badge/php-%5E8.2-blue)](https://packagist.org/packages/tourze/hotel-agent-bundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)](
https://github.com/tourze/php-monorepo/actions)
[![Coverage Status](https://img.shields.io/codecov/c/github/
tourze/php-monorepo)](
https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

Hotel agent management module for handling hotel distribution, 
agent accounts, orders, and billing.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Dependencies](#dependencies)
- [Usage](#usage)
  - [Managing Agents](#managing-agents)
  - [Working with Orders](#working-with-orders)
- [Commands](#commands)
  - [Agent Commands](#agent-commands)
  - [Bill Commands](#bill-commands)
- [Configuration](#configuration)
- [Advanced Usage](#advanced-usage)
  - [Custom Agent Code Generation](#custom-agent-code-generation)
  - [Bill Audit Workflow Customization](#bill-audit-workflow-customization)
  - [Order Import with Custom Validation](#order-import-with-custom-validation)
- [Entities](#entities)
- [Enums](#enums)
- [Services](#services)
- [Events](#events)
- [Admin Interface](#admin-interface)
- [Testing](#testing)
- [License](#license)

## Features

- Agent account management (creation, status management, commission rates)
- Hotel-agent mapping configuration
- Order management and processing
- Payment tracking and reconciliation
- Monthly billing generation and audit
- Comprehensive reporting and export capabilities
- EasyAdmin integration for backend management

## Installation

```bash
composer require tourze/hotel-agent-bundle
```

## Quick Start

1. Install the bundle:
   ```bash
   composer require tourze/hotel-agent-bundle
   ```

2. Register the bundle in your Symfony application:
   ```php
   // config/bundles.php
   return [
       // ... other bundles
       Tourze\HotelAgentBundle\HotelAgentBundle::class => ['all' => true],
   ];
   ```

3. Update your database schema:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

4. Create your first agent:
   ```bash
   php bin/console doctrine:fixtures:load --group=agent-demo
   ```

## Dependencies

This bundle requires the following packages:

- **PHP 8.2+**: Modern PHP features and performance
- **Symfony 7.3+**: Framework foundation
- **Doctrine ORM 3.0+**: Database abstraction and ORM
- **EasyAdmin 4+**: Admin interface
- **PHPOffice/PhpSpreadsheet**: Excel import/export functionality

Internal dependencies:
- `tourze/hotel-profile-bundle`: Hotel and room type management
- `tourze/hotel-contract-bundle`: Inventory and pricing contracts
- `tourze/doctrine-timestamp-bundle`: Automatic timestamp handling
- `tourze/easy-admin-menu-bundle`: Admin menu integration

## Usage

### Managing Agents

```php
use Tourze\HotelAgentBundle\Service\AgentBillService;
use Tourze\HotelAgentBundle\Service\OrderCreationService;

// Inject services via constructor
class YourController
{
    public function __construct(
        private AgentBillService $agentBillService,
        private OrderCreationService $orderCreationService
    ) {}

    public function createOrder(Agent $agent, array $orderData): Order
    {
        return $this->orderCreationService->createFromArray($orderData, $agent);
    }

    public function generateAgentBill(Agent $agent, string $month): AgentBill
    {
        return $this->agentBillService->generateMonthlyBill($agent, $month);
    }
}
```

### Working with Orders

```php
// Create an order with validation
$order = $orderCreationService->createFromArray([
    'hotelId' => 1,
    'roomTypeId' => 2,
    'checkInDate' => '2024-01-15',
    'checkOutDate' => '2024-01-17',
    'roomCount' => 2,
    'guestName' => 'John Doe',
    'guestPhone' => '+1234567890'
], $agent);

// Update order status
$orderStatusService->updateStatus($order, OrderStatusEnum::CONFIRMED);
```

## Commands

The bundle provides several console commands for agent and billing management.

## Agent Commands

### agent:check-expired

Check and update expired agent account statuses.

```bash
# Check expired agents and update their status
php bin/console agent:check-expired

# Dry run mode - only show what would be updated
php bin/console agent:check-expired --dry-run

# Check agents expiring within 30 days
php bin/console agent:check-expired --days=30
```

Options:
- `--dry-run`: Show what would be updated without making changes
- `--days`: Number of days to check for upcoming expirations (default: 7)

## Bill Commands

### app:generate-monthly-bills

Automatically generate monthly settlement bills for agents.

```bash
# Generate bills for the previous month
php bin/console app:generate-monthly-bills

# Generate bills for a specific month
php bin/console app:generate-monthly-bills 2024-01

# Force regenerate existing bills
php bin/console app:generate-monthly-bills --force

# Dry run mode - preview what would be generated
php bin/console app:generate-monthly-bills --dry-run
```

Arguments:
- `billMonth`: Bill month in YYYY-MM format (optional, defaults to previous month)

Options:
- `--force, -f`: Force regenerate existing bills
- `--dry-run`: Preview mode without creating actual bills

Recommended cron setup (run on the 1st of each month):
```bash
0 2 1 * * php /path/to/bin/console app:generate-monthly-bills
```

## Configuration

The bundle automatically registers its services and entities. No additional configuration is required for basic usage.

## Advanced Usage

### Custom Agent Code Generation

```php
use Tourze\HotelAgentBundle\Service\AgentCodeGenerator;

class CustomAgentCodeGenerator extends AgentCodeGenerator
{
    public function generateCode(): string
    {
        // Custom logic for agent code generation
        return 'AGT' . date('Y') . sprintf('%04d', rand(1, 9999));
    }
}
```

### Bill Audit Workflow Customization

```php
use Tourze\HotelAgentBundle\Service\BillAuditService;
use Tourze\HotelAgentBundle\Entity\AgentBill;

class CustomBillAuditService extends BillAuditService
{
    public function audit(AgentBill $bill, string $decision, string $comment = ''): bool
    {
        // Custom audit logic
        if ($this->performCustomValidation($bill)) {
            return parent::audit($bill, $decision, $comment);
        }
        
        return false;
    }
    
    private function performCustomValidation(AgentBill $bill): bool
    {
        // Your custom validation logic
        return true;
    }
}
```

### Order Import with Custom Validation

```php
use Tourze\HotelAgentBundle\Service\OrderImportService;

class CustomOrderImportService extends OrderImportService
{
    protected function validateRowData(array $rowData): bool
    {
        // Add custom validation rules
        if (!parent::validateRowData($rowData)) {
            return false;
        }
        
        // Additional custom checks
        return $this->performBusinessRuleValidation($rowData);
    }
}
```

## Entities

- **Agent**: Represents hotel distribution agents with commission rates and status
- **AgentBill**: Monthly settlement bills for agents
- **AgentHotelMapping**: Maps agents to hotels they can sell
- **Order**: Hotel booking orders placed by agents
- **OrderItem**: Individual room bookings within an order
- **Payment**: Payment records for orders
- **BillAuditLog**: Audit trail for bill approvals and rejections

## Enums

- **AgentStatusEnum**: ACTIVE, FROZEN, DISABLED, EXPIRED
- **AgentLevelEnum**: BRONZE, SILVER, GOLD, PLATINUM, DIAMOND
- **BillStatusEnum**: DRAFT, PENDING_AUDIT, AUDITED, REJECTED, PAID
- **OrderStatusEnum**: PENDING, CONFIRMED, CANCELLED, COMPLETED, CLOSED
- **PaymentStatusEnum**: PENDING, PROCESSING, SUCCESS, FAILED, REFUNDED

## Services

- **AgentBillService**: Handles monthly bill generation and calculations
- **BillAuditService**: Manages bill approval workflow
- **OrderCreationService**: Creates orders with validation
- **OrderStatusService**: Manages order state transitions
- **PaymentService**: Processes payments and refunds
- **OrderImportService**: Imports orders from Excel files
- **AgentCodeGenerator**: Generates unique agent codes

## Events

- Agent code auto-generation on creation
- Order item inventory synchronization
- Bill status change tracking

## Admin Interface

The bundle provides EasyAdmin CRUD controllers for:
- Agent management
- Order management with bulk operations
- Payment tracking
- Bill approval workflow
- Comprehensive reporting dashboards
- Data export functionality

## Testing

```bash
# Run all tests
./vendor/bin/phpunit packages/hotel-agent-bundle/tests

# Run specific test categories
./vendor/bin/phpunit packages/hotel-agent-bundle/tests/Entity
./vendor/bin/phpunit packages/hotel-agent-bundle/tests/Service
./vendor/bin/phpunit packages/hotel-agent-bundle/tests/Controller

# Run with coverage
./vendor/bin/phpunit packages/hotel-agent-bundle/tests --coverage-html=coverage

# Run quality checks
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/hotel-agent-bundle
```

## License

MIT