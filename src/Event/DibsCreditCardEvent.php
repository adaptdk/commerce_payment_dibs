<?php

namespace Drupal\commerce_payment_dibs\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the credit card event.
 *
 * @see \Drupal\commerce_payment_dibs\Event\DibsCreditCardEvent
 */
class DibsCreditCardEvent extends Event {

  const DISCOVER = 'dibs.event.discover';

  /**
   * The credit card.
   *
   * @var array
   */
  protected $creditcards;

  /**
   * Constructs a new DibsCreditCardEvent.
   *
   * @param array $creditCards
   *   An array of credit card information.
   */
  public function __construct(array $creditCards) {
    $this->creditcards = $creditCards;
  }

  /**
   * Gets the credit cards.
   *
   * @return array
   *   Returns the creditCards array.
   */
  public function getCreditCards() {
    return $this->creditcards;
  }

  /**
   * Set the credit cards.
   */
  public function setCreditCards(array $creditCards) {
    $this->creditcards = $creditCards;
  }

}
