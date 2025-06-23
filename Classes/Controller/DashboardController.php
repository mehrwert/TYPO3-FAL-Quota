<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Controller;

use Mehrwert\FalQuota\Utility\QuotaUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

#[AsController]
class DashboardController extends ActionController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly StorageRepository $storageRepository
    ) {}

    public function indexAction(): ResponseInterface
    {
        // TYPO3 admin user gets all storages
        if ($this->getBackendUser()->isAdmin() === true) {
            $storages = $this->storageRepository->findAll();
        } else {
            $storages = $this->getBackendUser()->getFileStorages();
        }
        $aggregatedStorages = [];

        if (!empty($storages)) {
            foreach ($storages as $storage) {
                $aggregatedStorages[$storage->getUid()] = QuotaUtility::getStorageDetails($storage);
            }
            asort($aggregatedStorages);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar
            ->makeShortcutButton()
            ->setRouteIdentifier('file_FalQuotaDashboard')
            ->setDisplayName('LLL:EXT:fal_quota/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab');
        $buttonBar->addButton($shortcutButton);

        $moduleTemplate->assign('storages', $aggregatedStorages);
        $moduleTemplate->assign(
            'localizationFile',
            'LLL:EXT:fal_quota/Resources/Private/Language/locallang_mod.xlf'
        );
        return $moduleTemplate->renderResponse();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
