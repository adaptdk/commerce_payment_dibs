<?php

namespace Drupal\commerce_payment_dibs\Service;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment_dibs\DibsTransactionService;
use GuzzleHttp\Client;

/**
 * Class PaymentFunctions.
 *
 * @package Drupal\commerce_payment_dibs\Service
 */
class PaymentFunctions {

  /**
   * Cancel url.
   */
  const CANCEL_URL = 'https://payment.architrade.com/cgi-adm/cancel.cgi';

  /**
   * Refund url.
   */
  const REFUND_URL = 'https://payment.architrade.com/cgi-adm/refund.cgi';

  /**
   * Transaction service.
   *
   * @var \Drupal\commerce_payment_dibs\DibsTransactionService
   */
  protected $transactionService;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Gateway configuration.
   *
   * @var array
   */
  protected $gatewayConfiguration;

  /**
   * Last response.
   *
   * @var array
   */
  protected $lastResponse;

  /**
   * PaymentFunctions constructor.
   *
   * @param \Drupal\commerce_payment_dibs\DibsTransactionService $transactionService
   *   Transaction service.
   * @param \GuzzleHttp\Client $client
   *   Client.
   */
  public function __construct(DibsTransactionService $transactionService, Client $client) {
    $this->transactionService = $transactionService;
    $this->client = $client;

    $payment_gateway = PaymentGateway::load('dibs');
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    // Get the configuration array.
    $this->gatewayConfiguration = $payment_gateway_plugin->getConfiguration();
  }

  /**
   * Call api.
   *
   * @param string $options
   *   Options.
   * @param string $url
   *   Url.
   *
   * @return array
   *   Response.
   */
  protected function callApi($options, $url) {
    $response = $this->client->post(
      $url,
      [
        'form_params' => $options,
        'auth'        => [$this->getConfigurationByKey('api_username'), $this->getConfigurationByKey('api_password')],
      ]
    );

    $this->setLastResponse($response->getBody()->getContents());

    return $this->getLastResponse();
  }

  /**
   * Get payment remote id.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Order.
   *
   * @return string
   *   Remote payment id.
   */
  protected function getPaymentRemoteId(Order $order) {
    $payment = $this->loadPaymentFromOrder($order);

    if($payment !== NULL){
      return $payment->getRemoteId();
    }

    return '';
  }

  /**
   * Load payment from order.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Order.
   *
   * @return \Drupal\commerce_payment\Entity\Payment
   *   Payment.
   */
  protected function loadPaymentFromOrder(Order $order) {
    $payments = \Drupal::entityQuery('commerce_payment')->condition('order_id', $order->id())->execute();
    $paymentId = reset($payments);

    return Payment::load($paymentId);
  }

  /**
   * Get md5 key.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Order.
   *
   * @return string
   */
  protected function getMD5Key(Order $order) {
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $price = $order->getTotalPrice()->getNumber();
    $total = $this->transactionService->formatPrice($price, $currencyCode);

    return $this->transactionService->getMD5Key(
      $this->gatewayConfiguration,
      $this->getOrderIdComposed($order),
      $currencyCode,
      $total
    );
  }

  /**
   * Get order id.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Order.
   *
   * @return string
   *   Order id
   */
  protected function getOrderIdComposed(Order $order) {
    return $this->getConfigurationByKey('prefix') . $order->id();
  }

  /**
   * Get configuration by key.
   *
   * @param string $key
   *   Key.
   *
   * @return string
   *   Configuration by key
   */
  protected function getConfigurationByKey($key) {
    return array_key_exists($key, $this->gatewayConfiguration) ? $this->gatewayConfiguration[$key] : '';
  }

  /**
   * Sets last response string as array.
   *
   * @param $responseString
   *   Response string.
   *
   * @return $this
   *   Instance of current object.
   */
  protected function setLastResponse($responseString) {
    parse_str($responseString, $this->lastResponse);
    $this->lastResponse['definition'] = PaymentApiErrorCodes::getPaymentHandlingDefinitionByCode($this->getLasResponseAttributeByKey('result'));

    return $this;
  }

  /**
   * Used to cancel a transaction.
   *
   * @param \Drupal\commerce_order\Entity\Order|NULL $order
   *   Order.
   *
   * @return array
   *   Response from DIBS.
   */
  public function cancelDibsTransaction(Order $order) {
    $options = [
      'merchant'  => $this->getConfigurationByKey('merchant'),
      'orderId'   => $this->getOrderIdComposed($order),
      'textreply' => 'Yes',
      'transact'  => $this->getPaymentRemoteId($order),
      'md5key'    => $this->getMD5Key($order),
    ];

    return $this->callApi($options, self::CANCEL_URL);
  }

  /**
   * Refund dibs payment.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Order.
   * @param string $amountToRefund
   *   Amount to refund.
   *
   * @return array
   *   Response.
   */
  public function refundDibsPayment(Order $order, $amountToRefund) {
$a =1;
    $options = [
      'merchant'  => $this->getConfigurationByKey('merchant'),
      'orderId'   => $this->getOrderIdComposed($order),
      'textreply' => 'Yes',
      'transact'  => $this->getPaymentRemoteId($order),
      'md5key'    => $this->getMD5Key($order),
      'currency'  => $order->getTotalPrice()->getCurrencyCode(),
      'amount'    => $amountToRefund,
    ];

    return $this->callApi($options, self::REFUND_URL);
  }

  /**
   * Get last response.
   *
   * @return array
   *   Last response.
   */
  public function getLastResponse() {
    return $this->lastResponse;
  }

  /**
   * Get last response attribute by key.
   *
   * @param $key
   *   Key.
   *
   * @return string
   *   Last response attribute.
   */
  public function getLasResponseAttributeByKey($key) {
    return array_key_exists($key, $this->getLastResponse()) ? $this->getLastResponse()[$key] : '';
  }

}
