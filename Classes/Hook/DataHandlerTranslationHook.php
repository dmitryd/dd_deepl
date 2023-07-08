<?php
namespace Dmitryd\DdDeepl\Hook;

use DeepL\DeepLException;
use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
    protected const DATA_NAME = 'TX_DD_DEEPL_DH_DATA';

    public function __construct()
    {
        if (!isset($GLOBALS[self::DATA_NAME])) {
            $GLOBALS[self::DATA_NAME] = [];
        }
    }

    /**
     * Hooks into the DataHandler to save data necessary to translating text.
     * We have to do it becase "processTranslateTo_copyAction" does not provide
     * useful parameters.
     *
     * @param string $command
     * @param string $tableName
     * @param int $recordId
     * @param int $languageId
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $caller
     * @param string $fieldName
     */
    public function processCmdmap_preProcess(string $command, string $tableName, $recordId, $languageId, DataHandler $dataHandler)
    {
        $data = new DataHandlerHookData();
        $data->setTranslationEnabled(false);

        if ($command === 'localize' || $command === 'copyToLanguage') {
            $record = BackendUtility::getRecord($tableName, $recordId);
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

        $GLOBALS[self::DATA_NAME][spl_object_hash($dataHandler)] = $data;
    }

    /**
     * Translates the value to a required language.
     *
     * @param $fieldValue
     * @param array $targetLanguage
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @param string $fielfdName
     * @throws \DeepL\DeepLException
     */
    public function processTranslateTo_copyAction(&$fieldValue, /** @noinspection PhpUnusedParameterInspection */ array $targetLanguage, DataHandler $dataHandler, string $fielfdName)
    {
        $data = $GLOBALS[self::DATA_NAME][spl_object_hash($dataHandler)] ?? new DataHandlerHookData();
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
                $dataHandler->errorLog[] = sprintf(
                    '[%d] %s',
                    $exception->getCode(),
                    $exception->getMessage()
                );
            }
        }
    }
}
