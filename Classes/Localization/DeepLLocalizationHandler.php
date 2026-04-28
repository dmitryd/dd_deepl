<?php

namespace Dmitryd\DdDeepl\Localization;

/***************************************************************
*  Copyright notice
*
*  (c) 2026 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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

use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerInterface;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Backend\Localization\LocalizationResult;
use TYPO3\CMS\Backend\Localization\ManualLocalizationHandler;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * This class contains a localization handler that translates records with DeepL.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class DeepLLocalizationHandler implements LocalizationHandlerInterface
{
    /**
     * Creates the instance of this class.
     */
    public function __construct(
        protected ManualLocalizationHandler $manualLocalizationHandler,
        protected DeepLLocalizationScope $deepLLocalizationScope,
        protected DeeplTranslationService $deeplTranslationService,
        protected SiteFinder $siteFinder,
    ) {
    }

    /**
     * Fetches the handler identifier.
     *
     * @return string
     */
    #[\Override]
    public function getIdentifier(): string
    {
        return 'deepl';
    }

    /**
     * Fetches the translated handler label.
     *
     * @return string
     */
    #[\Override]
    public function getLabel(): string
    {
        return 'LLL:EXT:dd_deepl/Resources/Private/Language/locallang.xlf:TYPO3.lang.localize.wizard.button.deepl';
    }

    /**
     * Fetches the translated handler description.
     *
     * @return string
     */
    #[\Override]
    public function getDescription(): string
    {
        return 'LLL:EXT:dd_deepl/Resources/Private/Language/locallang.xlf:TYPO3.lang.localize.educate.deepl';
    }

    /**
     * Fetches the icon identifier for the handler.
     *
     * @return string
     */
    #[\Override]
    public function getIconIdentifier(): string
    {
        return 'actions-translate';
    }

    /**
     * Checks if DeepL can translate the requested localization.
     *
     * @param \TYPO3\CMS\Backend\Localization\LocalizationInstructions $instructions
     * @return bool
     */
    #[\Override]
    public function isAvailable(LocalizationInstructions $instructions): bool
    {
        $isAvailable = false;
        $pageId = $this->getPageId($instructions);
        if ($instructions->mode === LocalizationMode::TRANSLATE && $pageId > 0) {
            $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
            if ((bool)($pageTsConfig['mod.']['web_layout.']['localization.']['enableDeepL'] ?? true)) {
                try {
                    $site = $this->siteFinder->getSiteByPageId($pageId);
                    $this->deeplTranslationService->setSite($site);
                    $isAvailable = $this->deeplTranslationService->isAvailable();
                } catch (SiteNotFoundException) {
                    $isAvailable = false;
                }
            }
        }

        return $isAvailable;
    }

    /**
     * Processes localization through the core handler with the DeepL marker active.
     *
     * @param \TYPO3\CMS\Backend\Localization\LocalizationInstructions $instructions
     * @return \TYPO3\CMS\Backend\Localization\LocalizationResult
     */
    #[\Override]
    public function processLocalization(LocalizationInstructions $instructions): LocalizationResult
    {
        $result = $this->deepLLocalizationScope->run(
            fn () => $this->manualLocalizationHandler->processLocalization($instructions)
        );
        $errors = array_values(array_filter(
            array_map(
                static fn ($error): string => trim((string)$error),
                array_merge($result->errors, $this->deepLLocalizationScope->getErrors())
            ),
            static fn (string $error): bool => $error !== ''
        ));
        if (!$result->isSuccess() && $errors === []) {
            $errors[] = 'DeepL localization failed. Please check the TYPO3 log for details.';
        }
        if ($errors !== []) {
            $result = LocalizationResult::error($errors);
        }

        return $result;
    }

    /**
     * Fetches a page id for the localization instructions.
     *
     * @param \TYPO3\CMS\Backend\Localization\LocalizationInstructions $instructions
     * @return int
     */
    protected function getPageId(LocalizationInstructions $instructions): int
    {
        $pageId = $instructions->recordUid;
        if ($instructions->mainRecordType !== 'pages') {
            $record = BackendUtility::getRecord($instructions->mainRecordType, $instructions->recordUid);
            $pageId = (int)($record['pid'] ?? 0);
        }

        return $pageId;
    }
}
