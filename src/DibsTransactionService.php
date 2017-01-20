<?php

namespace Drupal\commerce_payment_dibs;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\PaymentGatewayManager;

/**
 * Class DibsTransactionService.
 *
 * @package Drupal\commerce_payment_dibs
 */
class DibsTransactionService implements DibsTransactionServiceInterface {

  /**
   * @var \Drupal\commerce_payment\Entity\Payment
   */
  protected $paymentGatewayManager;

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public function getMD5Key(Payment $payment, $merchant, $orderId, $currency, $amount) {
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();
    $key1 = $configuration['md5key1'];
    $key2 = $configuration['md5key2'];
    $parameters = [
      'merchant' => $merchant,
      'orderid' => $orderId,
      'currency' => $currency,
      'amount' => $amount,
    ];

    $parameter_string = http_build_query($parameters);
    return MD5($key2 . MD5($key1 . $parameter_string));
  }

}
