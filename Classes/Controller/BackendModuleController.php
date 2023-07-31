<?php

namespace Dmitryd\DdDeepl\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2023 Dmitry Dulepov <dmitry.dulepov@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use DeepL\DeepLException;
use Dmitryd\DdDeepl\Configuration\Configuration;
use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class provides a backend module for DeepL extension.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class BackendModuleController extends ActionController
{
    protected ModuleTemplate $moduleTemplate;

    protected array $pageInformation = [];

    protected int $pageUid = 0;

    /**
     * Creates the instance of the class.
     */
    public function __construct()
    {
        $this->pageUid = (int)($GLOBALS['TYPO3_REQUEST']->getQueryParams()['id'] ?? 0);
        $this->pageInformation = BackendUtility::readPageAccess($this->pageUid, '');
    }

    /**
     * Downloads the glossary.
     *
     * @param string $glossaryId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \DeepL\DeepLException
     */
    public function downloadGlossaryAction(string $glossaryId): ResponseInterface
    {
        $service = GeneralUtility::makeInstance(DeeplTranslationService::class);

        try {
            $entries = $service->getGlossaryEntries($glossaryId);
        } catch (DeepLException $exception) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                '',
                LocalizationUtility::translate('module.error', 'dd_deepl', [$exception->getCode(), $exception->getMessage()]),
                AbstractMessage::ERROR,
                true
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);

            $this->redirect('glossary');
        }

        $response = GeneralUtility::makeInstance(Response::class);
        $stream = $response->getBody();
        foreach ($entries as $source => $target) {
            $stream->write(
                sprintf(
                    '"%s","%s"' . LF,
                    addslashes($source),
                    addslashes($target)
                )
            );
        }

        return $response->withHeader('Content-type', 'text/csv')
            ->withHeader('Content-disposition', 'attachment; filename="' . $glossaryId . '.csv"')
        ;
    }

    /**
     * Deletes the glossary.
     *
     * @param string $glossaryId
     * @throws \DeepL\DeepLException
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function deleteGlossaryAction(string $glossaryId): void
    {
        $service = GeneralUtility::makeInstance(DeeplTranslationService::class);
        $info = $service->getGlossary($glossaryId);
        try {
            $service->deleteGlossary($glossaryId);
        } catch (DeepLException) {
            // Igmore
        }

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            '',
            LocalizationUtility::translate('module.glossary.delete.done', 'dd_deepl', [$info->name, $glossaryId]),
            AbstractMessage::OK,
            true
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);

        $this->redirect('glossary');
    }

    /**
     * Manages glossaries.
     *
     * @param string $glossaryId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \DeepL\DeepLException
     */
    public function glossaryAction(string $glossaryId = ''): ResponseInterface
    {
        $service = GeneralUtility::makeInstance(DeeplTranslationService::class);
        $this->moduleTemplate->assignMultiple([
            'glossaries' => $service->listGlossaries(),
            'id' => $this->pageUid,
        ]);
        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Shows the prompt to select a page id.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function noPageIdAction(): ResponseInterface
    {
        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Shows the overview.
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \DeepL\DeepLException
     */
    public function overviewAction(): ResponseInterface
    {
        $configuration = GeneralUtility::makeInstance(Configuration::class);
        $service = GeneralUtility::makeInstance(DeeplTranslationService::class);

        if ($service->isAvailable()) {
            $usage = $service->getUsage();
            $this->moduleTemplate->assignMultiple([
                'apiKey' => substr($configuration->getApiKey(), 0, 5) . '...' . substr($configuration->getApiKey(), -5),
                'apiUrl' => $configuration->getApiUrl(),
                'usage' => [
                    'count' => $usage->character->count,
                    'limit' => $usage->character->limit,
                    'percent' => 100*$usage->character->count/$usage->character->limit,
                ],
                'glossaryCount' => count($service->listGlossaries()),
            ]);
        }
        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Shows the upload form.
     *
     * @return ResponseInterface
     */
    public function uploadFormAction(string $name = '', string $sourceLanguage = '', string $targetLanguage = ''): ResponseInterface
    {
        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->pageUid);
        } catch (SiteNotFoundException) {
            // TODO Handle this
        }
        $languages = [];
        foreach ($site->getAllLanguages() as $siteLanguage) {
            /** @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage $siteLanguage */
            $languages[$siteLanguage->getLocale()->getLanguageCode()] = $siteLanguage->getTitle();
        }
        $this->moduleTemplate->assignMultiple([
            'id' => $this->pageUid,
            'languages' => $languages,
            'name' => $name,
            'sourceLanguage' => $sourceLanguage,
            'targetLanguage' => $targetLanguage,
        ]);
        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Uploads the glossary.
     */
    public function uploadAction(string $name, string $sourceLanguage, string $targetLanguage)
    {
        if (count($_FILES) === 0) {
            $this->redirect('uploadForm');
        }

        $severity = ContextualFeedbackSeverity::ERROR;
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_NO_FILE:
                    $message = 'no_file';
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message = 'too_large';
                    break;
                default:
                    $message = 'unknown_error';
            }
        } elseif (!is_uploaded_file($_FILES['file']['tmp_name'])) {
            $message = 'unknown_error';
        } else {
            // TODO More validation: name, languages are valid and different
            $fileName = GeneralUtility::tempnam('', '.csv');
            move_uploaded_file($_FILES['file']['tmp_name'], $fileName);
            $service = GeneralUtility::makeInstance(DeeplTranslationService::class);
            $service->createGlossaryFromCsv($name, $sourceLanguage, $targetLanguage, file_get_contents($fileName));
            unlink($fileName);
            $severity = ContextualFeedbackSeverity::OK;
            $message = 'uploaded';
        }

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            '',
            LocalizationUtility::translate('module.upload.message.' . $message, 'dd_deepl'),
            $severity,
            true
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);

        if ($severity === ContextualFeedbackSeverity::ERROR) {
            $this->redirect(
                'uploadForm',
                null,
                null,
                [
                    'action' => 'uploadForm',
                    'name' => $name,
                    'sourceLanguage' => $sourceLanguage,
                    'targetLanguage' => $targetLanguage,
                ],
                $this->pageUid
            );
        } else {
            $this->redirect(
                'glossary',
                null,
                null,
                [],
                $this->pageUid
            );
        }
    }

    /**
     * Displays the information about a glossary.
     *
     * @param string $glossaryId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \DeepL\DeepLException
     */
    public function viewGlossaryAction(string $glossaryId): ResponseInterface
    {
        $service = GeneralUtility::makeInstance(DeeplTranslationService::class);
        try {
            $this->moduleTemplate->assignMultiple([
                'glossary' => $service->getGlossary($glossaryId),
                'entries' => $service->getGlossaryEntries($glossaryId),
            ]);
        } catch (DeepLException $exception) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                '',
                LocalizationUtility::translate('module.error', 'dd_deepl', [$exception->getCode(), $exception->getMessage()]),
                AbstractMessage::ERROR,
                true
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);

            $this->redirect('glossary');
        }
        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Creates buttons as necessary.
     */
    protected function createButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $buttons = [];

        switch ($this->request->getControllerActionName()) {
            case 'glossary':
                $buttons[] = [
                    'label' => 'module.button.upload',
                    'action' => 'uploadForm',
                    'icon' => 'actions-upload',
                    'arguments' => [],
                ];
                break;
            case 'uploadForm':
                $buttons[] = [
                    'label' => 'module.button.back',
                    'action' => 'glossary',
                    'icon' => 'actions-view-go-back',
                    'arguments' => [],
                ];
                break;
            case 'viewGlossary':
                $buttons[] = [
                    'label' => 'module.button.back',
                    'action' => 'glossary',
                    'icon' => 'actions-view-go-back',
                    'arguments' => [],
                ];
                $buttons[] = [
                    'label' => 'module.button.download',
                    'action' => 'downloadGlossary',
                    'icon' => 'actions-download',
                    'arguments' => ['glossaryId' => $_GET['tx_dddeepl_site_dddeepldddeepl']['glossaryId']],
                ];
                break;
        }

        foreach ($buttons as $configuration) {
            $title = LocalizationUtility::translate($configuration['label'], 'dd_deepl');
            $button = $buttonBar->makeLinkButton()
                ->setHref($uriBuilder->reset()->setRequest($this->request)->uriFor(
                    $configuration['action'],
                    $configuration['arguments']
                ))
                ->setDataAttributes([
                    'toggle' => 'tooltip',
                    'placement' => 'bottom',
                    'title' => $title])
                ->setTitle($title)
                ->setIcon($iconFactory->getIcon($configuration['icon'], Icon::SIZE_SMALL));
            $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }

        // Shortcut
        if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setRouteIdentifier('site_DdDeeplDdDeepl')
                ->setArguments([
                    'action' => $this->request->getControllerActionName(),
                    'id' => $this->pageUid,
                    'module' => $this->request->getPluginName(),
                ])
                ->setDisplayName('Shortcut');
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }

    /**
     * Creates the menu for the module.
     */
    protected function createMenu(): void
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $docHeaderComponent = $this->moduleTemplate->getDocHeaderComponent();
        $docHeaderComponent->setMetaInformation($this->pageInformation);

        $service = GeneralUtility::makeInstance(DeeplTranslationService::class);

        if ($this->pageUid > 0 && $service->isAvailable()) {
            // Show menu only if we have a page id
            $menu = $docHeaderComponent->getMenuRegistry()->makeMenu();
            $menu->setIdentifier('dd_deepl');
            $actions = [
                ['action' => 'overview', 'label' => 'overview', 'test' => '/^overview$/'],
                ['action' => 'glossary', 'label' => 'glossary', 'test' => '/glossary|upload/i'],
            ];
            foreach ($actions as $action) {
                $item = $menu->makeMenuItem()
                    ->setTitle(LocalizationUtility::translate('module.' . $action['label'], 'dd_deepl'))
                    ->setHref($uriBuilder->uriFor($action['action']))
                    ->setActive(preg_match($action['test'], $this->request->getControllerActionName()))
                ;
                $menu->addMenuItem($item);
            }
            $docHeaderComponent->getMenuRegistry()->addMenu($menu);
        }
    }

    /** @inheritDoc */
    protected function initializeAction()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplateFactory::class)->create($this->request);
        $this->createMenu();
        $this->createButtons();

        $service = GeneralUtility::makeInstance(DeeplTranslationService::class);
        $isAvailable = $service->isAvailable() && $this->pageUid > 0;
        $this->moduleTemplate->assignMultiple([
            'isConfigured' => $isAvailable,
            'isAvailable' => $isAvailable,
        ]);
    }

    /** @inheritDoc */
    protected function resolveActionMethodName()
    {
        return $this->pageUid === 0 ? 'noPageIdAction' : parent::resolveActionMethodName();
    }
}
