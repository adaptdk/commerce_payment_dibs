<?php

namespace Drupal\commerce_payment_dibs\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment_dibs\DibsTransactionServiceInterface;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

  use LoggerChannelTrait;

  public $transationService;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, DibsTransactionServiceInterface $transactionService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->transationService = $transactionService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_payment_dibs.transaction')
    );
  }

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
      'api_username' => '',
      'api_password' => '',
      'calcfee' => FALSE,
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
    $form['calcfee'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Calculate fee'),
      '#description' => $this->t('Automatically add the fee for the transaction to the payment.'),
      '#default_value' => $this->configuration['calcfee'],
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
    $form['api_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api user name'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['api_username'],
    ];
    $form['api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api password'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['api_password'],
    ];

    $cards = $this->transationService->getCreditCards();
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
      $this->configuration['calcfee'] = $values['calcfee'];
      $this->configuration['api_password'] = $values['api_password'];
      $this->configuration['creditcards'] = $values['creditcards'];
      $this->configuration['prefix'] = $values['prefix'];
      $this->configuration['api_username'] = $values['api_username'];
      $this->configuration['api_password'] = $values['api_password'];
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
      return $this->getRedirectResponse($order);
    }
    $authkey = $request->query->get('authkey');
    // Calculate total.
    $total = $this->getTotalAmount($order, $request);
    $this->getLogger('dibs')->info("Total: " . $total);
    // Get configuration.
    $configuration = $this->getConfiguration();
    // Setup variables for validation.
    $orderId = $configuration['prefix'] . $order->id();
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $currency = Currency::load($currencyCode);
    // generate md5 key.
    $this->getLogger('dibs')->info('Calculation variables: ' . $transact . ' : ' . $currency->getNumericCode() . ' : ' . $total);
    $md5 = $this->transationService->getAuthKey(
      $configuration,
      $transact,
      $currency->getNumericCode(),
      $total
    );
    // Compare keys.
    if ($md5 !== $authkey) {
      $message = $this->t("Unable to process payment since authentication keys didn't match");
      $this->getLogger('DibsAuthenticationFailed')->error($message, ['orderId' => $orderId]);
      return NULL;
    }
    $this->transationService->processPayment(
      $order,
      $transact,
      $statusCode,
      $this->entityId,
      $this->getMode(),
      $request->get('paytype')
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

    // Get configuration.
    $configuration = $this->getConfiguration();

    $paymentSuccess = $this->transationService->isPaymentStatusSuccess($configuration, $statusCode);
    if (!$paymentSuccess) { //Payment failed - don't process the payment
      $this->getLogger('DibsFailed')->notice("Payment status was not successful (onNotify): " . $statusCode);
      return NULL;
    } else {
      $this->getLogger('DibsSuccess')->notice("Payment status was fine (onNotify): " . $statusCode);
    }

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
      $this->getLogger('DibsFailed')->notice("Order not found.");
      return NULL;
    }
    // Calculate total.
    $total = $this->getTotalAmount($order, $request);
    // Setup variables for validation.
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $currency = Currency::load($currencyCode);
    // generate md5 key.
    $md5 = $this->transationService->getAuthKey(
      $configuration,
      $transact,
      $currency->getNumericCode(),
      $total
    );
    // Compare keys.
    if ($md5 !== $authkey) {
      $message = $this->t("Unable to process payment since authentication keys didn't match");
      $this->getLogger('DibsAuthenticationFailed')->error($message, ['orderId' => $order->id()]);
      return NULL;
    }
    $this->transationService->processPayment(
      $order,
      $transact,
      $statusCode,
      $this->entityId,
      $this->getMode(),
      $request->get('paytype')
    );
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $statusCode = $request->query->get('statuscode');
    if (isset($statusCode)) { //It must be due to a failed payment
      drupal_set_message($this->t('Payment failed at the payment server. Please review your information and try again.'), 'error');
    } else { //The user has cancelled the payment
      drupal_set_message($this->t('You have canceled checkout at @gateway but may resume the checkout process here when you are ready.', [
        '@gateway' => $this->getDisplayLabel(),
      ]));
    }
  }

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  protected function getRedirectResponse(OrderInterface $order) {
    $this->getLogger('Dibs')->error("Transaction not found.");
    $url = Url::fromRoute('commerce_payment_dibs.dibspayment', [
      'commerce_order' => $order->id(),
    ])->toString();
    $redirect = new RedirectResponse($url);
    return $redirect;
  }

  /**
   * Get the total amount.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The current order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return int
   *   A formatted price.
   */
  protected function getTotalAmount(OrderInterface $order, Request $request) {
    // Set values for calculating amount.
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $amount = $request->get('amount');
    if ($amount === NULL) {
      $amount = $order->getTotalPrice()->getNumber();
    }
    $calcFee = $request->get('calcfee');
    $fee = $request->get('fee');
    // Calculate total.
    $total = $this->getCalculationAmount($currencyCode, $amount, $calcFee, $fee);
    return $total;
  }

  /**
   * Get the calculated total amount.
   * 
   * @param string $currencyCode
   *   The currency code.
   * @param string $amount
   *   The order amount.
   * @param bool $calcFee
   *   Should the fee be added to the calculation.
   * @param string $fee
   *   The credit card fee.
   *
   * @return number
   *   The calculated total.
   */
  protected function getCalculationAmount($currencyCode, $amount, $calcFee, $fee) {
    $price = new Price((string) $amount, $currencyCode);
    if ($calcFee == 1 && $fee != '') {
      $fee = new Price((string) $fee, $currencyCode);
      $price = $price->add($fee);
    }
    $total = $price->getNumber();
    if (strpos($total, '.')) {
      $total = number_format($total, 2, '', '');
    }
    return $total;
  }
}
