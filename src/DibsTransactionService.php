<?php

namespace Drupal\commerce_payment_dibs;

use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment_dibs\Entity\Payment;
use Drupal\commerce_payment_dibs\Event\DibsCreditCardEvent;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class DibsTransactionService.
 *
 * @package Drupal\commerce_payment_dibs
 */
class DibsTransactionService extends DefaultPluginManager implements DibsTransactionServiceInterface {

  Use StringTranslationTrait;

  /**
   * @var \Drupal\commerce_payment\Entity\Payment
   */
  protected $paymentGatewayManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(OrderInterface $order, $transactionId, $statusCode, $payment_gateway_id, $mode, $paytype) {
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('remote_id', $transactionId)
      ->condition('order_id', $order->id());

    $payments = $query->execute();
    if (empty($payments)) {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      /** @var \Drupal\commerce_payment_dibs\Entity\Payment $payment */
      $payment = $payment_storage->create([
        'state' => 'authorization',
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $payment_gateway_id,
        'order_id' => $order->id(),
        'test' => $mode === 'test',
        'remote_id' => ($transactionId) ? $transactionId : '',
        'remote_state' => ($statusCode) ? $statusCode: '',
        'payment_type' => $paytype
      ]);
      if ($statusCode == '2') {
        $payment->setAuthorizedTime(REQUEST_TIME);
        $transition = $payment->getState()->getWorkflow()->getTransition('authorize');
        $payment->getState()->applyTransition($transition);
      }
      else {
        $transition = $payment->getState()->getWorkflow()->getTransition('void');
        $payment->getState()->applyTransition($transition);
      }
    }
    else {
      $payment_ids = array_keys($payments);
      $payment = Payment::load(current($payment_ids));
      $payment->setRemoteId($transactionId);
      $payment->setRemoteState($statusCode);
      $payment->setPaymentType($paytype);

      if ($statusCode == '2') {
        $payment->setAuthorizedTime(REQUEST_TIME);
        $transition = $payment->getState()->getWorkflow()->getTransition('authorize');
        $payment->getState()->applyTransition($transition);
      }
      else {
        // @todo set payment as declined.
        $transition = $payment->getState()->getWorkflow()->getTransition('void');
        $payment->getState()->applyTransition($transition);
      }
    }
    $payment->save();
    drupal_set_message($this->t('Payment was processed'));
  }

  /**
   * {@inheritdoc}
   */
  public function formatPrice($number, $currencyCode) {
    /** @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter */
    $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
    $number_formatter = $number_formatter_factory->createInstance(NumberFormatterInterface::DECIMAL);
    $number_formatter->setMaximumFractionDigits(6);
    $number_formatter->setGroupingUsed(FALSE);
    $number_formatter->setMinimumFractionDigits(2);
    $total = $number_formatter->format($number);
    $total = str_replace(',', '', $total);
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function getMD5Key($configuration, $orderId, $currency, $amount) {
    $key1 = $configuration['md5key1'];
    $key2 = $configuration['md5key2'];
    $merchant = $configuration['merchant'];
    $parameters = [
      'merchant' => $merchant,
      'orderid' => $orderId,
      'currency' => $currency,
      'amount' => $amount,
    ];

    $parameter_string = http_build_query($parameters);
    return $this->getMd5Hash($key1, $key2, $parameter_string);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthKey($configuration, $transaction, $currency, $amount) {
    $key1 = $configuration['md5key1'];
    $key2 = $configuration['md5key2'];
    $parameters = [
      'transact' => $transaction,
      'amount' => $amount,
      'currency' => $currency,
    ];
    $parameter_string = http_build_query($parameters);

    return $this->getMd5Hash($key1, $key2, $parameter_string);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateMac($msg, $hmac_key) {
    //Decode the hex encoded key.
    $hmac_key = pack('H*', $hmac_key);

    //Sort the key=>value array ASCII-betically according to the key
    ksort($msg, SORT_STRING);

    //Create message from sorted array.
    $msg = urldecode(http_build_query($msg));

    //Calculate and return the SHA-256 HMAC using algorithm for 1 key
    return hash_hmac("sha256", $msg, $hmac_key);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreditCards() {
    $credit_cards = self::getTypes();
    $evt = new DibsCreditCardEvent($credit_cards);
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = $dispatcher->dispatch(DibsCreditCardEvent::DISCOVER, $evt);
    $credit_cards = array_merge($credit_cards, $event->getCreditCards());
    return $credit_cards;
  }

  /**
   * {@inheritdoc}
   */
  public static function getTypes() {
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

  protected function getMd5Hash($key1, $key2, $parameter_string) {
    return MD5($key2 . MD5($key1 . $parameter_string));
  }

  /**
   * {@inheritdoc}
   */
  public function isPaymentStatusSuccess($configuration, $statusCode) {
    if ($configuration['capture']) { //Dibs capture success is 5
      return $statusCode == DibsCallbackStatus::CAPTURE_COMPLETED;
    } else { //We assume we want an authorization. Dibs auth success is 2
      return $statusCode ==  DibsCallbackStatus::APPROVED;
    }
  }
}
