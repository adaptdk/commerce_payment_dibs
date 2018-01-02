<?php

namespace Drupal\commerce_payment_dibs\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_payment_dibs\DibsLanguages;
use Drupal\commerce_payment_dibs\DibsUrls;
use Drupal\commerce_payment_dibs\Event\DibsInformationEvent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DibsPaymentForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $transactionService = \Drupal::service('commerce_payment_dibs.transaction');
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
    $total = $transactionService->formatPrice($price, $currencyCode);
    // Set data values.
    $billingAddress = $billingProfile->address->first()->getValue();
    $orderId = $configuration['prefix'] . $order->id();
    // Set accept url.
    $acceptUrl = Url::fromRoute('commerce_payment_dibs.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    // Set callback url.
    $callbackUrl = $payment_gateway_plugin->getNotifyUrl()->toString() . '?order-id=' . $order->uuid();
    // Generate MD5.
    $md5 = $transactionService->getMD5Key(
      $configuration,
      $orderId,
      $currencyCode,
      $total
    );
    // Get order language.
    $orderLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // Set default values.
    $data = [
      'accepturl' => $acceptUrl,
      'amount' => $total,
      'callbackurl' => $callbackUrl,
      'cancelurl' => $form['#cancel_url'],
      'currency' => $currencyCode,
      'merchant' => $configuration['merchant'],
      'orderid' => $orderId,
      'billingAddress' => $billingAddress['address_line1'],
      'billingCity' => $billingAddress['locality'],
      'billingCountry' => $billingAddress['locality'],
      'billingFirstName' => $billingAddress['given_name'],
      'billingLastName' => $billingAddress['family_name'],
      'email' => $order->getEmail(),
      'acquirerlang' => $orderLanguage,
      'md5key' => $md5,
      'type' => 'flex',
      'decorator' => 'responsive',
      'calcfee' => $configuration['calcfee'],
    ];
    if (in_array($orderLanguage, DibsLanguages::languages)) {
      $data['lang'] = $orderLanguage;
    }
    if ($configuration['mode'] == 'test') {
      $data['test'] = 'true';
    }
    if ($configuration['capturenow']) {
      $data['capturenow'] = 'true';
    }
    if ($configuration['creditcards']) {
      $creditcards = array_keys(array_filter($configuration['creditcards']));
      $data['paytype'] = implode(',', $creditcards);
    }
    // Dispatch event to allow other modules to add information.
    $evt = new DibsInformationEvent($data);
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = $dispatcher->dispatch(DibsInformationEvent::PRE_REDIRECT, $evt);
    $data = array_merge($data, $event->getInformation());

    return $this->buildRedirectForm($form, $form_state, DibsUrls::DIBS_REDIRECT_URL, $data, self::REDIRECT_POST);
  }

}
