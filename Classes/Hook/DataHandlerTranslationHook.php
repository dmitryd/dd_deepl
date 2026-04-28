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

    /** @var array<string, bool> */
    protected array $recordsToDelete = [];

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
                            $errorCount = $this->deepLLocalizationScope->getTranslationFailureCount();
                            $translatedFieldArray = $service->translateRecord($tableName, $sourceRecord, $targetLanguage);
                            ArrayUtility::mergeRecursiveWithOverrule($fieldArray, $translatedFieldArray);
                            if ($status === 'new' && $tableName !== 'pages' && $this->deepLLocalizationScope->getTranslationFailureCount() > $errorCount) {
                                $this->markRecordForDeletion($tableName, (string)$recordId);
                            }
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
                    $this->deepLLocalizationScope->addGenericTranslationFailure();
                    if ($status === 'new' && $tableName !== 'pages') {
                        $this->markRecordForDeletion($tableName, (string)$recordId);
                    }
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

    /**
     * Deletes translated records that had DeepL translation errors.
     *
     * This keeps failed content records out of the localized page so editors
     * can retry translating them later without hunting for original text.
     *
     * @param string $status
     * @param string $tableName
     * @param string|int $recordId
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $tableName, $recordId, array $fieldArray, DataHandler $dataHandler): void
    {
        $recordKey = $this->getRecordKey($tableName, (string)$recordId);
        if ($status !== 'new' || !isset($this->recordsToDelete[$recordKey])) {
            return;
        }
        unset($this->recordsToDelete[$recordKey]);

        $realRecordId = (int)($dataHandler->substNEWwithIDs[$recordId] ?? 0);
        if ($realRecordId <= 0) {
            return;
        }
        $deleteField = (string)($GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? '');
        if ($deleteField === '') {
            $this->logger?->warning(
                sprintf(
                    'Unable to remove failed DeepL localization %s#%d because the table has no delete field.',
                    $tableName,
                    $realRecordId
                )
            );
            return;
        }

        $dataHandler->deleteAction($tableName, $realRecordId, true);

        $message = sprintf(
            'DeepL could not translate "%1$s#%2$d". The translated record was removed, and you can try translating it again later.',
            $tableName,
            $realRecordId
        );
        $dataHandler->log($tableName, $realRecordId, 2, 0, 1, $message);
        $this->logger?->error($message);
    }

    /**
     * Marks a new localized record for removal after it has a real uid.
     *
     * @param string $tableName
     * @param string $recordId
     */
    protected function markRecordForDeletion(string $tableName, string $recordId): void
    {
        $this->recordsToDelete[$this->getRecordKey($tableName, $recordId)] = true;
    }

    /**
     * Creates a stable key for tracking a DataHandler record id.
     *
     * @param string $tableName
     * @param string $recordId
     * @return string
     */
    protected function getRecordKey(string $tableName, string $recordId): string
    {
        return $tableName . ':' . $recordId;
    }
}
