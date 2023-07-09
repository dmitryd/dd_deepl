<?php

namespace Dmitryd\DdDeepl\Hook;

use DeepL\DeepLException;
use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a hook to translate records using DeepL
 * when translation happoens via DataHandler.
 *
 * @author Dmitry Dulepov <support@snowflake.ch>
 */
class DataHandlerTranslationHook
{
    /** @var DataHandlerHookData[] */
    protected static array $dataHandlerData = [];

    /**
     * Hooks into the DataHandler to save data necessary to translating text.
     * We have to do it becase "processTranslateTo_copyAction" does not provide
     * useful parameters.
     *
     * @param string $command
     * @param string $tableName
     * @param int $recordId
     * @param int $languageId
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processCmdmap_preProcess(string $command, string $tableName, mixed $recordId, mixed $languageId, DataHandler $dataHandler): void
    {
        $data = new DataHandlerHookData();
        $data->setTranslationEnabled(false);

        if ($command === 'localize' || $command === 'copyToLanguage') {
            $record = BackendUtility::getRecord($tableName, (int)$recordId);
            try {
                $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
                if ($languageField) {
                    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($record['pid']);
                    $data->setSourceLanguage($site->getLanguageById((int)$record[$languageField]));
                    $data->setTargetLanguage($site->getLanguageById((int)$languageId));
                    $data->setTableName($tableName);
                    $data->setRecord($record);
                    $data->setTranslationEnabled(true);
                }
            } catch (SiteNotFoundException $exception) {
                // Nothing to do, record is outside of sites
                return;
            } catch (\InvalidArgumentException $exception) {
                // Nothing to do - language does not exist
                return;
            }
        }

        self::$dataHandlerData[spl_object_hash($dataHandler)] = $data;
    }

    /**
     * Cleans up the datahandler data.
     *
     * @param string $command
     * @param string $table
     * @param mixed $id
     * @param mixed $value
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processCmdmap_postProcess(/** @noinspection PhpUnusedParameterInspection */string $command, /** @noinspection PhpUnusedParameterInspection */string $table, /** @noinspection PhpUnusedParameterInspection */mixed $id, /** @noinspection PhpUnusedParameterInspection */ mixed $value, DataHandler $dataHandler): void
    {
        if (isset(self::$dataHandlerData[spl_object_hash($dataHandler)])) {
            unset(self::$dataHandlerData[spl_object_hash($dataHandler)]);
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
}
