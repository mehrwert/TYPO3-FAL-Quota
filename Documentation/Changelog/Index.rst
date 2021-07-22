.. include:: ../Includes.txt


.. _changelog:

=========
Changelog
=========

The following is an overview of the changes in this extension. For more details `read the online log <https://github.com/mehrwert/TYPO3-FAL-Quota>`_.

.. t3-field-list-table::
 :header-rows: 1

 - :Version:
      Version
   :Date:
      Release Date
   :Changes:
      Release Description

 - :Version:
      1.6.0
   :Date:
      2021-07-22
   :Changes:
      * Use localization for notification emails
      * Provide DDEV environment
      * Ensure TYPO3 10 compatibility of DatamapDataHandlerHook
      * Provide signal to extend warning email recipient list
      * Add extension-key to composer.json

 - :Version:
      1.5.2
   :Date:
      2021-01-15
   :Changes:
      Resolve a logical flaw in email notification in QuotaCommand::checkThreshold()

 - :Version:
      1.5.1
   :Date:
      2020-07-09
   :Changes:
      Add missing Slot postFolderRename()

 - :Version:
      1.5.0
   :Date:
      2020-06-07
   :Changes:
      [!!!] Make FAL Quota module available in FILE section and allow access for users and groups

 - :Version:
      1.4.0
   :Date:
      2020-05-30
   :Changes:
      Check if a quota has been set when calculating size after copy command

 - :Version:
      1.3.1
   :Date:
      2020-04-03
   :Changes:
      Remove duplicate 'MB' from FlashMessage texts and resolve improper quota evaluation

 - :Version:
      1.3.0
   :Date:
      2020-03-15
   :Changes:
      Compatibility with TYPO3 CMS v10.4

 - :Version:
      1.2.3
   :Date:
      2020-02-21
   :Changes:
      Use proper TCEmain log parameters and extract logging into a separate method

 - :Version:
      1.2.2
   :Date:
      2020-01-22
   :Changes:
      Use raw quota values for quota calculation

 - :Version:
      1.2.1
   :Date:
      2020-01-21
   :Changes:
      Do not use flash messages in CLI mode

 - :Version:
      1.2.0
   :Date:
      2020-01-02
   :Changes:
      Add quota utilization with pie charts in storage list view

 - :Version:
      1.1.0
   :Date:
      2019-12-29
   :Changes:
      Compatibility with TYPO3 CMS v10

 - :Version:
      1.0.0
   :Date:
      2019-12-27
   :Changes:
      Initial release of the FAL Quota extension
