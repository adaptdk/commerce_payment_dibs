<?php

namespace Drupal\commerce_payment_dibs\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the paytypes event.
 *
 * @see \Drupal\commerce_payment_dibs\Event\DibsPaytypesEvent
 */
class DibsPaytypesEvent extends Event {

  const DISCOVER = 'dibs.event.discover';

  /**
   * The paytype.
   *
   * @var array
   */
  protected $paytypes;

  /**
   * Constructs a new DibsPaytypesEvent.
   *
   * @param array $paytypes
   *   An array of paytypes.
   */
  public function __construct(array $paytypes) {
    $this->paytypes = $paytypes;
  }

  /**
   * Gets the order.
   *
   * @return array
   *   Returns the paytypes array.
   */
  public function getPaytypes() {
    return $this->paytypes;
  }

  /**
   * Set the paytypes.
   */
  public function setPaytypes(array $paytypes) {
    $this->paytypes = $paytypes;
  }

}
