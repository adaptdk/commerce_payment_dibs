<?php

namespace Drupal\commerce_payment_dibs;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;

/**
 * Interface getMD5Key.
 *
 * @package Drupal\commerce_payment_dibs
 */
interface DibsTransactionServiceInterface {

  /**
   * Process a payment from dibs.
   *
   * @param Order $order
   *   The order to process.
   * @param number $transactionId
   *   The transation id.
   * @param str $statusCode
   *   The status code.
   */
  public function processPayment(Order $order, $transactionId, $statusCode);

  /**
   * Format a price according to dibs requirements.
   *
   * @param number $number
   *   The number being formatted.
   * @param number $currencyCode
   *   The currency code to use for formatting.
   *
   * @return number
   *   The formatted price.
   */
  public function formatPrice($number, $currencyCode);

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

  /**
   * Get the available credit cards.
   *
   * @return array
   *   Returns an array of credit cards.
   */
  public function getCreditCards();

  /**
   * Gets all available credit card types.
   *
   * @return array
   *   The credit card array.
   */
  public function getTypes();
}
