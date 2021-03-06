<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
*/
/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 *
 * Generated from xml/schema/CRM/CreditNote/CreditNotePayment.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:e21dfb56b654dd78e0ead9af1ba5bf77)
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
/**
 * CRM_CreditNote_DAO_CreditNotePayment constructor.
 */
class CRM_CreditNote_DAO_CreditNotePayment extends CRM_Core_DAO {
  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  static $_tableName = 'civicrm_creditnote_payment';
  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var boolean
   */
  static $_log = true;
  /**
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to Contribution ID
   *
   * @var int unsigned
   */
  public $contribution_id;
  /**
   * Financial trxn id
   *
   * @var int unsigned
   */
  public $financial_trxn_id;
  /**
   * Total amount of this contribution. Use market value for non-monetary gifts.
   *
   * @var float
   */
  public $amount;
  /**
   * Class constructor.
   */
  function __construct() {
    $this->__table = 'civicrm_creditnote_payment';
    parent::__construct();
  }
  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static ::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contributionid', 'civicrm_contribution', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'financial_trxn_id', 'civicrm_financial_trxn', 'id');
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'table_name' => 'civicrm_creditnote_payment',
          'entity' => 'CreditNotePayment',
          'bao' => 'CRM_CreditNote_DAO_CreditNotePayment',
          'localizable' => 0,
        ) ,
        'contribution_id' => array(
          'name' => 'contribution_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => 'FK to Contribution ID',
          'required' => true,
          'table_name' => 'civicrm_creditnote_payment',
          'entity' => 'CreditNotePayment',
          'bao' => 'CRM_CreditNote_DAO_CreditNotePayment',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contribute_DAO_Contribution',
        ) ,
        'financial_trxn_id' => array(
          'name' => 'financial_trxn_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => 'Financial trxn id',
          'table_name' => 'civicrm_creditnote_payment',
          'entity' => 'CreditNotePayment',
          'bao' => 'CRM_CreditNote_DAO_CreditNotePayment',
          'localizable' => 0,
          'FKClassName' => 'CRM_Financial_DAO_FinancialTrxn',
        ) ,
        'amount' => array(
          'name' => 'amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Amount') ,
          'description' => 'Total amount of this contribution. Use market value for non-monetary gifts.',
          'required' => true,
          'precision' => array(
            20,
            2
          ) ,
          'import' => true,
          'where' => 'civicrm_creditnote_payment.amount',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'table_name' => 'civicrm_creditnote_payment',
          'entity' => 'CreditNotePayment',
          'bao' => 'CRM_CreditNote_DAO_CreditNotePayment',
          'localizable' => 0,
          'html' => array(
            'type' => 'Text',
          ) ,
        ) ,
      );
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }
  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }
  /**
   * Returns the names of this table
   *
   * @return string
   */
  static function getTableName() {
    return self::$_tableName;
  }
  /**
   * Returns if this table needs to be logged
   *
   * @return boolean
   */
  function getLog() {
    return self::$_log;
  }
  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &import($prefix = false) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'creditnote_payment', $prefix, array());
    return $r;
  }
  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &export($prefix = false) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'creditnote_payment', $prefix, array());
    return $r;
  }
  /**
   * Returns the list of indices
   */
  public static function indices($localize = TRUE) {
    $indices = array();
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }
}
