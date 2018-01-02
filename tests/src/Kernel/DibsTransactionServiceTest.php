<?php

namespace Drupal\Tests\commerce_payment_dibs\Functional;

use Drupal\commerce_payment_dibs\DibsTransactionService;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass Drupal\commerce_payment_dibs\DibsTransactionService
 *
 * @group commerce_payment
 */
class DibsTransactionServiceTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_payment',
    'commerce_order',
    'commerce_product',
    'commerce_payment',
    'state_machine',
    'entity_reference_revisions',
    'profile',
  ];

  /**
   * Tests creating a payment gateway.
   *
   * @covers Drupal\commerce_payment_dibs\DibsTransactionService::getAuthKey
   */
  public function testGetAuthKey() {
    $dibsTransactionServiceClass = DibsTransactionService::class;
    $dibsTransactionService = $this->getMockBuilder($dibsTransactionServiceClass)
      ->disableOriginalConstructor()
      ->getMock();
    $config = [
      'md5key1' => 'z]gUAWbB2hjkR@R^VTVOVDZbSVJ+(%*r',
      'md5key2' => 'jI{^eT(Md?$-Z46Ty.m-?ssZWyhIxA];',
    ];
    $transaction = '1983925025';
    $currencyCode = Currency;
    $total = '23139';
    // Get method.
    $method = $this->getMethod($dibsTransactionServiceClass, 'getAuthKey');
    // Invoke method.
    $authCode = $method->invokeArgs($dibsTransactionService, [$config, $transaction, $currencyCode, $total]);
    $this->assertEquals('f998398e3d49260c63b670afbc26a7d9', $authCode);
  }

  /**
   * Fetch a protected method to test.
   *
   * @param string $class
   *   The class to test.
   * @param string $name
   *   The name of the method.
   *
   * @return \ReflectionMethod
   *   The reflection of the method with changed accessibility.
   */
  protected function getMethod($class, $name) {
    $class = new \ReflectionClass($class);
    $method = $class->getMethod($name);
    $method->setAccessible(TRUE);
    return $method;
  }

}
