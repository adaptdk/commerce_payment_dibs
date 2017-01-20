<?php

namespace Drupal\commerce_payment_dibs\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is a dummy controller for mocking an off-site gateway.
 */
class DibsRedirectController implements ContainerInjectionInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new DibsRedirectController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Callback method which accepts POST.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function dibsCallback($order_uuid) {
    \Drupal::logger('commerce_payment_dibs')->notice(json_encode($_REQUEST));
    $statuscode = $this->currentRequest->get('statuscode');
    $transact = $this->currentRequest->get('transact');
    $authkey = $this->currentRequest->get('authkey');
    $order = EntityRepository::loadEntityByUuid('commerce_order', $order_uuid);
    $payment = $order->get('payment');
    $payment->setRemoteId($transact);
    $payment->setRemoteState($statuscode);
    if ($statuscode == '1') {
      // @todo set payment as declined.
      // $payment->setState();
    }
    else {
      $payment->setAuthorizedTime(REQUEST_TIME);
    }
    $payment->save();
    return new Response();
  }

}
