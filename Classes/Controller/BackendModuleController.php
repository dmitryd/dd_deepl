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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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
        $this->pageUid = (int)GeneralUtility::_GET('id');
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
        $this->view->assignMultiple([
            'glossaries' => $service->listGlossaries(),
            'id' => $this->pageUid,
        ]);
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Shows the prompt to select a page id.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function noPageIdAction(): ResponseInterface
    {
        return $this->htmlResponse($this->view->render('noPageId'));
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

        if (!$service->isAvailable()) {
            $this->view->assignMultiple([
                'isConfigured' => $configuration->isConfigured(),
                'isAvailable' => $service->isAvailable(),
            ]);
        } else {
            $usage = $service->getUsage();
            $this->view->assignMultiple([
                'apiKey' => substr($configuration->getApiKey(), 0, 5) . '...' . substr($configuration->getApiKey(), -5),
                'apiUrl' => $configuration->getApiUrl(),
                'isConfigured' => $configuration->isConfigured(),
                'isAvailable' => $service->isAvailable(),
                'usage' => [
                    'count' => $usage->character->count,
                    'limit' => $usage->character->limit,
                    'percent' => 100*$usage->character->count/$usage->character->limit,
                ],
                'glossaryCount' => count($service->listGlossaries()),
            ]);
        }
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
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
            $this->view->assignMultiple([
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
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
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

        if ($this->request->getControllerActionName() === 'viewGlossary') {
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
                    'id' => $GLOBALS['TYPO3_REQUEST']->getQueryParams()['id'] ?? 0,
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
                ['action' => 'glossary', 'label' => 'glossary', 'test' => '/glossary/i'],
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
    }

    /** @inheritDoc */
    protected function resolveActionMethodName()
    {
        return $this->pageUid === 0 ? 'noPageIdAction' : parent::resolveActionMethodName();
    }
}
