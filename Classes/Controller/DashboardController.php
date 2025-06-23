<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Controller;

use Mehrwert\FalQuota\Utility\QuotaUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class DashboardController extends ActionController
{
    private QuotaUtility $quotaUtility;

    private ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(QuotaUtility $quotaUtility, ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->quotaUtility = $quotaUtility;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function indexAction(): ResponseInterface
    {
        // TYPO3 admin user gets all storages
        if ($this->getBackendUser()->isAdmin() === true) {
            $storages = GeneralUtility::makeInstance(StorageRepository::class)->findAll();
        } else {
            $storages = $this->getBackendUser()->getFileStorages();
        }
        $aggregatedStorages = [];

        if (!empty($storages)) {
            foreach ($storages as $storage) {
                $storageUid = $storage->getUid();
                $aggregatedStorages[$storageUid] = $this->quotaUtility->getStorageDetails($storageUid);
            }
            asort($aggregatedStorages);
        }
        $this->view->assign('storages', $aggregatedStorages);
        $this->view->assign(
            'localizationFile',
            'LLL:EXT:fal_quota/Resources/Private/Language/locallang_mod.xlf'
        );

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar
            ->makeShortcutButton()
            ->setRouteIdentifier('file_FalQuotaFalquota');
        $buttonBar->addButton($shortcutButton);

        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
