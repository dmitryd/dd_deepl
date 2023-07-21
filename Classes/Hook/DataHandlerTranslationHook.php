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

use DeepL\DeepLException;
use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a hook to translate records using DeepL
 * when translation happens via DataHandler.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class DataHandlerTranslationHook
{
    /** @var DataHandlerHookData[] */
    protected static array $dataHandlerData = [];

    /**
     * Processes our custom command and saves some data for the actual hook.
     * We have to do it because "processTranslateTo_copyAction" does not provide
     * useful parameters.
     *
     * @param string &$command
     * @param string $tableName
     * @param int $recordId
     * @param int $languageId
     * @param bool &$commandIsProcessed
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processCmdmap(string &$command, string $tableName, mixed $recordId, mixed $languageId, bool &$commandIsProcessed, DataHandler $dataHandler): void
    {
        if ($command === 'deepl') {
            $record = BackendUtility::getRecord($tableName, (int)$recordId);
            if (!empty($record)) {
                try {
                    $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? false;
                    if ($languageField) {
                        $objectHash = spl_object_hash($dataHandler);
                        self::$dataHandlerData[$objectHash] = $this->getDataHandlerHookData($record, $languageField, $languageId, $tableName);
                        $this->translateRecord($tableName, $recordId, $languageId, $dataHandler);
                        unset(self::$dataHandlerData[$objectHash]);
                        $commandIsProcessed = true;
                    }
                } catch (SiteNotFoundException) {
                    // Nothing to do, record is outside of sites
                    return;
                } catch (\InvalidArgumentException) {
                    // Nothing to do - language does not exist
                    return;
                }
            }
            if (!$commandIsProcessed) {
                // We could not do it, revert to the standard handling (most likely useless but there is always a chance)
                $command = 'localize';
            }
        }
    }

    /**
     * Translates the value to a required language.
     *
     * @param mixed $fieldValue
     * @param array $targetLanguage
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @param string $fielfdName
     */
    public function processTranslateTo_copyAction(mixed &$fieldValue, /** @noinspection PhpUnusedParameterInspection */ array $targetLanguage, DataHandler $dataHandler, string $fielfdName): void
    {
        $data = self::$dataHandlerData[spl_object_hash($dataHandler)] ?? new DataHandlerHookData();
        if ($data->isTranslationEnabled() && is_string($fieldValue)) {
            try {
                $fieldValue = GeneralUtility::makeInstance(DeeplTranslationService::class)->translateField(
                    $data->getTableName(),
                    $fielfdName,
                    $fieldValue,
                    $data->getSourceLanguage(),
                    $data->getTargetLanguage()
                );
            } catch (DeepLException $exception) {
                $error = sprintf(
                    'Error while translating with DeepL: [%d] %s. Stack: %s',
                    $exception->getCode(),
                    $exception->getMessage(),
                    $exception->getTraceAsString()
                );
                $dataHandler->log(
                    $data->getTableName(),
                    $data->getRecord()['uid'],
                    2,
                    $data->getRecord()['pid'],
                    1,
                    $error
                );
                $dataHandler->errorLog[] = $error;
            }
        }
    }

    /**
     * ${CARET}
     *
     * @param array|null $record
     * @param mixed $languageField
     * @param mixed $languageId
     * @param string $tableName
     * @return \Dmitryd\DdDeepl\Hook\DataHandlerHookData
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    protected function getDataHandlerHookData(?array $record, mixed $languageField, mixed $languageId, string $tableName): DataHandlerHookData
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($record['pid']);
        $data = new DataHandlerHookData();
        $data->setSourceLanguage($site->getLanguageById((int)$record[$languageField]));
        $data->setTargetLanguage($site->getLanguageById((int)$languageId));
        $data->setTableName($tableName);
        $data->setRecord($record);
        $data->setTranslationEnabled(true);

        return $data;
    }

    /**
     * Gets the protected $useTransOrigPointerField from the DataHandler, which we need to save to
     * successfully call $dataHandler->localize().
     *
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return bool
     */
    protected function getUseTransOrigPointerField(DataHandler $dataHandler): bool
    {
        $getter = function (): bool {
            /** @noinspection PhpUndefinedFieldInspection */
            return (bool)$this->useTransOrigPointerField;
        };
        return $getter->call($dataHandler);
    }

    /**
     * Sets the protected $useTransOrigPointerField from the DataHandler for $dataHandler->localize().
     *
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @param bool $useTransOrigPointerField
     */
    protected function setUseTransOrigPointerField(DataHandler $dataHandler, bool $useTransOrigPointerField): void
    {
        $setter = function (bool $useTransOrigPointerField): void {
            /** @noinspection PhpDynamicFieldDeclarationInspection */
            $this->useTransOrigPointerField = $useTransOrigPointerField;
        };
        $setter->call($dataHandler, $useTransOrigPointerField);
    }

    /**
     * Translates the record using DataHandler. DataHandler will call our hook for each field that needs to be
     * translated.
     *
     * @param string $tableName
     * @param mixed $recordId
     * @param mixed $languageId
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    protected function translateRecord(string $tableName, mixed $recordId, mixed $languageId, DataHandler $dataHandler): void
    {
        $originalUseTransOrigPointerField = $this->getUseTransOrigPointerField($dataHandler);
        $this->setUseTransOrigPointerField($dataHandler, true);
        $dataHandler->localize($tableName, $recordId, $languageId);
        $this->setUseTransOrigPointerField($dataHandler, $originalUseTransOrigPointerField);
    }
}
