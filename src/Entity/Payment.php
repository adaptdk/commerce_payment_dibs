<?php

namespace Drupal\commerce_payment_dibs\Entity;

use Drupal\commerce_payment\Entity\Payment as CommercePayment;
use Drupal\commerce_payment_dibs\DibsTransactionService;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Class Payment.
 *
 * @package Drupal\commerce_payment_dibs\Entity
 */
class Payment extends CommercePayment {

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return mixed
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['payment_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment type'))
      ->setDescription(t('How the payment was paid (usually card type)'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Set payment type.
   *
   * @param string $type
   *   Type.
   */
  public function setPaymentType($type){
    $this->set('payment_type', $type);
  }

  /**
   * Get payment type.
   *
   * @return string
   *   Type.
   */
  public function getPaymentType(){
    return $this->get('payment_type')->first()->value;
  }

  /**
   * Get payment type description.
   *
   * @return string
   *   Description.
   */
  public function getPaymentTypeDescription(){
    return array_key_exists($this->getPaymentType(), DibsTransactionService::getTypes()) ? DibsTransactionService::getTypes()[$this->getPaymentType()] : $this->getPaymentType();
  }

}