<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Agent::class)]
final class AgentTest extends AbstractEntityTestCase
{
    public static function propertiesProvider(): iterable
    {
        yield 'userId' => ['userId', 12345];
        yield 'code' => ['code', 'AGT001'];
        yield 'companyName' => ['companyName', '测试公司'];
        yield 'contactPerson' => ['contactPerson', '张三'];
        yield 'phone' => ['phone', '13800138000'];
        yield 'email' => ['email', 'test@example.com'];
        yield 'licenseUrl' => ['licenseUrl', 'https://example.com/license.pdf'];
        yield 'level' => ['level', AgentLevelEnum::B];
        yield 'commissionRate' => ['commissionRate', '0.15'];
        yield 'status' => ['status', AgentStatusEnum::ACTIVE];
        yield 'expiryDate' => ['expiryDate', new \DateTimeImmutable('+1 year')];
    }

    protected function createEntity(): Agent
    {
        $agent = new Agent();
        $agent->setCreatedBy('test-user');

        return $agent;
    }

    public function testConstructInitializesCollections(): void
    {
        $agent = new Agent();
        $agent->setCreatedBy('test-user');

        $this->assertCount(0, $agent->getHotelMappings());
        $this->assertCount(0, $agent->getOrders());
        $this->assertCount(0, $agent->getBills());
    }

    public function testToStringReturnsCompanyNameAndCode(): void
    {
        $agent = new Agent();
        $agent->setCompanyName('测试公司');
        $agent->setCode('AGT001');

        $result = (string) $agent;

        $this->assertSame('测试公司 (AGT001)', $result);
    }

    public function testToStringWithEmptyValues(): void
    {
        $agent = new Agent();

        $result = (string) $agent;

        $this->assertSame(' ()', $result);
    }

    public function testSetUserIdWithValidValue(): void
    {
        $agent = new Agent();
        $agent->setUserId(123);

        $this->assertSame(123, $agent->getUserId());
    }

    public function testSetUserIdWithNull(): void
    {
        $agent = new Agent();
        $agent->setUserId(null);

        $this->assertNull($agent->getUserId());
    }

    public function testSetCodeWithValidCode(): void
    {
        $agent = new Agent();
        $agent->setCode('AGT20250101');

        $this->assertSame('AGT20250101', $agent->getCode());
    }

    public function testSetCompanyNameWithValidName(): void
    {
        $agent = new Agent();
        $agent->setCompanyName('北京测试公司');

        $this->assertSame('北京测试公司', $agent->getCompanyName());
    }

    public function testSetContactPersonWithValidName(): void
    {
        $agent = new Agent();
        $agent->setContactPerson('张三');

        $this->assertSame('张三', $agent->getContactPerson());
    }

    public function testSetPhoneWithValidPhone(): void
    {
        $agent = new Agent();
        $agent->setPhone('13800138000');

        $this->assertSame('13800138000', $agent->getPhone());
    }

    public function testSetEmailWithValidEmail(): void
    {
        $agent = new Agent();
        $agent->setEmail('test@example.com');

        $this->assertSame('test@example.com', $agent->getEmail());
    }

    public function testSetEmailWithNull(): void
    {
        $agent = new Agent();
        $agent->setEmail(null);

        $this->assertNull($agent->getEmail());
    }

    public function testSetLicenseUrlWithValidUrl(): void
    {
        $url = 'https://example.com/license.jpg';
        $agent = new Agent();
        $agent->setLicenseUrl($url);

        $this->assertSame($url, $agent->getLicenseUrl());
    }

    public function testSetLevelAutomaticallyUpdatesCommissionRate(): void
    {
        $agent = new Agent();
        $agent->setLevel(AgentLevelEnum::A);

        $this->assertSame(AgentLevelEnum::A, $agent->getLevel());
        $this->assertSame('0.10', $agent->getCommissionRate());
    }

    public function testSetLevelBUpdatesCommissionRate(): void
    {
        $agent = new Agent();
        $agent->setLevel(AgentLevelEnum::B);

        $this->assertSame('0.08', $agent->getCommissionRate());
    }

    public function testSetLevelCUpdatesCommissionRate(): void
    {
        $agent = new Agent();
        $agent->setLevel(AgentLevelEnum::C);

        $this->assertSame('0.05', $agent->getCommissionRate());
    }

    public function testSetCommissionRateWithValidRate(): void
    {
        $agent = new Agent();
        $agent->setCommissionRate('0.15');

        $this->assertSame('0.15', $agent->getCommissionRate());
    }

    public function testSetStatusWithValidStatus(): void
    {
        $agent = new Agent();
        $agent->setStatus(AgentStatusEnum::FROZEN);

        $this->assertSame(AgentStatusEnum::FROZEN, $agent->getStatus());
    }

    public function testSetExpiryDateWithValidDate(): void
    {
        $date = new \DateTimeImmutable('2025-12-31');
        $agent = new Agent();
        $agent->setExpiryDate($date);

        $this->assertSame($date, $agent->getExpiryDate());
    }

    public function testSetExpiryDateWithNull(): void
    {
        $agent = new Agent();
        $agent->setExpiryDate(null);

        $this->assertNull($agent->getExpiryDate());
    }

    public function testAddHotelMappingAddsNewMapping(): void
    {
        $agent = new Agent();
        $mapping = new AgentHotelMapping();
        $mapping->setCreatedBy('test-user');

        $result = $agent->addHotelMapping($mapping);

        $this->assertSame($agent, $result);
        $this->assertTrue($agent->getHotelMappings()->contains($mapping));
        $this->assertSame($agent, $mapping->getAgent());
    }

    public function testAddHotelMappingDoesNotAddDuplicate(): void
    {
        $agent = new Agent();
        $mapping = new AgentHotelMapping();
        $mapping->setCreatedBy('test-user');
        $agent->addHotelMapping($mapping);

        $agent->addHotelMapping($mapping);

        $this->assertCount(1, $agent->getHotelMappings());
    }

    public function testRemoveHotelMappingRemovesExistingMapping(): void
    {
        $agent = new Agent();
        $mapping = new AgentHotelMapping();
        $mapping->setCreatedBy('test-user');
        $agent->addHotelMapping($mapping);

        $result = $agent->removeHotelMapping($mapping);

        $this->assertSame($agent, $result);
        $this->assertFalse($agent->getHotelMappings()->contains($mapping));
        $this->assertNull($mapping->getAgent());
    }

    public function testRemoveHotelMappingWithNonExistingMapping(): void
    {
        $agent = new Agent();
        $mapping = new AgentHotelMapping();
        $mapping->setCreatedBy('test-user');

        $result = $agent->removeHotelMapping($mapping);

        $this->assertSame($agent, $result);
        $this->assertCount(0, $agent->getHotelMappings());
    }

    public function testAddOrderAddsNewOrder(): void
    {
        $agent = new Agent();
        $order = new Order();
        $order->setCreatedBy('123');

        $result = $agent->addOrder($order);

        $this->assertSame($agent, $result);
        $this->assertTrue($agent->getOrders()->contains($order));
        $this->assertSame($agent, $order->getAgent());
    }

    public function testRemoveOrderRemovesExistingOrder(): void
    {
        $agent = new Agent();
        $order = new Order();
        $order->setCreatedBy('123');
        $agent->addOrder($order);

        $result = $agent->removeOrder($order);

        $this->assertSame($agent, $result);
        $this->assertFalse($agent->getOrders()->contains($order));
        $this->assertNull($order->getAgent());
    }

    public function testAddBillAddsNewBill(): void
    {
        $agent = new Agent();
        $bill = new AgentBill();
        $bill->setCreatedBy('test-user');

        $result = $agent->addBill($bill);

        $this->assertSame($agent, $result);
        $this->assertTrue($agent->getBills()->contains($bill));
        $this->assertSame($agent, $bill->getAgent());
    }

    public function testRemoveBillRemovesExistingBill(): void
    {
        $agent = new Agent();
        $bill = new AgentBill();
        $bill->setCreatedBy('test-user');
        $agent->addBill($bill);

        $result = $agent->removeBill($bill);

        $this->assertSame($agent, $result);
        $this->assertFalse($agent->getBills()->contains($bill));
        $this->assertNull($bill->getAgent());
    }

    public function testIsExpiredReturnsFalseWhenNoExpiryDate(): void
    {
        $agent = new Agent();
        $agent->setExpiryDate(null);

        $this->assertFalse($agent->isExpired());
    }

    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $agent = new Agent();
        $expiredDate = new \DateTimeImmutable('-1 day');
        $agent->setExpiryDate($expiredDate);

        $this->assertTrue($agent->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $agent = new Agent();
        $futureDate = new \DateTimeImmutable('+1 day');
        $agent->setExpiryDate($futureDate);

        $this->assertFalse($agent->isExpired());
    }

    public function testIsActiveReturnsTrueWhenActiveAndNotExpired(): void
    {
        $agent = new Agent();
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setExpiryDate(new \DateTimeImmutable('+1 day'));

        $this->assertTrue($agent->isActive());
    }

    public function testIsActiveReturnsFalseWhenNotActive(): void
    {
        $agent = new Agent();
        $agent->setStatus(AgentStatusEnum::FROZEN);
        $agent->setExpiryDate(new \DateTimeImmutable('+1 day'));

        $this->assertFalse($agent->isActive());
    }

    public function testIsActiveReturnsFalseWhenExpired(): void
    {
        $agent = new Agent();
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setExpiryDate(new \DateTimeImmutable('-1 day'));

        $this->assertFalse($agent->isActive());
    }

    public function testIsActiveReturnsTrueWhenActiveNoExpiry(): void
    {
        $agent = new Agent();
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setExpiryDate(null);

        $this->assertTrue($agent->isActive());
    }

    public function testSetCreateTimeSetsTime(): void
    {
        $agent = new Agent();
        $time = new \DateTimeImmutable();
        $agent->setCreateTime($time);

        $this->assertSame($time, $agent->getCreateTime());
    }

    public function testSetUpdateTimeSetsTime(): void
    {
        $agent = new Agent();
        $time = new \DateTimeImmutable();
        $agent->setUpdateTime($time);

        $this->assertSame($time, $agent->getUpdateTime());
    }

    public function testDefaultValues(): void
    {
        $agent = new Agent();

        $this->assertNull($agent->getCode());
        $this->assertSame('', $agent->getCompanyName());
        $this->assertSame('', $agent->getContactPerson());
        $this->assertSame('', $agent->getPhone());
        $this->assertNull($agent->getEmail());
        $this->assertNull($agent->getLicenseUrl());
        $this->assertSame(AgentLevelEnum::C, $agent->getLevel());
        $this->assertSame('0.00', $agent->getCommissionRate());
        $this->assertSame(AgentStatusEnum::ACTIVE, $agent->getStatus());
        $this->assertNull($agent->getExpiryDate());
        $this->assertNull($agent->getCreateTime());
        $this->assertNull($agent->getUpdateTime());
        $this->assertNull($agent->getCreatedBy());
    }
}
