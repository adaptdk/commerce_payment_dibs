<?php

namespace Drupal\commerce_payment_dibs\Service;

/**
 * Class ApiErrorCodes.
 *
 * @package Drupal\commerce_payment_dibs\Service
 */
class PaymentApiErrorCodes {

  /**
   * Payment handling.
   * capture.cgi, refund.cgi, cancel.cgi, changestatus.cgi
   *
   * @var array
   */
  protected static $paymentHandlingCodes = [
    0  =>
      'Accepted.',
    1  =>
      'No response from acquirer.',
    2  =>
      'Timeout.',
    3  =>
      'Credit card expired.',
    4  =>
      'Rejected by acquirer.',
    5  =>
      'Authorisation older than 7 days.',
    6  =>
      'Transaction status on the DIBS server does not allow function.',
    7  =>
      'Amount too high.',
    8  =>
      'Error in the parameters sent to the DIBS server. An additional parameter called "message" is returned, with a value that may help identifying the error.',
    9  =>
      'Order number (orderid) does not correspond to the authorisation order number.',
    10 =>
      'Re-authorisation of the transaction was rejected.',
    11 =>
      'Not able to communicate with the acquier.',
    12 =>
      'Confirm request error.',
    14 =>
      'Capture is called for a transaction which is pending for batch - i.e. capture was already called.',
    15 =>
      'Capture or refund was blocked by DIBS.',
  ];

  /**
   * Payment authorization.
   * auth.cgi, reauth.cgi, ticket_auth.cgi
   *
   * @var array
   */
  protected static $paymentAuthorizationCodes = [
    0  =>
      'Rejected by acquirer.',
    1  =>
      'Communication problems.',
    2  =>
      'Error in the parameters sent to the DIBS server.',
    3  =>
      'Error at the acquirer.',
    4  =>
      'Credit card expired.',
    5  =>
      'Your shop does not support this credit card type, the credit card type could not be identified, or the credit card number was not modulus correct.',
    6  =>
      'Instant capture failed.',
    7  =>
      'The order number (orderid) is not unique.',
    8  =>
      'There number of amount parameters does not correspond to the number given in the split parameter.',
    9  =>
      'Control numbers (cvc) are missing.',
    10 =>
      'The credit card does not comply with the credit card type.',
    11 =>
      'Declined by DIBS Defender.',
    20 =>
      'Cancelled by user at 3D Secure authentication step.',
  ];

  /**
   * Returns payment handling definition of code.
   *
   * @param $code
   *   Code.
   *
   * @return string
   *   Definition.
   */
  public static function getPaymentHandlingDefinitionByCode($code){
    return array_key_exists($code, self::$paymentHandlingCodes) ? self::$paymentHandlingCodes[$code] : '';
  }

  /**
   * Returns payment authorization definition of code.
   *
   * @param $code
   *   Code.
   *
   * @return string
   *   Definition.
   */
  public static function getPaymentAuthorizationDefinitionByCode($code){
    return array_key_exists($code, self::$paymentAuthorizationCodes) ? self::$paymentAuthorizationCodes[$code] : '';
  }

}