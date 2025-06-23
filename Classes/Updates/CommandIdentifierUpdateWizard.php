<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

class CommandIdentifierUpdateWizard implements UpgradeWizardInterface
{
    private $table = 'tx_scheduler_task';

    public function getIdentifier(): string
    {
        return 'falQuota_commandIdentifierUpdate';
    }

    public function getTitle(): string
    {
        return 'Update command identifier in scheduler tasks';
    }

    public function getDescription(): string
    {
        return '"QuotaCommand" has been renamed and method "QuotaCommand::updateStorageUsage()" has been extracted to'
            . ' standalone "UpdateCommand" in order to separate the update of storage usage statistics from'
            . ' email notifications. This update takes care of scheduler tasks and adjusts the command identifier'
            . ' accordingly';
    }

    public function executeUpdate(): bool
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select('*')
            ->from($this->getTable())
            ->where(
                $queryBuilder->expr()->like(
                    'serialized_task_object',
                    $queryBuilder->createNamedParameter(
                        '%"fal_quota:quota:update"%'
                    )
                )
            )
            ->execute();

        while ($task = $statement->fetchAssociative()) {
            $recordId = (int)$task['uid'];
            $serializedTaskObject = $task['serialized_task_object'];

            /** @var ExecuteSchedulableCommandTask $taskObject */
            $taskObject = unserialize(
                $serializedTaskObject,
                ['allowed_classes' => [ExecuteSchedulableCommandTask::class]]
            );
            $taskObject->setCommandIdentifier('fal_quota:quota:notify');

            $this->getConnection()->update(
                $this->getTable(),
                [
                    'serialized_task_object' => serialize($taskObject),
                ],
                [
                    'uid' => $recordId,
                ]
            );
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        $updateNeeded = false;
        if ($this->checkIfWizardIsRequired()) {
            $updateNeeded = true;
        }

        return $updateNeeded;
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    private function getTable(): string
    {
        return $this->table;
    }

    private function checkIfWizardIsRequired()
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->count('uid')
            ->from($this->getTable())
            ->where(
                $queryBuilder->expr()->like(
                    'serialized_task_object',
                    $queryBuilder->createNamedParameter(
                        '%"fal_quota:quota:update"%'
                    )
                )
            )
            ->execute()
            ->fetchOne();
    }

    private function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionFortable($this->getTable());
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->getTable());
    }
}
