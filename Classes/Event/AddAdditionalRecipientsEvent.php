<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Event;

use TYPO3\CMS\Core\Resource\ResourceStorage;

final class AddAdditionalRecipientsEvent
{
    public function __construct(
        /** @var string[] */
        private array $additionalRecipients,
        private readonly ResourceStorage $storage,
    ) {}

    /**
     * @return string[]
     */
    public function getAdditionalRecipients(): array
    {
        return $this->additionalRecipients;
    }

    /**
     * @param string[] $additionalRecipients
     */
    public function setAdditionalRecipients(array $additionalRecipients): void
    {
        $this->additionalRecipients = $additionalRecipients;
    }

    public function getStorage(): ResourceStorage
    {
        return $this->storage;
    }
}
