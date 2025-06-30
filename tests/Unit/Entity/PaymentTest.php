<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Payment;

final class PaymentTest extends TestCase
{
    public function testCreatePayment(): void
    {
        $payment = new Payment();
        
        self::assertInstanceOf(Payment::class, $payment);
    }
}