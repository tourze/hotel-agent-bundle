<?php

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;

class AgentTest extends TestCase
{
    private Agent $agent;

    protected function setUp(): void
    {
        $this->agent = new Agent();
    }

    public function test_construct_initializes_collections(): void
    {
        $agent = new Agent();
        
        $this->assertCount(0, $agent->getHotelMappings());
        $this->assertCount(0, $agent->getOrders());
        $this->assertCount(0, $agent->getBills());
    }

    public function test_toString_returns_company_name_and_code(): void
    {
        $this->agent->setCompanyName('测试公司')->setCode('AGT001');
        
        $result = (string) $this->agent;
        
        $this->assertSame('测试公司 (AGT001)', $result);
    }

    public function test_toString_with_empty_values(): void
    {
        $result = (string) $this->agent;
        
        $this->assertSame(' ()', $result);
    }

    public function test_setUserId_with_valid_value(): void
    {
        $this->agent->setUserId(123);
        
        $this->assertSame(123, $this->agent->getUserId());
    }

    public function test_setUserId_with_null(): void
    {
        $this->agent->setUserId(null);
        
        $this->assertNull($this->agent->getUserId());
    }

    public function test_setCode_with_valid_code(): void
    {
        $this->agent->setCode('AGT20250101');
        
        $this->assertSame('AGT20250101', $this->agent->getCode());
    }

    public function test_setCompanyName_with_valid_name(): void
    {
        $this->agent->setCompanyName('北京测试公司');
        
        $this->assertSame('北京测试公司', $this->agent->getCompanyName());
    }

    public function test_setContactPerson_with_valid_name(): void
    {
        $this->agent->setContactPerson('张三');
        
        $this->assertSame('张三', $this->agent->getContactPerson());
    }

    public function test_setPhone_with_valid_phone(): void
    {
        $this->agent->setPhone('13800138000');
        
        $this->assertSame('13800138000', $this->agent->getPhone());
    }

    public function test_setEmail_with_valid_email(): void
    {
        $this->agent->setEmail('test@example.com');
        
        $this->assertSame('test@example.com', $this->agent->getEmail());
    }

    public function test_setEmail_with_null(): void
    {
        $this->agent->setEmail(null);
        
        $this->assertNull($this->agent->getEmail());
    }

    public function test_setLicenseUrl_with_valid_url(): void
    {
        $url = 'https://example.com/license.jpg';
        $this->agent->setLicenseUrl($url);
        
        $this->assertSame($url, $this->agent->getLicenseUrl());
    }

    public function test_setLevel_automatically_updates_commission_rate(): void
    {
        $this->agent->setLevel(AgentLevelEnum::A);
        
        $this->assertSame(AgentLevelEnum::A, $this->agent->getLevel());
        $this->assertSame('0.10', $this->agent->getCommissionRate());
    }

    public function test_setLevel_b_updates_commission_rate(): void
    {
        $this->agent->setLevel(AgentLevelEnum::B);
        
        $this->assertSame('0.08', $this->agent->getCommissionRate());
    }

    public function test_setLevel_c_updates_commission_rate(): void
    {
        $this->agent->setLevel(AgentLevelEnum::C);
        
        $this->assertSame('0.05', $this->agent->getCommissionRate());
    }

    public function test_setCommissionRate_with_valid_rate(): void
    {
        $this->agent->setCommissionRate('0.15');
        
        $this->assertSame('0.15', $this->agent->getCommissionRate());
    }

    public function test_setStatus_with_valid_status(): void
    {
        $this->agent->setStatus(AgentStatusEnum::FROZEN);
        
        $this->assertSame(AgentStatusEnum::FROZEN, $this->agent->getStatus());
    }

    public function test_setExpiryDate_with_valid_date(): void
    {
        $date = new \DateTime('2025-12-31');
        $this->agent->setExpiryDate($date);
        
        $this->assertSame($date, $this->agent->getExpiryDate());
    }

    public function test_setExpiryDate_with_null(): void
    {
        $this->agent->setExpiryDate(null);
        
        $this->assertNull($this->agent->getExpiryDate());
    }

    public function test_addHotelMapping_adds_new_mapping(): void
    {
        $mapping = new AgentHotelMapping();
        
        $result = $this->agent->addHotelMapping($mapping);
        
        $this->assertSame($this->agent, $result);
        $this->assertTrue($this->agent->getHotelMappings()->contains($mapping));
        $this->assertSame($this->agent, $mapping->getAgent());
    }

    public function test_addHotelMapping_does_not_add_duplicate(): void
    {
        $mapping = new AgentHotelMapping();
        $this->agent->addHotelMapping($mapping);
        
        $this->agent->addHotelMapping($mapping);
        
        $this->assertCount(1, $this->agent->getHotelMappings());
    }

    public function test_removeHotelMapping_removes_existing_mapping(): void
    {
        $mapping = new AgentHotelMapping();
        $this->agent->addHotelMapping($mapping);
        
        $result = $this->agent->removeHotelMapping($mapping);
        
        $this->assertSame($this->agent, $result);
        $this->assertFalse($this->agent->getHotelMappings()->contains($mapping));
        $this->assertNull($mapping->getAgent());
    }

    public function test_removeHotelMapping_with_non_existing_mapping(): void
    {
        $mapping = new AgentHotelMapping();
        
        $result = $this->agent->removeHotelMapping($mapping);
        
        $this->assertSame($this->agent, $result);
        $this->assertCount(0, $this->agent->getHotelMappings());
    }

    public function test_addOrder_adds_new_order(): void
    {
        $order = new Order();
        
        $result = $this->agent->addOrder($order);
        
        $this->assertSame($this->agent, $result);
        $this->assertTrue($this->agent->getOrders()->contains($order));
        $this->assertSame($this->agent, $order->getAgent());
    }

    public function test_removeOrder_removes_existing_order(): void
    {
        $order = new Order();
        $this->agent->addOrder($order);
        
        $result = $this->agent->removeOrder($order);
        
        $this->assertSame($this->agent, $result);
        $this->assertFalse($this->agent->getOrders()->contains($order));
        $this->assertNull($order->getAgent());
    }

    public function test_addBill_adds_new_bill(): void
    {
        $bill = new AgentBill();
        
        $result = $this->agent->addBill($bill);
        
        $this->assertSame($this->agent, $result);
        $this->assertTrue($this->agent->getBills()->contains($bill));
        $this->assertSame($this->agent, $bill->getAgent());
    }

    public function test_removeBill_removes_existing_bill(): void
    {
        $bill = new AgentBill();
        $this->agent->addBill($bill);
        
        $result = $this->agent->removeBill($bill);
        
        $this->assertSame($this->agent, $result);
        $this->assertFalse($this->agent->getBills()->contains($bill));
        $this->assertSame($this->agent, $bill->getAgent());
    }

    public function test_isExpired_returns_false_when_no_expiry_date(): void
    {
        $this->agent->setExpiryDate(null);
        
        $this->assertFalse($this->agent->isExpired());
    }

    public function test_isExpired_returns_true_when_expired(): void
    {
        $expiredDate = new \DateTime('-1 day');
        $this->agent->setExpiryDate($expiredDate);
        
        $this->assertTrue($this->agent->isExpired());
    }

    public function test_isExpired_returns_false_when_not_expired(): void
    {
        $futureDate = new \DateTime('+1 day');
        $this->agent->setExpiryDate($futureDate);
        
        $this->assertFalse($this->agent->isExpired());
    }

    public function test_isActive_returns_true_when_active_and_not_expired(): void
    {
        $this->agent->setStatus(AgentStatusEnum::ACTIVE);
        $this->agent->setExpiryDate(new \DateTime('+1 day'));
        
        $this->assertTrue($this->agent->isActive());
    }

    public function test_isActive_returns_false_when_not_active(): void
    {
        $this->agent->setStatus(AgentStatusEnum::FROZEN);
        $this->agent->setExpiryDate(new \DateTime('+1 day'));
        
        $this->assertFalse($this->agent->isActive());
    }

    public function test_isActive_returns_false_when_expired(): void
    {
        $this->agent->setStatus(AgentStatusEnum::ACTIVE);
        $this->agent->setExpiryDate(new \DateTime('-1 day'));
        
        $this->assertFalse($this->agent->isActive());
    }

    public function test_isActive_returns_true_when_active_no_expiry(): void
    {
        $this->agent->setStatus(AgentStatusEnum::ACTIVE);
        $this->agent->setExpiryDate(null);
        
        $this->assertTrue($this->agent->isActive());
    }

    public function test_setCreateTime_sets_time(): void
    {
        $time = new \DateTime();
        $this->agent->setCreateTime($time);
        
        $this->assertSame($time, $this->agent->getCreateTime());
    }

    public function test_setUpdateTime_sets_time(): void
    {
        $time = new \DateTime();
        $this->agent->setUpdateTime($time);
        
        $this->assertSame($time, $this->agent->getUpdateTime());
    }

    public function test_default_values(): void
    {
        $agent = new Agent();
        
        $this->assertSame('', $agent->getCode());
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