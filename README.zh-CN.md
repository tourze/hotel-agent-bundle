# hotel-agent-bundle

[![PHP Version Require](https://img.shields.io/badge/php-%5E8.2-blue)](
https://packagist.org/packages/tourze/hotel-agent-bundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)](
https://github.com/tourze/php-monorepo/actions)
[![Coverage Status](https://img.shields.io/codecov/c/github/
tourze/php-monorepo)](
https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

酒店代理管理模块，用于处理酒店分销、代理账户、订单和账单管理。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [快速开始](#快速开始)
- [依赖关系](#依赖关系)
- [使用方法](#使用方法)
  - [代理管理](#代理管理)
  - [订单处理](#订单处理)
- [命令](#命令)
  - [代理命令](#代理命令)
  - [账单命令](#账单命令)
- [配置](#配置)
- [高级用法](#高级用法)
  - [自定义代理编号生成](#自定义代理编号生成)
  - [账单审核工作流自定义](#账单审核工作流自定义)
  - [订单导入自定义验证](#订单导入自定义验证)
- [实体](#实体)
- [枚举](#枚举)
- [服务](#服务)
- [事件](#事件)
- [管理界面](#管理界面)
- [测试](#测试)
- [许可证](#许可证)

## 功能特性

- 代理账户管理（创建、状态管理、佣金费率）
- 酒店-代理映射配置
- 订单管理和处理
- 支付跟踪和对账
- 月结账单生成和审核
- 全面的报表和导出功能
- EasyAdmin 集成用于后台管理

## 安装

```bash
composer require tourze/hotel-agent-bundle
```

## 快速开始

1. 安装Bundle：
   ```bash
   composer require tourze/hotel-agent-bundle
   ```

2. 在Symfony应用中注册Bundle：
   ```php
   // config/bundles.php
   return [
       // ... 其他bundles
       Tourze\HotelAgentBundle\HotelAgentBundle::class => ['all' => true],
   ];
   ```

3. 更新数据库结构：
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

4. 创建第一个代理：
   ```bash
   php bin/console doctrine:fixtures:load --group=agent-demo
   ```

## 依赖关系

此Bundle需要以下包：

- **PHP 8.2+**：现代PHP特性和性能
- **Symfony 7.3+**：框架基础
- **Doctrine ORM 3.0+**：数据库抽象和ORM
- **EasyAdmin 4+**：管理界面
- **PHPOffice/PhpSpreadsheet**：Excel导入/导出功能

内部依赖：
- `tourze/hotel-profile-bundle`：酒店和房型管理
- `tourze/hotel-contract-bundle`：库存和价格合约
- `tourze/doctrine-timestamp-bundle`：自动时间戳处理
- `tourze/easy-admin-menu-bundle`：管理菜单集成

## 使用方法

### 代理管理

```php
use Tourze\HotelAgentBundle\Service\AgentBillService;
use Tourze\HotelAgentBundle\Service\OrderCreationService;

// 通过构造函数注入服务
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

### 订单处理

```php
// 创建带验证的订单
$order = $orderCreationService->createFromArray([
    'hotelId' => 1,
    'roomTypeId' => 2,
    'checkInDate' => '2024-01-15',
    'checkOutDate' => '2024-01-17',
    'roomCount' => 2,
    'guestName' => 'John Doe',
    'guestPhone' => '+1234567890'
], $agent);

// 更新订单状态
$orderStatusService->updateStatus($order, OrderStatusEnum::CONFIRMED);
```

## 命令

该 Bundle 提供了多个用于代理和账单管理的控制台命令。

## 代理命令

### agent:check-expired

检查并更新过期的代理账户状态。

```bash
# 检查过期代理并更新其状态
php bin/console agent:check-expired

# 干运行模式 - 仅显示将要更新的内容
php bin/console agent:check-expired --dry-run

# 检查30天内即将过期的代理
php bin/console agent:check-expired --days=30
```

选项：
- `--dry-run`：显示将要更新的内容，但不实际修改
- `--days`：检查未来几天内即将过期的代理数（默认：7）

## 账单命令

### app:generate-monthly-bills

自动生成代理月结账单。

```bash
# 生成上个月的账单
php bin/console app:generate-monthly-bills

# 生成指定月份的账单
php bin/console app:generate-monthly-bills 2024-01

# 强制重新生成已存在的账单
php bin/console app:generate-monthly-bills --force

# 干运行模式 - 预览将要生成的内容
php bin/console app:generate-monthly-bills --dry-run
```

参数：
- `billMonth`：账单月份，YYYY-MM 格式（可选，默认为上个月）

选项：
- `--force, -f`：强制重新生成已存在的账单
- `--dry-run`：预览模式，不创建实际账单

建议的 cron 设置（每月 1 日运行）：
```bash
0 2 1 * * php /path/to/bin/console app:generate-monthly-bills
```

## 配置

该 Bundle 会自动注册其服务和实体。基本使用无需额外配置。

## 高级用法

### 自定义代理编号生成

```php
use Tourze\HotelAgentBundle\Service\AgentCodeGenerator;

class CustomAgentCodeGenerator extends AgentCodeGenerator
{
    public function generateCode(): string
    {
        // 自定义代理编号生成逻辑
        return 'AGT' . date('Y') . sprintf('%04d', rand(1, 9999));
    }
}
```

### 账单审核工作流自定义

```php
use Tourze\HotelAgentBundle\Service\BillAuditService;
use Tourze\HotelAgentBundle\Entity\AgentBill;

class CustomBillAuditService extends BillAuditService
{
    public function audit(AgentBill $bill, string $decision, string $comment = ''): bool
    {
        // 自定义审核逻辑
        if ($this->performCustomValidation($bill)) {
            return parent::audit($bill, $decision, $comment);
        }
        
        return false;
    }
    
    private function performCustomValidation(AgentBill $bill): bool
    {
        // 您的自定义验证逻辑
        return true;
    }
}
```

### 订单导入自定义验证

```php
use Tourze\HotelAgentBundle\Service\OrderImportService;

class CustomOrderImportService extends OrderImportService
{
    protected function validateRowData(array $rowData): bool
    {
        // 添加自定义验证规则
        if (!parent::validateRowData($rowData)) {
            return false;
        }
        
        // 额外的自定义检查
        return $this->performBusinessRuleValidation($rowData);
    }
}
```

## 实体

- **Agent**：代表酒店分销代理，包含佣金费率和状态
- **AgentBill**：代理的月结账单
- **AgentHotelMapping**：映射代理可以销售的酒店
- **Order**：代理下单的酒店预订订单
- **OrderItem**：订单中的单个房间预订
- **Payment**：订单的支付记录
- **BillAuditLog**：账单审批和拒绝的审计跟踪

## 枚举

- **AgentStatusEnum**：ACTIVE（激活）、FROZEN（冻结）、DISABLED（禁用）、EXPIRED（过期）
- **AgentLevelEnum**：BRONZE（青铜）、SILVER（白银）、GOLD（黄金）、PLATINUM（铂金）、DIAMOND（钻石）
- **BillStatusEnum**：DRAFT（草稿）、PENDING_AUDIT（待审核）、AUDITED（已审核）、REJECTED（已拒绝）、PAID（已支付）
- **OrderStatusEnum**：PENDING（待处理）、CONFIRMED（已确认）、CANCELLED（已取消）、COMPLETED（已完成）、CLOSED（已关闭）
- **PaymentStatusEnum**：PENDING（待支付）、PROCESSING（处理中）、SUCCESS（成功）、FAILED（失败）、REFUNDED（已退款）

## 服务

- **AgentBillService**：处理月结账单生成和计算
- **BillAuditService**：管理账单审批工作流
- **OrderCreationService**：创建带验证的订单
- **OrderStatusService**：管理订单状态转换
- **PaymentService**：处理支付和退款
- **OrderImportService**：从 Excel 文件导入订单
- **AgentCodeGenerator**：生成唯一的代理编号

## 事件

- 创建时自动生成代理编号
- 订单项库存同步
- 账单状态变更跟踪

## 管理界面

该 Bundle 提供 EasyAdmin CRUD 控制器用于：
- 代理管理
- 订单管理及批量操作
- 支付跟踪
- 账单审批工作流
- 综合报表仪表板
- 数据导出功能

## 测试

```bash
# 运行所有测试
./vendor/bin/phpunit packages/hotel-agent-bundle/tests

# 运行特定类型的测试
./vendor/bin/phpunit packages/hotel-agent-bundle/tests/Entity
./vendor/bin/phpunit packages/hotel-agent-bundle/tests/Service
./vendor/bin/phpunit packages/hotel-agent-bundle/tests/Controller

# 运行覆盖率测试
./vendor/bin/phpunit packages/hotel-agent-bundle/tests --coverage-html=coverage

# 运行质量检查
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/hotel-agent-bundle
```

## 许可证

MIT