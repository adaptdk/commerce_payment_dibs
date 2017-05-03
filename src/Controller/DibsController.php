<?php

namespace Drupal\commerce_payment_dibs\Controller;

use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides the checkout form page.
 */
class DibsController implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The cart session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * Constructs a new CheckoutController object.
   *
   * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_session
   *   The cart session.
   */
  public function __construct(CheckoutOrderManagerInterface $checkout_order_manager, FormBuilderInterface $form_builder, CartSessionInterface $cart_session) {
    $this->checkoutOrderManager = $checkout_order_manager;
    $this->formBuilder = $form_builder;
    $this->cartSession = $cart_session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_checkout.checkout_order_manager'),
      $container->get('form_builder'),
      $container->get('commerce_cart.cart_session')
    );
  }

  /**
   * Provides the "return" checkout payment page.
   *
   * Redirects to the next checkout page, completing checkout.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function returnCheckoutPage(OrderInterface $commerce_order, Request $request) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $commerce_order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
    }
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $commerce_order->checkout_flow->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();

    $statusCode = $request->query->get('statuscode');
    $configuration = $payment_gateway_plugin->getConfiguration();
    $paymentSuccess = \Drupal::service('commerce_payment_dibs.transaction')->isPaymentStatusSuccess($configuration, $statusCode);
    if (!$paymentSuccess) { //Payment was not approved
      \Drupal::logger('DibsFailed')->notice("Payment status was not successful (returnCheckoutPage) - redirect to review: " . $statusCode);
      $redirect_step = $checkout_flow_plugin->getPreviousStepId(); //Go back to preview step
      $payment_gateway_plugin->onCancel($commerce_order, $request); //Make sure the order is not being processed
    } else { //Success
      \Drupal::logger('DibsSuccess')->notice("Payment status was fine (returnCheckoutPage) - redirect to payment: " . $statusCode);
      try {
        $response = $payment_gateway_plugin->onReturn($commerce_order, $request);
        if ($response) {
          return $response;
        }
        $redirect_step = $checkout_flow_plugin->getNextStepId('payment');
      }
      catch (PaymentGatewayException $e) {
        \Drupal::logger('commerce_payment')->error($e->getMessage());
        drupal_set_message(t('Payment failed at the payment server. Please review your information and try again.'), 'error');
        $redirect_step = $checkout_flow_plugin->getPreviousStepId('payment');
      }
    }
    \Drupal::logger('commerce_payment_dibs')->notice('Redirect step: ' . $redirect_step);
    $checkout_flow_plugin->redirectToStep($redirect_step);
  }

  public function validatePayment(OrderInterface $commerce_order, Request $request) {
    $result = \Drupal::entityQuery('commerce_payment')
      ->condition('order_id', $commerce_order->id())
      ->execute();
    if (!empty($result)) {
      $url = Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $commerce_order->id(),
        'step' => 'payment',
      ])->toString();
      $redirect = new RedirectResponse($url);
      return $redirect;
    }
    else {
      $request->headers->set('refresh', [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'MobileOptimized',
          'content' => 'width',
        ]]
      );
      $return = [
        '#markup' => t('Waiting for payment.'),
      ];
      $return['#attached']['html_head'][] = [[
          '#tag' => 'meta',
          '#attributes' => [
            'http-equiv' => 'refresh',
            'content' => '10',
          ],
        ], 'refresh'
      ];
      return $return;
    }
  }
}
