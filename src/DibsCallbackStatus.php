<?php

namespace Drupal\commerce_payment_dibs;

/**
 * Class DibsCallbackStatus
 *
 * @package Drupal\commerce_payment_dibs
 */
class DibsCallbackStatus {

  const TRANSACTION_INSERTED = 0;

  const DECLINED = 1;

  const APPROVED = 2;

  const CAPTURE_SENT_TO_ACQUIRER = 3;

  const CAPTURE_DECLINED_BY_ACQUIRER = 4;

  const CAPTURE_COMPLETED = 5;

  const AUTHORIZATION_DELETED = 6;

  const CAPTURE_BALANCED = 7;

  const PARTIALLY_REFUNDED = 8;

  const REFUND_SENT_TO_ACQUIRER = 9;

  const REFUND_DECLINED = 10;

  const REFUND_COMPLETED = 11;

  const CAPTURE_PENDING = 12;

  const TICKET_TRANSACTION = 13;

  const DELETED_TICKET_TRANSACTION = 14;

  const REFUND_PENDING = 15;

  const WAITING_FOR_SHOP_APPROVAL = 16;

  const DECLINED_BY_DIBS = 17;

  const MULTICAP_TRANSACTION_OPEN = 18;

  const MULTICAP_TRANSACTION_CLOSED = 19;

  const POSTPONED = 26;

}