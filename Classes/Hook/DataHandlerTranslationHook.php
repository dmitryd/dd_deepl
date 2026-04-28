<?php

namespace Dmitryd\DdDeepl\Hook;

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

use Dmitryd\DdDeepl\Localization\DeepLLocalizationScope;
use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a hook to translate records using DeepL
 * when translation happens via DataHandler inside a DeepL localization scope.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class DataHandlerTranslationHook implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Creates the instance of this class.
     */
    public function __construct(protected DeepLLocalizationScope $deepLLocalizationScope)
    {
    }

    /**
     * Translated records via DeepL.
     *
     * @param string $status
     * @param string $tableName
     * @param $recordId
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_postProcessFieldArray(string $status, string $tableName, $recordId, array &$fieldArray, DataHandler $dataHandler): void
    {
        if (isset($fieldArray['pid']) && $this->deepLLocalizationScope->isActive()) {
            $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? false;
            if ($languageField) {
                try {
                    $pidField = 'pid';
                    if ((int)$fieldArray['pid'] === 0) {
                        $pidField = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] ?? '';
                        if (!isset($fieldArray[$pidField])) {
                            $this->logger?->error(
                                sprintf(
                                    'Unable to determine pid for the record from "%s" new new id "%s"',
                                    $tableName,
                                    $recordId
                                )
                            );
                            return;
                        }
                    }
                    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($fieldArray[$pidField]);
                    $service = GeneralUtility::makeInstance(DeeplTranslationService::class);
                    $service->setDeepLLocalizationScope($this->deepLLocalizationScope);
                    $service->setSite($site);
                    if ($service->isAvailable()) {
                        $targetLanguage = $site->getLanguageById($fieldArray[$languageField]);
                        $translationSourceField = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'];
                        $sourceRecord = BackendUtility::getRecord($tableName, $fieldArray[$translationSourceField]);
                        // TODO: investigate when/why this happens - possibly free mode?
                        if ($sourceRecord) {
                            $translatedFieldArray = $service->translateRecord($tableName, $sourceRecord, $targetLanguage);
                            ArrayUtility::mergeRecursiveWithOverrule($fieldArray, $translatedFieldArray);
                        }
                    }
                } catch (SiteNotFoundException) {
                    // Nothing to do, record is outside of sites
                } catch (\InvalidArgumentException) {
                    // Nothing to do - language does not exist on the site but the record has it
                } catch (\Throwable $exception) {
                    $logMessage = sprintf(
                        'Unable to translate record %1$s#%2$s using DeepL. Error: %3$s',
                        $tableName,
                        $recordId,
                        $exception->getMessage()
                    );
                    $userMessage = sprintf(
                        'DeepL could not translate record "%1$s#%2$s". The record was created, but some fields may contain original text. Please check the TYPO3 log for details.',
                        $tableName,
                        $recordId
                    );
                    $this->deepLLocalizationScope->addError($userMessage);
                    $dataHandler->log($tableName, $recordId, 2, 0, 1, $logMessage);
                    $this->logger?->error(
                        sprintf(
                            'Unable to translate %s#%s. Message: \'%s\'. Stack: %s',
                            $tableName,
                            $recordId,
                            $exception->getMessage(),
                            $exception->getTraceAsString()
                        )
                    );
                }
            }
        }
    }
}
