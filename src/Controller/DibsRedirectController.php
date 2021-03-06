<?php

namespace Drupal\commerce_payment_dibs\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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
  public function dibsAccept() {
    $cancel = $this->currentRequest->request->get('cancel');
    $return = $this->currentRequest->request->get('return');
    $total = $this->currentRequest->request->get('total');

    if ($total > 20) {
      return new TrustedRedirectResponse($return);
    }

    return new TrustedRedirectResponse($cancel);
  }

  /**
   * Callback method which accepts POST.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function dibsReturn() {
    $cancel = $this->currentRequest->request->get('cancel');
    $return = $this->currentRequest->request->get('return');
    $total = $this->currentRequest->request->get('total');

    if ($total > 20) {
      return new TrustedRedirectResponse($return);
    }

    return new TrustedRedirectResponse($cancel);
  }

  /**
   * Callback method which accepts POST.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function dibsCancel() {
    $cancel = $this->currentRequest->request->get('cancel');
    $return = $this->currentRequest->request->get('return');
    $total = $this->currentRequest->request->get('total');

    if ($total > 20) {
      return new TrustedRedirectResponse($return);
    }

    return new TrustedRedirectResponse($cancel);
  }

}
