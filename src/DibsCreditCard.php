<?php

namespace Drupal\commerce_payment_dibs;

use Drupal\commerce_payment_dibs\Event\DibsCreditCardEvent;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_payment\CreditCardType;

/**
 * Provides logic for listing card types and validating card details.
 */
class DibsCreditCard {

  /**
   * The instantiated credit card types.
   *
   * @var \Drupal\commerce_payment\CreditCardType[]
   */
  public static $types = [];

  /**
   * Gets the credit card type with the given ID.
   *
   * @param string $id
   *   The credit card type ID. For example: 'visa'.
   *
   * @return \Drupal\commerce_payment\CreditCardType
   *   The credit card type.
   */
  public static function getType($id) {
    $types = self::getTypes();
    if (!isset($types[$id])) {
      throw new \InvalidArgumentException(sprintf('Invalid credit card type "%s"', $id));
    }

    return $types[$id];
  }

  /**
   * Gets all available credit card types.
   *
   * @return \Drupal\commerce_payment\CreditCardType[]
   *   The credit card types.
   */
  public static function getTypes() {
    $definitions = [
      'DK' => [
        'id' => 'DK',
        'label' => new TranslatableMarkup('Dankort'),
        'number_prefixes' => ['4'],
      ],
      'V-DK' => [
        'id' => 'visa',
        'label' => new TranslatableMarkup('VISA-Dankort'),
        'number_prefixes' => ['4'],
      ],
      'VISA' => [
        'id' => 'VISA',
        'label' => new TranslatableMarkup('Visa'),
        'number_prefixes' => ['4'],
      ],
      'VISA(DK)' => [
        'id' => 'VISA(DK)',
        'label' => new TranslatableMarkup('Visa (DK)'),
        'number_prefixes' => ['4'],
      ],
      'VISA(SE)' => [
        'id' => 'VISA(SE)',
        'label' => new TranslatableMarkup('Visa (SE)'),
        'number_prefixes' => ['4'],
      ],
      'ELEC' => [
        'id' => 'ELEC',
        'label' => new TranslatableMarkup('VISA Electron'),
        'number_prefixes' => ['4'],
      ],
      'MC' => [
        'id' => 'MC',
        'label' => new TranslatableMarkup('MasterCard'),
        'number_prefixes' => ['51-55', '222100-272099'],
      ],
      'MC(DK)' => [
        'id' => 'MC(DK)',
        'label' => new TranslatableMarkup('MasterCard (DK)'),
        'number_prefixes' => ['51-55', '222100-272099'],
      ],
      'MC(SE)' => [
        'id' => 'MC(SE)',
        'label' => new TranslatableMarkup('MasterCard (SE)'),
        'number_prefixes' => ['51-55', '222100-272099'],
      ],
      'MC(YX)' => [
        'id' => 'MC(YX)',
        'label' => new TranslatableMarkup('MasterCard (YX)'),
        'number_prefixes' => ['51-55', '222100-272099'],
      ],
      'MPO_Nets' => [
        'id' => 'MPO_Nets',
        'label' => new TranslatableMarkup('MobilePay Online (Nets)'),
        'number_prefixes' => ['4'],
      ],
      'MPO_EULI' => [
        'id' => 'MPO_EULI',
        'label' => new TranslatableMarkup('MobilePay Online (Euroline)'),
        'number_prefixes' => ['4'],
      ],
      'MTRO' => [
        'id' => 'MTRO',
        'label' => new TranslatableMarkup('Maestro'),
        'number_prefixes' => [
          '5018', '5020', '5038', '5612', '5893', '6304',
          '6759', '6761', '6762', '6763', '0604', '6390',
        ],
        'number_lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
      ],
      'MTRO(DK)' => [
        'id' => 'MTRO(DK)',
        'label' => new TranslatableMarkup('Maestro (DK)'),
        'number_prefixes' => [
          '5018', '5020', '5038', '5612', '5893', '6304',
          '6759', '6761', '6762', '6763', '0604', '6390',
        ],
        'number_lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
      ],
      'MTRO(UK)' => [
        'id' => 'MTRO(UK)',
        'label' => new TranslatableMarkup('Maestro (UK)'),
        'number_prefixes' => [
          '5018', '5020', '5038', '5612', '5893', '6304',
          '6759', '6761', '6762', '6763', '0604', '6390',
        ],
        'number_lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
      ],
      'MTRO(SOLO)' => [
        'id' => 'MTRO(SOLO)',
        'label' => new TranslatableMarkup('Solo'),
        'number_prefixes' => [
          '5018', '5020', '5038', '5612', '5893', '6304',
          '6759', '6761', '6762', '6763', '0604', '6390',
        ],
        'number_lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
      ],
      'MTRO(SE)' => [
        'id' => 'MTRO(SE)',
        'label' => new TranslatableMarkup('Maestro (SE)'),
        'number_prefixes' => [
          '5018', '5020', '5038', '5612', '5893', '6304',
          '6759', '6761', '6762', '6763', '0604', '6390',
        ],
        'number_lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
      ],
      'AMEX' => [
        'id' => 'AMEX',
        'label' => new TranslatableMarkup('American Express'),
        'number_prefixes' => ['34', '37'],
        'number_lengths' => [15],
        'security_code_length' => 4,
      ],
      'AMEX(DK)' => [
        'id' => 'AMEX(DK)',
        'label' => new TranslatableMarkup('American Express (DK)'),
        'number_prefixes' => ['34', '37'],
        'number_lengths' => [15],
        'security_code_length' => 4,
      ],
      'AMEX(SE)' => [
        'id' => 'AMEX(SE)',
        'label' => new TranslatableMarkup('American Express (SE)'),
        'number_prefixes' => ['34', '37'],
        'number_lengths' => [15],
        'security_code_length' => 4,
      ],
      'DIN' => [
        'id' => 'DIN',
        'label' => new TranslatableMarkup('Diners Club'),
        'number_prefixes' => ['300-305', '309', '36', '38', '39'],
        'number_lengths' => [14],
      ],
      'DIN(DK)' => [
        'id' => 'DIN(DK)',
        'label' => new TranslatableMarkup('Diners Club (DK)'),
        'number_prefixes' => ['300-305', '309', '36', '38', '39'],
        'number_lengths' => [14],
      ],
      'JCB' => [
        'id' => 'JCB',
        'label' => new TranslatableMarkup('JCB'),
        'number_prefixes' => ['3528-3589'],
      ],
    ];

    $evt = new DibsCreditCardEvent($definitions);
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = $dispatcher->dispatch(DibsCreditCardEvent::DISCOVER, $evt);
    $definitions = array_merge($definitions, $event->getCreditCards());
    foreach ($definitions as $id => $definition) {
      self::$types[$id] = new CreditCardType($definition);
    }

    return self::$types;
  }

  /**
   * Gets the labels of all available credit card types.
   *
   * @return array
   *   The labels, keyed by ID.
   */
  public static function getTypeLabels() {
    $types = self::getTypes();
    $type_labels = array_map(function ($type) {
      return $type->getLabel();
    }, $types);

    return $type_labels;
  }

  /**
   * Detects the credit card type based on the number.
   *
   * @param string $number
   *   The credit card number.
   *
   * @return \Drupal\commerce_payment\CreditCardType|null
   *   The credit card type, or NULL if unknown.
   */
  public static function detectType($number) {
    if (!is_numeric($number)) {
      return FALSE;
    }
    $types = self::getTypes();
    foreach ($types as $type) {
      foreach ($type->getNumberPrefixes() as $prefix) {
        if (self::matchPrefix($number, $prefix)) {
          return $type;
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks whether the given credit card number matches the given prefix.
   *
   * @param string $number
   *   The credit card number.
   * @param string $prefix
   *   The prefix to match against. Can be a single number such as '43' or a
   *   range such as '30-35'.
   *
   * @return bool
   *   TRUE if the credit card number matches the prefix, FALSE otherwise.
   */
  public static function matchPrefix($number, $prefix) {
    if (is_numeric($prefix)) {
      return substr($number, 0, strlen($prefix)) == $prefix;
    }
    else {
      list($start, $end) = explode('-', $prefix);
      $number = substr($number, 0, strlen($start));
      return $number >= $start && $number <= $end;
    }
  }

  /**
   * Validates the given credit card number.
   *
   * @param string $number
   *   The credit card number.
   * @param \Drupal\commerce_payment\CreditCardType $type
   *   The credit card type.
   *
   * @return bool
   *   TRUE if the credit card number is valid, FALSE otherwise.
   */
  public static function validateNumber($number, CreditCardType $type) {
    if (!is_numeric($number)) {
      return FALSE;
    }
    if (!in_array(strlen($number), $type->getNumberLengths())) {
      return FALSE;
    }
    if ($type->usesLuhn() && !self::validateLuhn($number)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates the given credit card number using the Luhn algorithm.
   *
   * @param string $number
   *   The credit card number.
   *
   * @return bool
   *   TRUE if the credit card number is valid, FALSE otherwise.
   */
  public static function validateLuhn($number) {
    $total = 0;
    foreach (array_reverse(str_split($number)) as $i => $digit) {
      $digit = $i % 2 ? $digit * 2 : $digit;
      $digit = $digit > 9 ? $digit - 9 : $digit;
      $total += $digit;
    }
    return ($total % 10 === 0);
  }

  /**
   * Validates the given credit card expiration date.
   *
   * @param string $month
   *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
   * @param string $year
   *   The 4-digit numeric representation of the year, i.e. 2010.
   *
   * @return bool
   *   TRUE if the credit card expiration date is valid, FALSE otherwise.
   */
  public static function validateExpirationDate($month, $year) {
    if ($month < 1 || $month > 12) {
      return FALSE;
    }
    if ($year < date('Y')) {
      return FALSE;
    }
    elseif ($year == date('Y') && $month < date('n')) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Calculates the unix timestamp for a credit card expiration date.
   *
   * @param string $month
   *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
   * @param string $year
   *   The 4-digit numeric representation of the year, i.e. 2010.
   *
   * @return int
   *   The expiration date as a unix timestamp.
   */
  public static function calculateExpirationTimestamp($month, $year) {
    // Credit cards expire on the last day of the month.
    $month_start = strtotime($year . '-' . $month . '-01');
    $last_day = date('t', $month_start);
    return strtotime($year . '-' . $month . '-' . $last_day);
  }

  /**
   * Validates the given credit card security code.
   *
   * @param string $security_code
   *   The credit card security code.
   * @param \Drupal\commerce_payment\CreditCardType $type
   *   The credit card type.
   *
   * @return bool
   *   TRUE if the credit card security code is valid, FALSE otherwise.
   */
  public static function validateSecurityCode($security_code, CreditCardType $type) {
    if (!is_numeric($security_code)) {
      return FALSE;
    }
    if (strlen($security_code) != $type->getSecurityCodeLength()) {
      return FALSE;
    }

    return TRUE;
  }

}
