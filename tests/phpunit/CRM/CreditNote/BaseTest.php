<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

class CRM_CreditNote_BaseTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  protected $_contactID;
  protected $_contributionID;
  protected $_contribution;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    $this->enableCreditNoteForStatus();
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
     * wrap api functions.
     * so we can ensure they succeed & throw exceptions without litterering the test with checks
     *
     * @param string $entity
     * @param string $action
     * @param array $params
     * @param mixed $checkAgainst
     *   Optional value to check result against, implemented for getvalue,.
     *   getcount, getsingle. Note that for getvalue the type is checked rather than the value
     *   for getsingle the array is compared against an array passed in - the id is not compared (for
     *   better or worse )
     *
     * @return array|int
     */
    public function callAPISuccess($entity, $action, $params, $checkAgainst = NULL) {
      $params = array_merge(array(
          'debug' => 1,
        ),
        $params
      );
      switch (strtolower($action)) {
        case 'getvalue':
          return $this->callAPISuccessGetValue($entity, $params, $checkAgainst);
        case 'getsingle':
          return $this->callAPISuccessGetSingle($entity, $params, $checkAgainst);
        case 'getcount':
          return $this->callAPISuccessGetCount($entity, $params, $checkAgainst);
      }
      $result = civicrm_api3($entity, $action, $params);
      return $result;
    }
    public function callAPISuccessGetValue($entity, $params, $type = NULL) {
      $params += array(
        'debug' => 1,
      );
      $result = civicrm_api3($entity, 'getvalue', $params);
      if ($type) {
        if ($type == 'integer') {
          // api seems to return integers as strings
          $this->assertTrue(is_numeric($result), "expected a numeric value but got " . print_r($result, 1));
        }
        else {
          $this->assertType($type, $result, "returned result should have been of type $type but was ");
        }
      }
      return $result;
    }
    public function callAPISuccessGetSingle($entity, $params, $checkAgainst = NULL) {
      $params += array(
        'debug' => 1,
      );
      $result = civicrm_api3($entity, 'getsingle', $params);
      if (!is_array($result) || !empty($result['is_error']) || isset($result['values'])) {
        throw new Exception('Invalid getsingle result' . print_r($result, TRUE));
      }
      if ($checkAgainst) {
        // @todo - have gone with the fn that unsets id? should we check id?
        $this->checkArrayEquals($result, $checkAgainst);
      }
      return $result;
    }
    public function callAPISuccessGetCount($entity, $params, $count = NULL) {
      $params += array(
        'debug' => 1,
      );
      $result = $this->civicrm_api3($entity, 'getcount', $params);
      if (!is_int($result) || !empty($result['is_error']) || isset($result['values'])) {
        throw new Exception('Invalid getcount result : ' . print_r($result, TRUE) . " type :" . gettype($result));
      }
      if (is_int($count)) {
        $this->assertEquals($count, $result, "incorrect count returned from $entity getcount");
      }
      return $result;
    }
    /**
    * Create contact.
    */
   function createContact() {
     if (!empty($this->_contactID)) {
       return;
     }
     $results = $this->callAPISuccess('Contact', 'create', array(
       'contact_type' => 'Individual',
       'first_name' => 'Jose',
       'last_name' => 'Lopez'
     ));;
     $this->_contactID = $results['id'];
   }
   /**
   * Create dummy contact.
   */
  function createDummyContact() {
    $results = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Adam' . substr(sha1(rand()), 0, 7),
      'last_name' => 'Cooper' . substr(sha1(rand()), 0, 7),
    ));
    return $results['id'];
  }

   /**
   * Create contribution.
   */
   function createContribution($params = array()) {
     if (empty($this->_contactID)) {
       $this->createContact();
     }
     $p = array_merge(array(
       'contact_id' => $this->_contactID,
       'receive_date' => '2010-01-20',
       'total_amount' => 100.00,
       'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
       'non_deductible_amount' => 10.00,
       'fee_amount' => 0.00,
       'net_amount' => 100.00,
       'trxn_id' => 'trxn_' . substr(sha1(rand()), 0, 7),
       'invoice_id' => 'inv_' . substr(sha1(rand()), 0, 7),
       'source' => 'SSF',
       'contribution_status_id' => 1,
     ), $params);
     return $this->callAPISuccess('contribution', 'create', $p);
   }

   /**
    * Enable Tax and Invoicing
    */
   protected function enableCreditNoteForStatus() {
     $contributionStatus = array(
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending refund'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Refunded'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled'),
    );

     return Civi::settings()->set('enable_credit_note_for_status', $contributionStatus);
   }
}
