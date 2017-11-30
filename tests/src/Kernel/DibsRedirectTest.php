<?php

namespace Drupal\Tests\commerce_payment_dibs\Functional;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_payment_dibs\PluginForm\OffsiteRedirect\DibsRedirect
 * @group commerce_payment
 */
class DibsRedirectTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_payment',
  ];

  /**
   * Tests creating a payment gateway.
   */
  public function testPaymentOnReturn() {

  }

}
