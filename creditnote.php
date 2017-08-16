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
  // Add batch list selector.
  if (in_array($formName, array(
    "CRM_Contribute_Form_Contribution",
    "CRM_Member_Form_Membership",
    "CRM_Event_Form_Participant",
    "CRM_Contribute_Form_AdditionalPayment"
  ))) {
    if ($form->_mode) {
      return FALSE;
    }
    if ($form->_flagSubmitted) {
      $creditNote = CRM_Utils_Array::value('credit_note_id', $form->_submitValues);
      if ($creditNote) {
        $form->assign('creditNote', $creditNote);
      }
    }
  }
  if ($formName == 'CRM_Financial_Form_Payment' && !empty($form->paymentInstrumentID)) {
    if (CRM_Utils_Array::value('financial_trxn_id', $form->_values)) {
      return NULL;
    }
    $paymentInstrumentName = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $form->paymentInstrumentID);
    if ('Credit Note' == substr($paymentInstrumentName, 0, 11)) {
      $form->assign('paymentFields', array('credit_note_contact_id', 'credit_note_id'));

      // assign payment fields of Credit Note
      $form->addEntityRef('credit_note_contact_id', ts('Credit Note Contact'), array('api' => array('extra' => array('email'))));
      $form->add('select', 'credit_note_id',
        ts('Credit Note Amount'),
        CRM_CreditNote_BAO_CreditNote::getCreditNotes(),
        TRUE, array('class' => 'crm-select2', 'multiple' => 'true', 'placeholder' => ts('- any -'))
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

/**
 * Fetch payment instrument 'Credit Note'
 */
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
 */
function creditnote_civicrm_preProcess($formName, &$form) {
  if ($formName == 'CRM_Admin_Form_Preferences_Contribute') {
    $settings = $form->getVar('_settings');
    $contributeSettings = array();
    foreach ($settings as $key => $setting) {
      $contributeSettings[$key] = $setting;
      if ($key == 'acl_financial_type') {
        $contributeSettings['enable_credit_note_for_status'] = CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME;
      }
    }
    $form->setVar('_settings', $contributeSettings);
  }
}

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
    "CRM_Contribute_Form_AdditionalPayment",
  ))) {
    if (in_array($formName, array(
        "CRM_Member_Form_Membership",
        "CRM_Event_Form_Participant",
      ))
      && !CRM_Utils_Array::value('record_contribution', $fields)
    ) {
      return FALSE;
    }
    $paymentInstrument = CRM_Utils_Array::value('payment_instrument_id', $fields);
    if ($paymentInstrument) {
      $paymentInstrumentName = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $paymentInstrument);
      if ('Credit Note' == substr($paymentInstrumentName, 0, 11) && empty($fields['credit_note_id'])) {
        $errors['_qf_default'] = ts("Please select Credit Note Amount.");
      }
    }
    $creditNote = CRM_Utils_Array::value('credit_note_id', $fields);
    if ($creditNote) {
      $creditNoteAmount = CRM_CreditNote_BAO_CreditNote::getCreditNoteAmount($creditNote);
      $totalAmount = CRM_Utils_Array::value('total_amount', $fields);
      if ('CRM_Contribute_Form_AdditionalPayment' == $formName) {
        if ($totalAmount > $creditNoteAmount) {
          $errors['total_amount'] = ts("Total amount cannot be more than sum of selected credit note amount.");
        }
        return NULL;
      }
      $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $fields['contribution_status_id']);
      if ($creditNoteAmount >= $totalAmount && $contributionStatus != 'Completed') {
        $errors['contribution_status_id'] = ts('Contribution status should be completed because credit note amount is greater than total amount.');
      }
    }
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 *
 */
function creditnote_civicrm_postProcess($formName, &$form) {
  // Backoffice forms.
  if (in_array($formName, array(
    "CRM_Contribute_Form_Contribution",
    "CRM_Member_Form_Membership",
    "CRM_Event_Form_Participant",
    "CRM_Contribute_Form_AdditionalPayment"
  ))) {
    $form->assign('creditNote', NULL);
    $submitValues = $form->_submitValues;
    CRM_CreditNote_BAO_CreditNote::processCreditNote($submitValues);
  }
  // Component settings form.
  if ($formName == 'CRM_Admin_Form_Preferences_Contribute') {
    // Save the individual settings.
    $params = $form->_submitValues;
    $easyBatchParams = array(
      'enable_credit_note_for_status',
    );
    foreach ($easyBatchParams as $field) {
      Civi::settings()->set($field, CRM_Utils_Array::value($field, $params, 0));
    }
  }
}

/**
 * Implements hook_civicrm_postSave_table_name().
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_postSave_table_name/
 *
 */
function creditnote_civicrm_postSave_civicrm_financial_trxn($dao) {
  if ($dao->is_payment) {
    $creditNote = CRM_Core_Smarty::singleton()->get_template_vars('creditNote');
    if ($creditNote) {
      CRM_CreditNote_BAO_CreditNote::createCreditNotePayment(
        $creditNote,
	$dao->id,
	$dao->total_amount
      );
      CRM_Core_Smarty::singleton()->assign('creditNote', NULL);
    }
  }
}

/**
 * Implements hook_civicrm_postSave_table_name().
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_postSave_table_name/
 *
 */
function creditnote_civicrm_postSave_civicrm_contribution($dao) {
  if ($dao->contribution_status_id && !$dao->creditnote_id) {
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $dao->contribution_status_id);
    if ($contributionStatus == 'Pending refund') {
      $creditNote = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
        $dao->id, 'creditnote_id'
      );
      if (!$creditNote) {
        $dao->creditnote_id = CRM_Contribute_BAO_Contribution::createCreditNoteId();
	$dao->save();
      }
    }
  }
}

/**
 * Implements hook_civicrm_pre().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pre
 *
 */
function creditnote_civicrm_pre($op, $objectName, &$objectId, &$params) {
  if ($objectName == 'Contribution' && $op == 'create') {
    $creditNote = CRM_Core_Smarty::singleton()->get_template_vars('creditNote');
    if (!$creditNote && !empty($params['credit_note_id'])) {
      $creditNote = $params['credit_note_id'];
      CRM_Core_Smarty::singleton()->assign('creditNote', $creditNote);
    }
    if ($creditNote) {
      $creditNoteAmount = CRM_CreditNote_BAO_CreditNote::getCreditNoteAmount($creditNote);
      if ($creditNoteAmount > 0 && $creditNoteAmount < $params['total_amount']) {
        $params['partial_payment_total'] = $params['total_amount'];
        $params['partial_amount_to_pay'] = $creditNoteAmount;
        $params['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Partially paid');
      }
    }
  }
}

/**
 * Implements hook_civicrm_alterSettingsMetaData().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsMetaData
 *
 */
function creditnote_civicrm_alterSettingsMetaData(&$settingsMetadata, $domainID, $profile) {
  $contributionStatus = array(
    CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending refund'),
    CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Refunded'),
    CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled'),
  );
  $contributionStatusLabels = CRM_Core_PseudoConstant::get('CRM_Contribute_BAO_Contribution', 'contribution_status_id');
  $settingsMetadata['enable_credit_note_for_status'] = array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'enable_credit_note_for_status',
    'type' => 'Integer',
    'html_type' => 'select',
    'quick_form_type' => 'Element',
    'default' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending refund'),
    'add' => '4.7',
    'html_attributes' => array(
      'multiple' => 1,
      'class' => 'crm-select2',
    ),
    'title' => 'Enable credit note for contribution status?',
    'option_values' => array_intersect_key($contributionStatusLabels, array_flip($contributionStatus)),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => '',
  );
}