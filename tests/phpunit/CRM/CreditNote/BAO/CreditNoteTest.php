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

}
