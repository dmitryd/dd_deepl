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
    /**
     * Processes our custom command and translates the record.
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
                $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? false;
                if ($languageField) {
                    $this->translateRecord($tableName, $record, $languageId, $dataHandler);
                    $commandIsProcessed = true;
                }
            }
            if (!$commandIsProcessed) {
                // We could not do it, revert to the standard handling (most likely useless but there is always a chance)
                $command = 'localize';
            }
        }
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
     * Translates fields of the record localized by TYPO3.
     *
     * @param string $tableName
     * @param array $record
     * @param mixed $languageId
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    protected function translateLocalizedRecordFields(string $tableName, array $record, mixed $languageId, DataHandler $dataHandler): void
    {
        list($translation) = BackendUtility::getRecordLocalization($tableName, $record['uid'], $languageId);
        if ($translation) {
            try {
                $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($record['pid']);
                $targetLanguage = $site->getLanguageById($languageId);
            } catch (SiteNotFoundException) {
                // Nothing to do, record is outside of sites
                return;
            } catch (\InvalidArgumentException) {
                // Nothing to do - language does not exist on the site but the record has it
                return;
            }
            try {
                $data = [
                    $tableName => [
                        $translation['uid'] => GeneralUtility::makeInstance(DeeplTranslationService::class)->translateRecord($tableName, $record, $targetLanguage),
                    ]
                ];
                $localDataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $localDataHandler->start($data, [], $dataHandler->BE_USER);
                $localDataHandler->process_datamap();
            } catch (DeepLException) {
                // TODO Logging here about failure reasons
            }
        }
    }

    /**
     * Translates the record using DataHandler. DataHandler will call our hook for each field that needs to be
     * translated.
     *
     * @param string $tableName
     * @param array $record
     * @param mixed $languageId
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    protected function translateRecord(string $tableName, array $record, mixed $languageId, DataHandler $dataHandler): void
    {
        $originalUseTransOrigPointerField = $this->getUseTransOrigPointerField($dataHandler);
        $this->setUseTransOrigPointerField($dataHandler, true);
        $dataHandler->localize($tableName, $record['uid'], $languageId);
        $this->setUseTransOrigPointerField($dataHandler, $originalUseTransOrigPointerField);

        $this->translateLocalizedRecordFields($tableName, $record, $languageId, $dataHandler);
    }
}
