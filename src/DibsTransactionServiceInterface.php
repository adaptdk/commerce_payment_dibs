<?php

namespace Drupal\commerce_payment_dibs;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Interface getMD5Key.
 *
 * @package Drupal\commerce_payment_dibs
 */
interface DibsTransactionServiceInterface {

  /**
   * Process a payment from dibs.
   *
   * @param OrderInterface $order
   *   The order to process.
   * @param int $transactionId
   *   The transation id.
   * @param string $statusCode
   *   The status codeDibsRedirect
   * @param int $payment_gateway_id
   *   Gateway id.
   * @param string $mode
   *   Mode, test or prod.
   * @param string $paytype
   *   Payment type.
   *
   * @return
   */
  public function processPayment(OrderInterface $order, $transactionId, $statusCode, $payment_gateway_id, $mode, $paytype);

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
  public function getMD5Key($configuration, $orderId, $currency, $amount);

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
  public static function getTypes();

  /**
   * Checks whether the received statuscode from DIBS is a success
   *
   * @param array $configuration
   *   The DIBS configuration
   * @param number $statusCode
   *   The DIBS status code
   *
   * @return boolean
   *    Whether the statuscode indicates success
   */
  public function isPaymentStatusSuccess($configuration, $statusCode);
}
