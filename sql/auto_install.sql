
 CREATE TABLE `civicrm_creditnote_memo_payment` (

    `id` INT unsigned NOT NULL AUTO_INCREMENT ,
    `contribution_id` INT unsigned NOT NULL COMMENT 'FK to Contribution ID of credit note' ,
    `financial_trxn_id` INT unsigned NOT NULL COMMENT 'FK to Financial Trxn ID',
    `amount` DECIMAL(20,2) NOT NULL COMMENT 'Amount used from credit note.' ,

    PRIMARY KEY (`id`),
    CONSTRAINT FK_civicrm_creditnote_payment_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE CASCADE ,
    CONSTRAINT FK_civicrm_creditnote_payment_financial_trxn_id FOREIGN KEY (`financial_trxn_id`) REFERENCES `civicrm_financial_trxn`(`id`) ON DELETE CASCADE

  ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;
