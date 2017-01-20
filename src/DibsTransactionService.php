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

  /**
   * {@inheritdoc}
   */
  public function getCreditCards() {
    $credit_cards = $this->getTypes();
    $evt = new DibsCreditCardEvent($credit_cards);
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = $dispatcher->dispatch(DibsCreditCardEvent::DISCOVER, $evt);
    $credit_cards = array_merge($credit_cards, $event->getCreditCards());
    return $credit_cards;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return [
      'DK' => 'Dankort',
      'V-DK' => 'VISA-Dankort',
      'VISA' => 'Visa',
      'VISA(DK)' => 'Visa (DK)',
      'VISA(SE)' => 'Visa (SE)',
      'ELEC' => 'VISA Electron',
      'MC' => 'MasterCard',
      'MC(DK)' => 'MasterCard (DK)',
      'MC(SE)' => 'MasterCard (SE)',
      'MC(YX)' => 'MasterCard (YX)',
      'MPO_Nets' => 'MobilePay Online (Nets)',
      'MPO_EULI' => 'MobilePay Online (Euroline)',
      'MTRO' => 'Maestro',
      'MTRO(DK)' => 'Maestro (DK)',
      'MTRO(UK)' => 'Maestro (UK)',
      'MTRO(SOLO)' => 'Solo',
      'MTRO(SE)' => 'Maestro (SE)',
      'AMEX' => 'American Express',
      'AMEX(DK)' => 'American Express (DK)',
      'AMEX(SE)' => 'American Express (SE)',
      'DIN' => 'Diners Club',
      'DIN(DK)' => 'Diners Club (DK)',
      'JCB' => 'JCB',
    ];
  }

}
