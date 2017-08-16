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

  public static $_processedCreditNotes = NULL;

  public static $_creditNotes = NULL;

  /**
   * Function will return the list of CN amounts.
   */
  public static function getCreditNotes($contactID = NULL) {
    $pendingRefundStatusID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending refund');
    $where = '';
    if ($contactID) {
      $where = " AND cc.contact_id = {$contactID}";
    }
    $query = "SELECT * FROM (
        SELECT temp1.id, temp1.currency,  (temp1.balance_amount - SUM(IFNULL(ccp.amount, 0))) as credit_note_amount FROM (
          SELECT cc.id, cc.currency, IF (cc.contribution_status_id = {$pendingRefundStatusID}, (SUM(cft.total_amount)- cc.total_amount), cc.total_amount) as balance_amount
          FROM `civicrm_contribution` cc
            INNER JOIN civicrm_entity_financial_trxn ceft ON ceft.entity_id = cc.id
              AND ceft.entity_table = 'civicrm_contribution'
            INNER JOIN civicrm_financial_trxn cft ON cft.id = ceft.financial_trxn_id
              AND cft.is_payment = 1
          WHERE contribution_status_id IN (3, 7, 9) {$where}
          GROUP BY cc.id
        ) as temp1
        LEFT JOIN civicrm_creditnote_payment ccp ON ccp.contribution_id = temp1.id
        GROUP BY temp1.id
      ) as temp2 where credit_note_amount > 0
      ORDER BY credit_note_amount
    ";
    $cnPrefix = CRM_Contribute_BAO_Contribution::checkContributeSettings('credit_notes_prefix');
    $dao = CRM_Core_DAO::executeQuery($query);
    $creditNotes = array();
    while ($dao->fetch()) {
      if ($dao->credit_note_amount <= 0) continue;
      self::$_creditNotes[$dao->id] = $dao->credit_note_amount;
      $amount = CRM_Utils_Money::format($dao->credit_note_amount, $dao->currency);
      $creditNotes[$dao->id] = "{$cnPrefix}{$dao->id} : {$amount}";
    }
    return $creditNotes;
  }

  public static function getCreditNoteAmount($creditNote) {
    if (!is_array($creditNote)) {
      $creditNote = array($creditNote);
    }
    if (empty(self::$_creditNotes)) {
      self::getCreditNotes();
    }
    $creditNoteAmount = 0;
    foreach ($creditNote as $id) {
      $creditNoteAmount += CRM_Utils_Array::value($id, self::$_creditNotes, 0);
    }
    return $creditNoteAmount;
  }

  public static function addCreditNotePayments($params) {
    $op = 'edit';
    $entityId = CRM_Utils_Array::value('id', $params);
    if (!$entityId) {
      $op = 'create';
    }
    CRM_Utils_Hook::pre($op, 'CreditNotePayment', $entityId, $params);
    $creditNotePayment = new CRM_CreditNote_DAO_CreditNotePayment();
    $creditNotePayment->copyValues($params);
    $creditNotePayment->save();
    CRM_Utils_Hook::post($op, 'CreditNotePayment', $creditNotePayment->id, $creditNotePayment);
    return $creditNotePayment;
  }

  public static function processCreditNote($params) {
    if (!empty($params['credit_note_id'])) {
      $paymentInstrument = CRM_Utils_Array::value('payment_instrument_id', $params);
      if (!$paymentInstrument) {
        $paymentInstrument = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Note');
      }
      foreach (self::$_processedCreditNotes as $creditNoteId => $creditNoteDetails) {
        $contributionId = $creditNoteId;
        $contribution = civicrm_api3('Contribution', 'getSingle', array(
          'return' => array("total_amount", 'currency', 'contribution_status_id', 'financial_type_id', 'payment_instrument_id'),
	  'id' => $contributionId,
	));
        $trxnParams = array(
          'contribution_id' => $contributionId,
          'is_payment' => 1,
	  'total_amount' => -$creditNoteDetails['amount'],
	  'net_amount' => -$creditNoteDetails['amount'],
	  'from_financial_account_id' => self::getFromAccountId($contribution),
	  'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($paymentInstrument),
	  'trxn_date' => date('YmdHis'),
	  'currency' => $contribution['currency'],
	  'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
	  'payment_instrument_id' => $paymentInstrument,
	);
	$ftDao = CRM_Core_BAO_FinancialTrxn::create($trxnParams);

	// store financial item Proportionaly.
	$trxnParams = array(
          'total_amount' => $ftDao->total_amount,
          'contribution_id' => $contributionId,
        );
	CRM_Contribute_BAO_Contribution::assignProportionalLineItems($trxnParams, $ftDao->id, $contribution['total_amount']);
      }
      self::$_processedCreditNotes = NULL;
    }
  }

  public static function createCreditNotePayment($creditNotes, $financialTrxnId, $amount) {
    if (!is_array($creditNotes)) {
      $creditNotes = array($creditNotes);
    }
    $creditNotes = self::getCreditNoteDetails($creditNotes);
    foreach ($creditNotes as $creditNoteId => $creditNoteAmount) {
      $params = array(
        'contribution_id' => $creditNoteId,
        'financial_trxn_id' => $financialTrxnId,
      );
      if ($creditNoteAmount > $amount) {
        $params['amount'] = $amount;
      }
      else {
        $params['amount'] = $creditNoteAmount;
      }
      self::$_processedCreditNotes[$creditNoteId] = $params;
      self::addCreditNotePayments($params);
      $amount -= $params['amount'];
      if ($amount <= 0) break;
    }
  }

  public static function getCreditNoteDetails ($creditNotesIds) {
    $creditNotes =  array();
    if (empty(self::$_creditNotes)) {
      self::getCreditNotes();
    }
    foreach ($creditNotesIds as $id) {
      $creditNotes[$id] = CRM_Utils_Array::value($id, self::$_creditNotes, 0);
    }
    return $creditNotes;
  }

  public static function getFromAccountId($contribution) {
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']);
    $fromFinancialAccount = NULL;
    if ($contributionStatus == 'Refunded') {
      $fromFinancialAccount = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($contribution['payment_instrument_id']);
    }
    elseif ($contributionStatus == 'Pending refund') {
      $fromFinancialAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $contribution['financial_type_id'],
        'Accounts Receivable Account is'
      );
    }
    return $fromFinancialAccount;
  }

}
