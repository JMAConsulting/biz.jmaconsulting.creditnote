<?php
/*
 +--------------------------------------------------------------------+
 | Creit Note Extension                                               |
 +--------------------------------------------------------------------+
 | Copyright (C) 2016-2017 JMA Consulting                             |
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
 *
 * @package CRM
 * @copyright JMA Consulting (c) 2016-2017
 * $Id$
 *
 */
class CRM_CreditNote_BAO_CreditNote extends CRM_Core_DAO {

  /**
   * Function will return the list of CN amounts.
   */ 
  public static function getCreditNotes() {
    return array(
      'financial_trxn_id_1' => 'CN_112 : $125',
      'financial_trxn_id_2' => 'CN_127 : $143',
    );
  }

  public static function getCreditNoteAmount($creditNote) {
    return 100;
  }

  public static function addCreditNote($params) {
    $op = 'edit';
    $entityId = CRM_Utils_Array::value('id', $params);
    if (!$entityId) {
      $op = 'create';
    }
    CRM_Utils_Hook::pre($op, 'CreditNoteMemo', $entityId, $params);
    $creditNoteMemo = CRM_CreditNote_DAO_CreditNoteMemo();
    $creditNoteMemo->copyValues($params);
    $creditNoteMemo->save();
    CRM_Utils_Hook::post($op, 'CreditNoteMemo', $creditNoteMemo->id, $creditNoteMemo);
    return $creditNoteMemo;
  }
  
  public static function addCreditNotePayments($params) {
    $op = 'edit';
    $entityId = CRM_Utils_Array::value('id', $params);
    if (!$entityId) {
      $op = 'create';
    }
    CRM_Utils_Hook::pre($op, 'CreditNoteMemoPayment', $entityId, $params);
    $creditNoteMemoPayment = CRM_CreditNote_DAO_CreditNoteMemoPayment();
    $creditNoteMemoPayment->copyValues($params);
    $creditNoteMemoPayment->save();
    CRM_Utils_Hook::post($op, 'CreditNoteMemoPayment', $creditNoteMemoPayment->id, $creditNoteMemoPayment);
    return $creditNoteMemoPayment;
  }

}