<?php

namespace Drupal\commerce_payment_dibs;

use Drupal\commerce_payment\Entity\Payment;

/**
 * Interface getMD5Key.
 *
 * @package Drupal\commerce_payment_dibs
 */
interface DibsTransactionServiceInterface {

  /**
   * Loads the user's reusable payment methods for the given payment gateway.
   *
   * @param number $merchant
   *   The merchant number.
   * @param number $orderId
   *   The order id.
   * @param string $currency
   *   The currency code.
   * @param number $amount
   *   The transaction amount.
   *
   * @return string
   *   The generated MD5 Key.
   */
  public function getMD5Key(Payment $payment, $merchant, $orderId, $currency, $amount);
}
