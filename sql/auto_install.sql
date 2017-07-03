
CREATE TABLE `civicrm_creditnote_memo` (

   `id` INT unsigned NOT NULL AUTO_INCREMENT ,
   `contribution_id` INT unsigned NOT NULL  COMMENT 'FK to Contribution ID' ,
   `description` VARCHAR(255) COMMENT 'Credit note description' ,
   `amount` DECIMAL(20,2) NOT NULL COMMENT 'Total amount of this contribution. Use market value for non-monetary gifts.' ,
   `created_date` DATETIME NOT NULL COMMENT 'Credit Note created date' ,

   PRIMARY KEY (`id`),
   CONSTRAINT FK_civicrm_creditnote_memo_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE CASCADE

 ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;


 CREATE TABLE `civicrm_creditnote_memo_payment` (

    `id` INT unsigned NOT NULL AUTO_INCREMENT ,
    `creditnote_memo_id` INT unsigned NOT NULL COMMENT 'FK to CreditNote Memo ID' ,
    `financial_trxn_id` INT unsigned NOT NULL COMMENT 'FK to Financial Trxn ID',
    `amount` DECIMAL(20,2) NOT NULL COMMENT 'Total amount of this contribution. Use market value for non-monetary gifts.' ,

    PRIMARY KEY (`id`),
    CONSTRAINT FK_civicrm_creditnote_memo_payment_creditnote_memo_id FOREIGN KEY (`creditnote_memo_id`) REFERENCES `civicrm_creditnote_memo`(`id`) ON DELETE CASCADE ,
    CONSTRAINT FK_civicrm_creditnote_memo_payment_financial_trxn_id FOREIGN KEY (`financial_trxn_id`) REFERENCES `civicrm_financial_trxn`(`id`) ON DELETE CASCADE

  ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;
