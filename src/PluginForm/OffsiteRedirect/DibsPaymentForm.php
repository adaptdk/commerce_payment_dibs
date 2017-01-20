<?php

namespace Drupal\commerce_payment_dibs\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_payment_dibs\Event\DibsInformationEvent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use CommerceGuys\Intl\Formatter\NumberFormatterInterface;

class DibsPaymentForm extends PaymentOffsiteForm {

  const DIBS_REDIRECT_URL = 'https://payment.architrade.com/paymentweb/start.action';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    // Get the configuration array.
    $configuration = $payment_gateway_plugin->getConfiguration();
    // Get the order.
    $order = $payment->getOrder();
    // Get the billing profile.
    $billingProfile = $order->getBillingProfile();
    // Format price.
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $price = $order->getTotalPrice()->getNumber();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter */
    $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance(NumberFormatterInterface::DECIMAL);
    $number_formatter->setMaximumFractionDigits(6);
    $number_formatter->setGroupingUsed(FALSE);
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
    $currencies = $currency_storage->loadMultiple();
    $currency = $currencies[$currencyCode];
    $number_formatter->setMinimumFractionDigits($currency->getFractionDigits());
    $total = $number_formatter->format($price);
    $total = str_replace(',', '', $total);
    // Set data values.
    $billingAddress = $billingProfile->address->first()->getValue();
    $orderId = $configuration['prefix'] . $order->id();
    $data = [
      'orderid' => $orderId,
      'amount' => $total,
      'currency' => $currencyCode,
      'merchant' => $configuration['merchant'],
      'billingAddress' => $billingAddress['address_line1'],
      'billingCity' => $billingAddress['locality'],
      'billingCountry' => $billingAddress['locality'],
      'billingFirstName' => $billingAddress['given_name'],
      'billingLastName' => $billingAddress['family_name'],
      'email' => $order->getEmail(),
      'acquirerlang' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'accepturl' => $form['#return_url'],
      'callbackurl' => Url::fromRoute('commerce_payment_dibs.dibscallback', ['order_uuid' => $order->uuid()], ['absolute' => TRUE])->toString(),
      'cancelurl' => $form['#cancel_url'],
      'md5key' => \Drupal::service('commerce_payment_dibs.transaction')->getMD5Key(
        $payment,
        $configuration['merchant'],
        $orderId,
        $currencyCode,
        $total
      ),
    ];
//    if ($shipments = $order->get('shipments')) {
//      if (count($shipments) == 1) {
//        $shipment = $shipments[0];
//        $data['delivery1.First name'] = '';
//        $data['delivery1.Last name'] = '';
//        $data['delivery1.Address'] = '';
//        $data['delivery1.City'] = '';
//        $data['delivery1.Country'] = '';
//      }
//    }
//    $orderLines = $order->getItems();
//    $count = 0;
//    $key = 'ordline' . $count;
//    $data[$key . '-0'] = 'Description';
//    $data[$key . '-1'] = 'Price';
//    foreach ($orderLines as $item) {
//      $count++;
//      $key = 'ordline' . $count;
//      $data[$key . '-0'] = $item->getTitle();
//      $data[$key . '-1'] = $item->getTotalPrice()->getNumber();
//    }
    if ($configuration['mode'] == 'test') {
      $data['test'] = 'true';
    }
    if ($configuration['capturenow']) {
      $data['capturenow'] = 'true';
    }
    $creditcards = [];
    if ($configuration['creditcards']) {
      $cards = $payment_gateway_plugin->getCreditCardTypes();
      foreach ($cards as $card) {
        if ($configuration['creditcards'][$card->getId()]) {
          $creditcards[] = $card->getId();
        }
      }
      $data['paytype'] = implode(',', $creditcards);
    }
    $evt = new DibsInformationEvent($data);
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = $dispatcher->dispatch(DibsInformationEvent::PRE_REDIRECT, $evt);
    $data = array_merge($data, $event->getInformation());
    return $this->buildRedirectForm($form, $form_state, self::DIBS_REDIRECT_URL, $data, self::REDIRECT_POST);
  }

}
