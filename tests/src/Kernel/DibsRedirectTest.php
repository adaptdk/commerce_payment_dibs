<?php

namespace Drupal\Tests\commerce_payment_dibs\Functional;

use Drupal\commerce_payment_dibs\Plugin\Commerce\PaymentGateway\DibsRedirect;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass Drupal\commerce_payment_dibs\Plugin\Commerce\PaymentGateway\DibsRedirect
 *
 * @group commerce_payment
 */
class DibsRedirectTest extends CommerceKernelTestBase {

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
   * @covers Drupal\commerce_payment_dibs\Plugin\Commerce\PaymentGateway\DibsRedirect::getCalculationAmount
   */
  public function testGetCalculationAmount() {
    $dibsRedirectClass = DibsRedirect::class;
    $dibsRedirect = $this->getMockBuilder($dibsRedirectClass)
      ->disableOriginalConstructor()
      ->setMethods(['getCalculationAmount'])
      ->getMock();
    // Get method.
    $method = $this->getMethod($dibsRedirectClass, 'getCalculationAmount');
    // Invoke method.
    $total = $method->invokeArgs($dibsRedirect, ['DKK', '23000', '139']);
    $this->assertEquals('23139', $total);
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
