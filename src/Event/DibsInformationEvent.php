<?php

namespace Drupal\commerce_payment_dibs\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order event.
 *
 * @see \Drupal\commerce_order\Event\OrderEvents
 */
class DibsInformationEvent extends Event {

  const PRE_REDIRECT = 'dibs.event.pre.redirect';

  /**
   * The information.
   *
   * @var array
   */
  protected $information;

  /**
   * Constructs a new DibsInformationEvent.
   *
   * @param array $information
   *   The information.
   */
  public function __construct(array $information) {
    $this->information = $information;
  }

  /**
   * Gets the information.
   *
   * @return array
   *   Returns the information array.
   */
  public function getInformation() {
    return $this->information;
  }

}
