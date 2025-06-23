<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Updates;

use Doctrine\DBAL\Exception as DbalException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

class CommandIdentifierUpdateWizard implements UpgradeWizardInterface
{
    private string $table = 'tx_scheduler_task';

    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {}

    public function getIdentifier(): string
    {
        return 'falQuota_commandIdentifierUpdate';
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'Update command identifier in scheduler tasks';
    }

    #[\Override]
    public function getDescription(): string
    {
        return '"QuotaCommand" has been renamed and method "QuotaCommand::updateStorageUsage()" has been extracted to'
            . ' standalone "UpdateCommand" in order to separate the update of storage usage statistics from'
            . ' email notifications. This update takes care of scheduler tasks and adjusts the command identifier'
            . ' accordingly';
    }

    /**
     * @throws DbalException
     */
    #[\Override]
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
            ->executeQuery();

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

    /**
     * @throws DbalException
     */
    #[\Override]
    public function updateNecessary(): bool
    {
        $updateNeeded = false;
        if ($this->checkIfWizardIsRequired()) {
            $updateNeeded = true;
        }

        return $updateNeeded;
    }

    #[\Override]
    public function getPrerequisites(): array
    {
        return [];
    }

    private function getTable(): string
    {
        return $this->table;
    }

    /**
     * @throws DbalException
     */
    private function checkIfWizardIsRequired(): int
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
            ->executeQuery()
            ->fetchOne();
    }

    private function getConnection(): Connection
    {
        return $this->connectionPool->getConnectionFortable($this->getTable());
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable($this->getTable());
    }
}
