.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

The extension groups all quota related fields in a tab :guilabel:`Quota settings` in the File Storage records.
Quota configuration is done per storage.

.. figure:: ../Images/ExampleStorage.png
   :class: with-shadow
   :alt: Example storage with Quota Settings tab

   Example storage with »Quota Settings« tab active

Enabling Quota for a Storage
============================

To enable Quotas for a storage, you must set up a quota [1], a hard limit [2] and optionally a threshold (defaults to 75 %)
[3] and email recipient(s). Multiple recipient addresses are separated by comma (,) [4].

.. figure:: ../Images/ExampleQuotaSettings.png
   :class: with-shadow
   :alt: Example quota configuration for storage

   Example quota configuration for storage with 10 MB quota, hard limit of 15 MB and a warning threshold of 75% (of the
   10 MB quota, sending notifications after reaching 7.5 MB).

The example above defines a soft quota of 10 MB [1] and a hard limit of 15 MB [2]. Hard limit values must be equal to or
greater than the soft quota values. The notification threshold value [3] defines the limit where the utilization check starts
sending email notifications (if recipients have been specified and the Scheduler task has been configured.
See :ref:`configuration` for details).

Additional recipients can optionally provided by a PSR event listener.

Example Configuration/Services.yaml:

.. code-block:: yaml

   services:
     MyVendor\MyProject\EventListener\AddAdditionalRecipients:
       public: true
       tags:
         - name: event.listener
           identifier: 'MyVendor-MyProject-AddAdditionalRecipients'
           event: MyVendor\MyProject\Event\AddAdditionalRecipientsEvent

Example Classes/EventListener/AddAdditionalRecipients.php:

.. code-block:: php

   <?php

   namespace MyVendor\MyProject\EventListener;

   use Mehrwert\FalQuota\Event\AddAdditionalRecipientsEvent;

   readonly class AddAdditionalRecipients
   {
       private const array ADDITIONAL_RECIPIENTS = [
           1 => [
             'person-for-storage-1@example.org',
           ],
           2 => [
             'person-for-storage-2@example.org',
             'another-person-for-storage-2@example.org',
           ],
       ];

       public function __invoke(AddAdditionalRecipientsEvent $event): void
       {
           $storageId = $event->getStorage()->getUid();
           $event->setAdditionalRecipients(
               self::ADDITIONAL_RECIPIENTS[$storageId] ?? []
           );
       }
   }
