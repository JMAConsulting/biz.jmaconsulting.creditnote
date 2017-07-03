<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_CreditNote_Form_Add extends CRM_Core_Form {

  protected $_contributionID;

  /**
   * Pre-process
   */
  public function preProcess() {
    $this->_contributionID = CRM_Utils_Request::retrieve('contribution_id', 'Positive', $this, TRUE);

    if (CRM_CreditNote_BAO_CreditNote::checkIdCreditNoteCreated($this->_contributionID)) {
      CRM_Core_Error::statusBounce(ts('Credit Note is already created for this contribution.'), NULL, ts('Sorry'));
    }
  }

  public function buildQuickForm() {
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Yes'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('No'),
      ),
    ));
  }

  public function postProcess() {
    CRM_CreditNote_BAO_CreditNote::createCreditNote($this->_contributionID);
    return;
  }

}
