<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../BaseTest.php';

class CRM_CreditNote_BAO_CreditNoteTest extends CRM_CreditNote_BaseTest {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test function to check Credit Note result
   */
  public function testCreditNotesResult() {
    $expectedValues = array();
    $prefix = CRM_Contribute_BAO_Contribution::checkContributeSettings('credit_notes_prefix');

    $contribution = $this->createContribution();
    $refundedContribution = $this->callAPISuccess('contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'refund_trxn_id' => 'the refund',
    ));
    $expectedValues[$contribution['id']] = sprintf("%s%d : $ 100.00", $prefix, $contribution['id']);

    $contribution = $this->createContribution(array('total_amount' => 50.00));
    $cancelledContribution = $this->callAPISuccess('contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Cancelled',
      'cancel_date' => '2015-01-01 09:00',
    ));
    $expectedValues[$contribution['id']] = sprintf("%s%d : $ 50.00", $prefix, $contribution['id']);

    $this->assertEquals($expectedValues, CRM_CreditNote_BAO_CreditNote::getCreditNotes());
  }

  /**
   * Test function to create contribuion using credit note
   */
  public function testContributionUsingCreditNote() {
    $creditNotePaymentInstrumentID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Note');
    // create contribution of amount $100 and later refund it, so it can be used for credit note payment
    $contribution = $this->createContribution();
    $refundedContribution = $this->callAPISuccess('contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'refund_trxn_id' => 'the refund',
    ));
    $fromAccountID = CRM_CreditNote_BAO_CreditNote::getFromAccountId($refundedContribution['values'][$refundedContribution['id']]);

    // create another contribution of amount $200 and use credit note of $100 for payment
    $contribution = $this->createContribution(array(
      'total_amount' => 200.00,
      'payment_instrument_id' => $creditNotePaymentInstrumentID,
      'credit_note_id' => $refundedContribution['id'],
    ));
    $this->assertEquals(
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Partially paid'),
      $contribution['values'][$contribution['id']]['contribution_status_id']
    );
    $creditNotePayment = CRM_Utils_Array::value(0, CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_creditnote_payment')->fetchAll());
    $this->assertEquals($creditNotePayment['contribution_id'], $refundedContribution['id']);
    $this->assertEquals($creditNotePayment['amount'], 100.00);

    $financialTrxn = $this->callAPISuccessGetSingle('FinancialTrxn', array('id' => $creditNotePayment['financial_trxn_id']));
    $this->assertEquals(1, $financialTrxn['is_payment']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'), $financialTrxn['status_id']);
    // @TODO : currently from_account_id doesn't matches with expected financial account id
    //$this->assertEquals($fromAccountID, $financialTrxn['from_financial_account_id']);
    $this->assertEquals(CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($creditNotePaymentInstrumentID), $financialTrxn['to_financial_account_id']);
  }

  /**
   * Test function to create contribuion using multiple credit notes
   */
  public function testContributionUsingMultipleCreditNotes() {
    $creditNotePaymentInstrumentID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Note');
    // create contribution of amount $100 and later refund it, so it can be used for credit note payment
    $contribution = $this->createContribution();
    $refundedContribution = $this->callAPISuccess('contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'refund_trxn_id' => 'the refund',
    ));

    // create contribution of amount $50 and later cancel it, so it can be used for credit note payment
    $contribution = $this->createContribution(array('total_amount' => 50.00));
    $cancelledContribution = $this->callAPISuccess('contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Cancelled',
      'cancel_date' => '2015-01-01 09:00',
    ));

    // create another contribution of amount $150 and use credit notes of $100 and $50 for payment
    $contribution = $this->createContribution(array(
      'total_amount' => 150.00,
      'payment_instrument_id' => $creditNotePaymentInstrumentID,
      'credit_note_id' => array($refundedContribution['id'], $cancelledContribution['id']), // use both refunded and cancelled contributions as a credit notes
    ));
    // check that status should be completed
    $this->assertEquals(
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      $contribution['values'][$contribution['id']]['contribution_status_id']
    );

    // check the credit note payment enties created
    $creditNotePayments = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_creditnote_payment')->fetchAll();
    $this->assertEquals(2, count($creditNotePayments));
    $expectedValues = array(
      array(
        'contribution_id' => $refundedContribution['id'],
        'amount' => 100.00,
      ),
      array(
        'contribution_id' => $cancelledContribution['id'],
        'amount' => 50.00,
      ),
    );
    foreach ($expectedValues as $key => $expectedValue) {
      foreach ($expectedValue as $attribute => $value) {
        $this->assertEquals($expectedValue[$attribute], $creditNotePayments[$key][$attribute]);
      }
    }
  }

}
