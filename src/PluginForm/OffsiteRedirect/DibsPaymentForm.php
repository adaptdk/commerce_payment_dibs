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
    $data = [
      'orderid' => $order->id(),
      'amount' => $total,
      'currency' => $currencyCode,
      'merchant' => $configuration['merchant'],
      'billingAddress' => $billingProfile->get('address')->getValue('address'),
      'billingCity' => $billingProfile->get('address')->getValue('city'),
      'billingCountry' => $billingProfile->get('address')->getValue('country'),
      'billingFirstName' => $billingProfile->get('address')->getValue('firstName'),
      'billingLastName' => $billingProfile->get('address')->getValue('lastName'),
      'email' => $order->getEmail(),
      'acquirerlang' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'accepturl' => Url::fromRoute('commerce_payment_dibs.dibsaccept')->toString(),
      'callbackurl' => Url::fromRoute('commerce_payment_dibs.dibsreturn')->toString(),
      'cancelurl' => $form['#cancel_url'],
      'md5key' => $this->getMD5Key(
        $configuration['merchant'],
        $order->id(),
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
//      $data[$key . '-1'] = $item->getTotalPrice();
//    }
    if ($configuration['test']) {
      $data['test'] = 'true';
    }
    if ($configuration['capturenow']) {
      $data['capturenow'] = 'true';
    }
    $creditcards = [];
    if ($configuration['creditcards']) {
      $cards = $payment_gateway_plugin->getCreditCardTypes();
      foreach ($cards as $card) {
        $creditcards[] = $card->getId();
      }
      $data['paytype'] = implode(',', $creditcards);
    }
    $evt = new DibsInformationEvent($data);
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = $dispatcher->dispatch(DibsInformationEvent::PRE_REDIRECT, $evt);
    $data = array_merge($data, $event->getInformation());
    return $this->buildRedirectForm($form, $form_state, self::DIBS_REDIRECT_URL, $data, self::REDIRECT_POST);
  }

  protected function getMD5Key($merchant, $orderId, $currency, $amount) {
    $payment = $this->entity;
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
