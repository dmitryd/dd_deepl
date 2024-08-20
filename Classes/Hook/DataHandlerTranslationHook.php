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

use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a hook to translate records using DeepL
 * when translation happens via DataHandler.  It uses a request,
 * so if you want to use DeepL service automatically
 * with DataHandler, you have to fake it like:
 *
 *   $originalRequest = $GLOBALS['TYPO3_REQUEST'];
 *   $queryParams = $request->getQueryParams();
 *   $queryParams['deepl'] = 1;
 *   $GLOBALS['TYPO3_REQUEST'] = $request->withQueryParams($queryParams);
 *   $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
 *   $dataHandler->start([], $commandMap);
 *   $dataHandler->process_cmdmap();
 *   $GLOBALS['TYPO3_REQUEST'] = $originalRequest;
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class DataHandlerTranslationHook
{
    /**
     * Creates the instance of the class.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(protected LoggerInterface $logger)
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
        if (($fieldArray['pid'] ?? false) && ($this->isDeeplRequest() || $this->isNewPageTranslation($tableName, $recordId, $fieldArray))) {
            $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? false;
            if ($languageField) {
                $service = GeneralUtility::makeInstance(DeeplTranslationService::class);
                if ($service->isAvailable()) {
                    try {
                        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($fieldArray['pid']);
                        $targetLanguage = $site->getLanguageById($fieldArray[$languageField]);
                        $translationSourceField = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'];
                        $sourceRecord = BackendUtility::getRecord($tableName, $fieldArray[$translationSourceField]);
                        // TODO: investigate when/why this happens
                        if ($sourceRecord) {
                            $translatedFieldArray = $service->translateRecord($tableName, $sourceRecord, $targetLanguage);
                            ArrayUtility::mergeRecursiveWithOverrule($fieldArray, $translatedFieldArray);
                        }
                    } catch (SiteNotFoundException) {
                        // Nothing to do, record is outside of sites
                    } catch (\InvalidArgumentException) {
                        // Nothing to do - language does not exist on the site but the record has it
                    } catch (\Exception $exception) {
                        $message = sprintf(
                            'Unable to translate record %1$s#%2$s using DeepL. Error: %3$s',
                            $tableName,
                            $recordId,
                            $exception->getMessage()
                        );
                        $dataHandler->log($tableName, $recordId, 2, 0, 1, $message);
                        $this->logger->error(
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

    /**
     * Checks if this is a DeepL request.
     *
     * @return bool
     */
    protected function isDeeplRequest(): bool
    {
        $request = $GLOBALS['TYPO3_REQUEST'];
        /** @var \TYPO3\CMS\Core\Http\ServerRequest $request */
        $queryParams = $request->getQueryParams() ?? [];
        $parsedBody = $request->getParsedBody() ?? [];

        return ($queryParams['deepl'] ?? false) || ($parsedBody['deepl'] ?? false);
    }

    /**
     * Checks if the user is creating a new translation of the page.
     *
     * @param string $tableName
     * @param mixed $recordId
     * @param array $fieldArray
     * @return bool
     */
    protected function isNewPageTranslation(string $tableName, $recordId, array $fieldArray): bool
    {
        return $tableName === 'pages' &&
            ($fieldArray['sys_language_uid'] ?? 0) > 0 &&
            str_starts_with((string)$recordId, 'NEW')
        ;
    }
}
