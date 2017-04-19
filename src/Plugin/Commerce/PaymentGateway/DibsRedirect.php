<?php

namespace Drupal\commerce_payment_dibs\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "dibs_payment",
 *   label = "Dibs payment",
 *   display_label = "Dibs",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payment_dibs\PluginForm\OffsiteRedirect\DibsPaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "AMEX", "AMEX(DK)", "AMEX(SE)", "DIN", "DIN(DK)", "DK", "ELEC", "JCB", "MC", "MC(DK)", "MC(SE)", "MC(YX)", "MPO_Nets", "MPO_EULI", "MTRO", "MTRO(DK)", "MTRO(UK)", "MTRO(SOLO)", "MTRO(SE)", "V-DK", "VISA", "VISA(DK)", "VISA(SE)"
 *   },
 * )
 */
class DibsRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant' => '',
      'account' => '',
      'md5key1' => '',
      'md5key2' => '',
      'capturenow' => FALSE,
      'test' => FALSE,
      'creditcards' => [],
      'prefix' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['redirect_method']);
    $form['merchant'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DIBS Merchant ID'),
      '#description' => $this->t('The DIBS merchant id found under configuration => edit profile.'),
      '#default_value' => $this->configuration['merchant'],
    ];
    $form['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DIBS account'),
      '#description' => $this->t('If multiple departments utilize the same DIBS account, it may be practical to keep the transactions separate at DIBS. An account name may be inserted in this field, to separate transactions at DIBS.'),
      '#default_value' => $this->configuration['account'],
    ];
    $form['md5key1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MD5 key 1'),
      '#description' => $this->t('The first MD5 key, which can be found at Integration => MD5 keys.'),
      '#default_value' => $this->configuration['md5key1'],
    ];
    $form['md5key2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MD5 key 2'),
      '#description' => $this->t('The second MD5 key, which can be found at Integration => MD5 keys.'),
      '#default_value' => $this->configuration['md5key2'],
    ];
    $form['capturenow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capture now'),
      '#description' => $this->t('Automatically capture the payment once authenticated.'),
      '#default_value' => $this->configuration['capturenow'],
    ];
    $form['creditcards'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Credit cards'),
      '#description' => $this->t('Select credit card types here to limit the available choises. If you do not select any here, the configuration of your DIBS account will determine the available options.'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
    ];
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order id prefix'),
      '#description' => $this->t('If you have multiple sites paying via your DIBS account you can add a prefix to avoid duplicate order ids.'),
      '#default_value' => $this->configuration['prefix'],
    ];
    $cards = \Drupal::service('commerce_payment_dibs.transaction')->getCreditCards();
    $creditcards = $this->configuration['creditcards'];
    foreach ($cards as $key => $card) {
      $form['creditcards'][$key] = [
        '#type' => 'checkbox',
        '#title' => $card,
        '#default_value' => isset($creditcards[$key]) ? $creditcards[$key] : 0,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant'] = $values['merchant'];
      if (isset($values['account'])) {
        $this->configuration['account'] = $values['account'];
      }
      $this->configuration['md5key1'] = $values['md5key1'];
      $this->configuration['md5key2'] = $values['md5key2'];
      $this->configuration['capture'] = $values['capturenow'];
      $this->configuration['creditcards'] = $values['creditcards'];
      $this->configuration['prefix'] = $values['prefix'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // Get status code.
    $statusCode = $request->query->get('statuscode');
    $transact = $request->query->get('transact');
    if (!$transact) {
      \Drupal::logger('commerce_payment_dibs')->error("Transaction not found.");
      $url = Url::fromRoute('commerce_payment_dibs.dibspayment', [
        'commerce_order' => $order->id(),
      ])->toString();
      $redirect = new RedirectResponse($url);
      return $redirect;
    }
    $authkey = $request->query->get('authkey');
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $currency = Currency::load($currencyCode);
    $price = $order->getTotalPrice()->getNumber();
    $total = \Drupal::service('commerce_payment_dibs.transaction')->formatPrice($price, $currencyCode);
    $payment_gateway_plugin = PaymentGateway::load($this->entityId)->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();
    $orderId = $configuration['prefix'] . $order->id();
    $md5 = \Drupal::service('commerce_payment_dibs.transaction')->getAuthKey(
      $configuration,
      $transact,
      $currency->getNumericCode(),
      $total
    );
    \Drupal::logger('commerce_payment_dibs')->notice(json_encode([
      $configuration,
      $transact,
      $currency->getNumericCode(),
      $total,
    ]));
//    if ($md5 !== $authkey) {
//      \Drupal::logger('commerce_payment_dibs')->error($this->t("Unable to process payment since authentication keys didn't match"), ['orderId' => $orderId]);
//      return NULL;
//    }
    \Drupal::service('commerce_payment_dibs.transaction')->processPayment(
      $order,
      $transact,
      $statusCode,
      $this->entityId,
      $this->getMode()
    );
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    // Get status code.
    $statusCode = $request->get('statuscode');
    $transact = $request->get('transact');
    $authkey = $request->get('authkey');
    $orderId = $request->get('orderid');

    $payment_gateway_plugin = PaymentGateway::load($this->entityId)->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();
    if ($orderId) {
      $orderId = urldecode($orderId);
      $orderId = str_replace($configuration['prefix'], '', $orderId);
      $order = Order::load($orderId);
    }
    else {
      $order_uuid = $request->get('order-id');
      $order = \Drupal::service('entity.repository')->loadEntityByUuid('commerce_order', $order_uuid);
    }
    if (!$order) {
      \Drupal::logger('DibsFailed')->notice("Order not found.");
      \Drupal::logger('DibsFailed')->notice(serialize($request->getContent()));
      return NULL;
    }
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $currency = Currency::load($currencyCode);
    $price = $order->getTotalPrice()->getNumber();
    $total = \Drupal::service('commerce_payment_dibs.transaction')->formatPrice($price, $currencyCode);
    $md5 = \Drupal::service('commerce_payment_dibs.transaction')->getAuthKey(
      $configuration,
      $transact,
      $currency->getNumericCode(),
      $total
    );
    \Drupal::logger('commerce_payment_dibs')->notice($md5 . ' == ' . $authkey);
//    if (FALSE && $md5 !== $authkey) {
//      \Drupal::logger('commerce_payment_dibs')->error($this->t("Unable to process payment since authentication keys didn't match"), ['orderId' => $order->id()]);
//      return NULL;
//    }
    \Drupal::service('commerce_payment_dibs.transaction')->processPayment(
      $order,
      $transact,
      $statusCode,
      $this->entityId,
      $this->getMode()
    );
    if ($order->getState()->value == 'draft') {
      $transition = $order->getState()->getWorkflow()->getTransition('place');
      $order->getState()->applyTransition($transition);
      $order->save();
    }
    return NULL;
  }

}
