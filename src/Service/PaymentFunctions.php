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
   * @var \Drupal\commerce_payment_dibs\DibsTransactionService
   */
  protected $transactionService;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var array
   */
  protected $gatewayConfiguration;

  /**
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
   * Used to cancel a transaction.
   *
   * @param \Drupal\commerce_order\Entity\Order|NULL $order
   *   Order.
   *
   * @return array
   *   Response form DIBS
   */
  public function cancelDibsTransaction(Order $order) {

    $orderId = $this->getConfigurationByKey('prefix') . $order->id();

    $payments = \Drupal::entityQuery('commerce_payment')->condition('order_id', $order->id())->execute();
    $paymentId = reset($payments);
    $payment = Payment::load($paymentId);
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $price = $order->getTotalPrice()->getNumber();
    $total = $this->transactionService->formatPrice($price, $currencyCode);

    $options = [
      'merchant'  => $this->getConfigurationByKey('merchant'),
      'orderId'   => $orderId,
      'textreply' => 'Yes',
      'transact'  => $payment->getRemoteId(),
      'md5key'    => $this->transactionService->getMD5Key(
        $this->gatewayConfiguration,
        $orderId,
        $currencyCode,
        $total
      ),
    ];

    $response = $this->client->post(
      self::CANCEL_URL,
      [
        'form_params' => $options,
        'auth'        => [$this->getConfigurationByKey('api_username'), $this->getConfigurationByKey('api_password')],
      ]
    );
    $this->setLastResponse($response->getBody()->getContents());

    return $this->getLastResponse();
  }

  /**
   * Get configuration by key.
   *
   * @param $key
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
  protected function setLastResponse($responseString){
     parse_str($responseString, $this->lastResponse);
     $this->lastResponse['definition'] = PaymentApiErrorCodes::getPaymentHandlingDefinitionByCode($this->getLasResponseAttributeByKey('result'));

     return $this;
  }

  /**
   * Get last response.
   *
   * @return array
   *   Last response.
   */
  public function getLastResponse(){
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
  public function getLasResponseAttributeByKey($key){
    return array_key_exists($key, $this->getLastResponse()) ? $this->getLastResponse()[$key] : '';
  }

}
