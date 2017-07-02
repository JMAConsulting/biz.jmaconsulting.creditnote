<?php

require_once 'creditnote.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function creditnote_civicrm_config(&$config) {
  _creditnote_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function creditnote_civicrm_xmlMenu(&$files) {
  _creditnote_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function creditnote_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Financial_Form_Payment' && !empty($form->paymentInstrumentID)) {
    if (CRM_Utils_Array::value('financial_trxn_id', $form->_values)) {
      return NULL;
    }
    if ($form->paymentInstrumentID == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Note')) {
      $form->assign('paymentFields', array('credit_note_contact_id', 'credit_note_id'));

      // assign payment fields of Credit Note
      $form->addEntityRef('credit_note_contact_id', ts('Credit Note Contact'), array('api' => array('extra' => array('email'))));
      $form->add('select', 'credit_note_id',
        ts('Credit Note Amount'),
        CRM_CreditNote_BAO_CreditNote::getCreditNotes(),
        TRUE, array('class' => 'crm-select2', 'placeholder' => ts('- any -'))
      );
    }
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function creditnote_civicrm_install() {
  $result = civicrm_api3('OptionValue', 'Create', array(
    'option_group_id' => 'payment_instrument',
    'label' => 'Credit Note',
    'name' => 'Credit Note',
    'is_reserved' => 1,
    'filter' => 1,
  ));

  if (!empty($result['id'])) {
    $accountType = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name = 'Asset' ");
    $financialAccountID = array_search('Accounts Receivable', CRM_Contribute_PseudoConstant::financialAccount(NULL, key($accountType)));
    civicrm_api3('EntityFinancialAccount', 'create', array(
      'entity_table' => 'civicrm_option_value',
      'entity_id' => $result['id'],
      'account_relationship' => key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' ")),
      'financial_account_id' => $financialAccountID,
    ));
  }

  _creditnote_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function creditnote_civicrm_uninstall() {
  if ($id = _getCNPaymentInstrumentID()) {
    $entityFinancialAccountID = CRM_Utils_Array::value('id', civicrm_api3('EntityFinancialAccount', 'get', array(
      'entity_table' => 'civicrm_option_value',
      'entity_id' => $id,
    )));
    if ($entityFinancialAccountID) {
      civicrm_api3('EntityFinancialAccount', 'delete', array('id' => $entityFinancialAccountID));
    }
  }
  _creditnote_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function creditnote_civicrm_enable() {
  _creditnote_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function creditnote_civicrm_disable() {
  _creditnote_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function creditnote_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _creditnote_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function creditnote_civicrm_managed(&$entities) {
  if ($id = _getCNPaymentInstrumentID()) {
    $entities[] = array(
      'module' => 'biz.jmaconsulting.creditnote',
      'name' => 'CreditNote',
      'entity' => 'OptionValue',
      'params' => array(
        'version' => 3,
        'id' => $id,
      ),
    );
  }
  _creditnote_civix_civicrm_managed($entities);
}

function _getCNPaymentInstrumentID() {
  $result = civicrm_api3('OptionValue', 'Get', array(
    'option_group_id' => 'payment_instrument',
    'name' => 'Credit Note',
  ));
  return CRM_Utils_Array::value('id', $result);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function creditnote_civicrm_caseTypes(&$caseTypes) {
  _creditnote_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function creditnote_civicrm_angularModules(&$angularModules) {
_creditnote_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function creditnote_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _creditnote_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function creditnote_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function creditnote_civicrm_navigationMenu(&$menu) {
  _creditnote_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'biz.jmaconsulting.creditnote')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _creditnote_civix_navigationMenu($menu);
} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function creditnote_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if (in_array($formName, array(
    "CRM_Contribute_Form_Contribution",
    "CRM_Member_Form_Membership",
    "CRM_Event_Form_Participant",
  ))) {
    if (in_array($formName, array(
        "CRM_Member_Form_Membership",
        "CRM_Event_Form_Participant",
      ))
      && !CRM_Utils_Array::value('record_contribution', $fields)
    ) {
      return FALSE;
    }
    $creditNote = CRM_Utils_Array::value('credit_note_id', $fields);
    if ($creditNote) {
      $creditNoteAmount = CRM_CreditNote_BAO_CreditNote::getCreditNoteAmount($creditNote);
      $totalAmount = CRM_Utils_Array::value('credit_note_id', $fields);
      $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $fields['contribution_status_id']);
      if ($creditNoteAmount >= $totalAmount && $contributionStatus != 'Completed') {
        $errors['contribution_status_id'] = ts('Contribution status should be completed because credit note amount is greater than total amount');
      }
    }
  }
}

function creditnote_civicrm_links($op, $objectName, &$objectId, &$links, &$mask = NULL, &$values = array()) {;
  if ($op == 'contribution.selector.row' && $objectName == 'Contribution') {
    $contributionStatus = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $objectId, 'contribution_status_id');
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionStatus);
    if (in_array($contributionStatus, array('Refunded', 'Pending refund'))) {
      $links[] = array(
        'name' => ts('Create Credit Note'),
	'url' => '',
	'qs' => '',
	'title' => ts('Create Credit Note'),
	'ref' => " contribution-{$objectId}",
      );
    }
  }

}