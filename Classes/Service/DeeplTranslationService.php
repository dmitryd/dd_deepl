<?php

namespace Dmitryd\DdDeepl\Service;

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
use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use DeepL\GlossaryLanguagePair;
use DeepL\Language;
use DeepL\LanguageCode;
use DeepL\TranslateTextOptions;
use DeepL\Translator;
use DeepL\TranslatorOptions;
use DeepL\Usage;
use Dmitryd\DdDeepl\Configuration\Configuration;
use Dmitryd\DdDeepl\Event\AfterFieldTranslatedEvent;
use Dmitryd\DdDeepl\Event\AfterRecordTranslatedEvent;
use Dmitryd\DdDeepl\Event\BeforeFieldTranslationEvent;
use Dmitryd\DdDeepl\Event\BeforeRecordTranslationEvent;
use Dmitryd\DdDeepl\Event\CanFieldBeTranslatedCheckEvent;
use Dmitryd\DdDeepl\Event\PreprocessFieldValueEvent;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a service to translate records and texts in TYPO3.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class DeeplTranslationService implements SingletonInterface
{
    use LoggerAwareTrait;

    protected Configuration $configuration;

    protected EventDispatcher $eventDispatcher;

    /** @var \DeepL\Language[] */
    protected array $sourceLanguages = [];

    /** @var \DeepL\Language[] */
    protected array $targetLanguages = [];

    protected ?Translator $translator = null;
    
    protected bool $autoDetectSourceLang = false;

    /**
     * Creates the instance of the class.
     *
     * @param array $deeplOptions
     * @throws \DeepL\DeepLException
     */
    public function __construct(array $deeplOptions = [])
    {
        $this->configuration = GeneralUtility::makeInstance(Configuration::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);

        $this->setLogger(GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__));

        if (Environment::isComposerMode()) {
            $deeplOptions = array_merge(
                [
                    TranslatorOptions::PROXY => $this->getProxySettings(),
                    TranslatorOptions::SERVER_URL => $this->configuration->getApiUrl(),
                    TranslatorOptions::TIMEOUT => 10,
                ],
                $deeplOptions
            );
            $apiKey = $this->configuration->getApiKey();
            if ($apiKey) {
                $this->translator = new Translator($apiKey, $deeplOptions);
                try {
                    $this->sourceLanguages = $this->translator->getSourceLanguages();
                    $this->targetLanguages = $this->translator->getTargetLanguages();
                } catch (\Exception $exception) {
                    $this->logger->error(
                        sprintf(
                            'Exception %s while fetching DeepL languages. Code %d, message "%s". Stack: %s',
                            get_class($exception),
                            $exception->getCode(),
                            $exception->getMessage(),
                            $exception->getTraceAsString()
                        )
                    );
                    $this->translator = null;
                }
            }
        } else {
            $message = $GLOBALS['LANG']->sL('LLL:EXT:dd_deepl/Resources/Private/Language/locallang.xlf:not_composer');

            $this->logger->critical($message);

            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                '',
                $message,
                ContextualFeedbackSeverity::ERROR,
                true
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
    }

    /**
     * Creates a new glossary on DeepL server with given name, languages, and entries
     *
     * @param string $name User-defined name to assign to the glossary.
     * @param string $sourceLanguageIsoCode Language code of the glossary source terms
     * @param string $targetLanguageIsoCode Language code of the glossary target terms
     * @param GlossaryEntries $entries The source- & target-term pairs to add to the glossary
     * @return GlossaryInfo Details about the created glossary.
     * @throws DeepLException
     * @internal
     */
    public function createGlossary(string $name, string $sourceLanguageIsoCode, string $targetLanguageIsoCode, GlossaryEntries $entries): GlossaryInfo
    {
        return $this->translator->createGlossary($name, $sourceLanguageIsoCode, $targetLanguageIsoCode, $entries);
    }

    /**
     * Creates a new glossary on DeepL server with given name, languages, and entries.
     *
     * @param string $name User-defined name to assign to the glossary
     * @param string $sourceLanguageIsoCode Language code of the glossary source terms
     * @param string $targetLanguageIsoCode Language code of the glossary target terms
     * @param string $csvContent String containing CSV content
     * @return GlossaryInfo
     * @throws DeepLException
     * @internal
     */
    public function createGlossaryFromCsv(string $name, string $sourceLanguageIsoCode, string $targetLanguageIsoCode, string $csvContent): GlossaryInfo
    {
        return $this->translator->createGlossaryFromCsv($name, $sourceLanguageIsoCode, $targetLanguageIsoCode, $csvContent);
    }

    /**
     * Deletes the glossary by id.
     *
     * @param string $glossaryId
     * @throws \DeepL\DeepLException
     * @internal
     */
    public function deleteGlossary(string $glossaryId)
    {
        $this->translator->deleteGlossary($glossaryId);
    }

    /**
     * Gets information about an existing glossary
     *
     * @param string $glossaryId Glossary ID of the glossary
     * @return GlossaryInfo GlossaryInfo containing details about the glossary
     * @throws DeepLException
     * @internal
     */
    public function getGlossary(string $glossaryId): GlossaryInfo
    {
        return $this->translator->getGlossary($glossaryId);
    }

    /**
     * Retrieves the entries stored with the glossary with the given glossary ID
     *
     * @param string $glossaryId Glossary ID of the glossary
     * @return string[]
     * @throws DeepLException
     * @internal
     */
    public function getGlossaryEntries(string $glossaryId): array
    {
        return $this->translator->getGlossaryEntries($glossaryId)->getEntries();
    }

    /**
     * Queries languages supported for glossaries by the DeepL API
     *
     * @return GlossaryLanguagePair[]
     * @throws DeepLException
     * @internal
     */
    public function getGlossaryLanguages(): array
    {
        return $this->translator->getGlossaryLanguages();
    }

    /**
     * Fetches usage information
     *
     * @return Usage
     * @throws \DeepL\DeepLException
     */
    public function getUsage(): Usage
    {
        return $this->translator->getUsage();
    }

    /**
     * Checks if DeepL translation is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (!$this->translator) {
            return false;
        }
        try {
            // Best alternative to a ping function
            $result = $this->translator->getUsage();
        } catch (\Exception) {
            $result = null;
        }

        return ($result instanceof Usage) && !$result->anyLimitReached();
    }

    /**
     * Gets information about all existing glossaries.
     * @return GlossaryInfo[] Array of GlossaryInfos containing details about all existing glossaries.
     * @throws DeepLException
     * @internal
     */
    public function listGlossaries(): array
    {
        return $this->translator->listGlossaries();
    }

    /**
     * Translates the record.
     *
     * @param string $tableName
     * @param array $record
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @param array $exceptFieldNames
     * @return array
     * @throws \DeepL\DeepLException
     */
    public function translateRecord(string $tableName, array $record, SiteLanguage $targetLanguage, array $exceptFieldNames = []): array
    {
        $translatedFields = [];

        $event = GeneralUtility::makeInstance(BeforeRecordTranslationEvent::class, $tableName, $record, $targetLanguage, $exceptFieldNames);
        $this->eventDispatcher->dispatch($event);
        $record = $event->getRecord();
        $exceptFieldNames = $event->getExceptFieldNames();

        $wasTranslated = false;
        $sourceLanguage = $this->getRecordSourceLanguage($tableName, $record);
        if ($this->canTranslate($sourceLanguage, $targetLanguage) && isset($GLOBALS['TCA'][$tableName])) {
            $slugField = null;
            foreach ($record as $fieldName => $fieldValue) {
                if (isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]) && !in_array($fieldName, $exceptFieldNames)) {
                    $config = $GLOBALS['TCA'][$tableName]['columns'][$fieldName];
                    if ($this->canFieldBeTranslated($tableName, $fieldName, $fieldValue, $config)) {
                        if ($config['config']['type'] === 'flex') {
                            $ds = $this->getFlexformDataStructure($tableName, $fieldName, $record);
                            if ($ds) {
                                $translatedFields[$fieldName] = $this->translateFlexformField(
                                    $tableName,
                                    $fieldName,
                                    $fieldValue,
                                    $ds,
                                    $sourceLanguage,
                                    $targetLanguage
                                );
                            }
                        } else {
                            $translatedFields[$fieldName] = $this->translateFieldInternal(
                                $tableName,
                                $fieldName,
                                $fieldValue,
                                $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'],
                                $sourceLanguage,
                                $targetLanguage
                            );
                        }
                        $wasTranslated = $translatedFields[$fieldName] !== $fieldValue;
                    } elseif ($config['config']['type'] === 'slug') {
                        $slugField = $fieldName;
                    }
                }
            }
        }

        if (count($translatedFields) > 0 && $slugField) {
            $slugHelper = GeneralUtility::makeInstance(
                SlugHelper::class,
                $tableName,
                $slugField,
                $GLOBALS['TCA'][$tableName]['columns'][$slugField]['config']
            );
            /** @var SlugHelper $slugHelper */
            $translatedFields[$slugField] = $slugHelper->generate(
                array_merge($record, $translatedFields),
                $record['pid']
            );
        }

        $event = GeneralUtility::makeInstance(AfterRecordTranslatedEvent::class, $tableName, $record, $targetLanguage, $translatedFields, $wasTranslated);
        $this->eventDispatcher->dispatch($event);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $translatedFields = $event->getTranslatedFields();

        return $translatedFields;
    }

    /**
     * Translates a single field.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param string $fieldValue
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $sourceLanguage
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @return string
     * @throws \DeepL\DeepLException
     */
    public function translateField(string $tableName, string $fieldName, string $fieldValue, SiteLanguage $sourceLanguage, SiteLanguage $targetLanguage): string
    {
        if ($this->canTranslate($sourceLanguage, $targetLanguage)) {
            $fieldValue = $this->translateFieldInternal(
                $tableName,
                $fieldName,
                $fieldValue,
                $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'],
                $sourceLanguage,
                $targetLanguage
            );
        }

        return $fieldValue;
    }

    /**
     * Translates the text.
     *
     * @param string $text
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @return string
     * @throws \DeepL\DeepLException
     * @todo Possibly before/after events here too?
     */
    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        $options = [
            TranslateTextOptions::PRESERVE_FORMATTING => true,
            TranslateTextOptions::TAG_HANDLING => 'html',
        ];
        [$sourceLanguageForGlossary] = explode('-', $sourceLanguage);
        [$targetLanguageForGlossary] = explode('-', $targetLanguage);
        $glossary = $this->configuration->getGlossaryForLanguagePair($sourceLanguageForGlossary, $targetLanguageForGlossary);
        if ($glossary) {
            $options[TranslateTextOptions::GLOSSARY] = $glossary;
        }

        return empty($text) ? '' : $this->translator->translateText(
            $text,
            $this->autoDetectSourceLang ? null : $sourceLanguage,
            $targetLanguage,
            $options
        );
    }

    /**
     * Checks if the field can be translated.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param ?string $fieldValue
     * @param array $tcaConfiguration
     * @return bool
     */
    protected function canFieldBeTranslated(string $tableName, string $fieldName, ?string $fieldValue, array $tcaConfiguration): bool
    {
        $result = null;

        // If translateWithDeepl is set, then:
        // - if it is false, the field is never translated
        // - if it is true, other checks are evaluated
        // Hook can still override the result.
        if (isset($tcaConfiguration['translateWithDeepl']) && !(bool)$tcaConfiguration['translateWithDeepl']) {
            $result = false;
        } elseif (empty($fieldValue)) {
            $result = false;
        } elseif (($tcaConfiguration['l10n_mode'] ?? '') === 'exclude') {
            $result = false;
        } elseif ($tcaConfiguration['config']['type'] === 'input') {
            $result = true;
            if (isset($tcaConfiguration['renderType']) && $tcaConfiguration['renderType'] !== 'default') {
                // Not the usual input
                $result = false;
            }
            if (isset($tcaConfiguration['config']['softref'])) {
                // Not the usual input either
                $result = false;
            }
            if (isset($tcaConfiguration['config']['valuePicker'])) {
                // Value picker
                $result = false;
            }
            if (isset($tcaConfiguration['config']['eval']) && preg_match('/alphanum|domainname|double2|int|is_in|md5|nospace|num|password|year/i', $tcaConfiguration['config']['eval'])) {
                // All kind of special values
                $result = false;
            }
            if (preg_match('/^[\d\.]+[a-z]*$/i', $fieldValue)) {
                // Looks like '15px' or '0.1234'
                $result = false;
            }
        } elseif ($tcaConfiguration['config']['type'] === 'text') {
            $result = true;
            if (isset($tcaConfiguration['config']['renderType']) && $tcaConfiguration['config']['renderType'] !== 'default') {
                // Anything that is not default is not translatable
                $result = false;
            } elseif (isset($tcaConfiguration['config']['enableRichtext']) && $tcaConfiguration['config']['enableRichtext'] && empty(trim(strip_tags(str_ireplace('&nbsp;', ' ', $fieldValue))))) {
                // "<p> </p>", "<br/>" or similar
                $result = false;
            }
        } elseif ($tcaConfiguration['config']['type'] === 'flex') {
            $result = true;
        }

        $event = GeneralUtility::makeInstance(CanFieldBeTranslatedCheckEvent::class, $tableName, $fieldName, $fieldValue, $result);
        $this->eventDispatcher->dispatch($event);
        $result = $event->getCanBeTranslated();

        return (bool)$result;
    }

    /**
     * Checks if translation is supported for these languages.
     *
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $sourceLanguage
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @return bool
     */
    protected function canTranslate(SiteLanguage $sourceLanguage, SiteLanguage $targetLanguage): bool
    {
        $canTranslate = true;

        if ($sourceLanguage->getLocale()->getLanguageCode() === $targetLanguage->getLocale()->getLanguageCode()) {
            $canTranslate = false;
        } elseif (!$this->isSupportedLanguage($sourceLanguage, $this->sourceLanguages)) {
            $this->logger->notice(
                sprintf(
                    'Language "%s" is not supported as a source language. Will try to proceed using DeepL autodetection',
                    $sourceLanguage->getLocale()->getLanguageCode()
                )
            );
            $this->autoDetectSourceLang = true;
        } elseif (!$this->isSupportedLanguage($targetLanguage, $this->targetLanguages)) {
            $this->logger->notice(
                sprintf(
                    'Language "%s" cannot be used as a target language because it is not supported',
                    $targetLanguage->getLocale()->getLanguageCode()
                )
            );
            $canTranslate = false;
        }

        return $canTranslate;
    }

    /**
     * Resolves the data structure for the flexform field.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param array $databaseRow
     * @return array|null
     * @see \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::initializeDataStructure()
     */
    protected function getFlexformDataStructure(string $tableName, string $fieldName, array $databaseRow): ?array
    {
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['dataStructureIdentifier'])) {
            try {
                $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
                    $GLOBALS['TCA'][$tableName]['columns'][$fieldName],
                    $tableName,
                    $fieldName,
                    $databaseRow
                );
                $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            } catch (\Exception $exception) {
                $this->logger->debug(
                    sprintf(
                        'Exception %s, code %d, message: "%s" while fetching datas tructure for %s.%s',
                        get_class($exception),
                        $exception->getCode(),
                        $exception->getMessage(),
                        $tableName,
                        $fieldName
                    )
                );
                $dataStructureArray = null;
            }
        } else {
            // Assume the data structure has been given from outside if the data structure identifier is already set.
            $dataStructureArray = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['ds'];
            $dataStructureArray = $flexFormTools->removeElementTceFormsRecursive($dataStructureArray);
            $dataStructureArray = $flexFormTools->migrateFlexFormTcaRecursive($dataStructureArray);
        }

        return $dataStructureArray;
    }

    /**
     * Fetches proxy settings for the connection. This covers both simple and advanced
     * proxy settings.
     *
     * @return string
     * @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/Configuration/Typo3ConfVars/HTTP.html?highlight=proxy#proxy
     * @see https://docs.guzzlephp.org/en/latest/request-options.html#proxy
     */
    protected function getProxySettings(): string
    {
        $result = '';

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy'])) {
            if (is_string($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy'])) {
                $result = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy'];
            } elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy'])) {
                $result = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy']['https'] ?? null;
                if (is_null($result)) {
                    $result = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy']['http'] ?? '';
                }
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy']['no'])) {
                    $apiHost = parse_url($this->configuration->getApiUrl(), PHP_URL_HOST);
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy']['no'] as $entry) {
                        if (str_ends_with($apiHost, $entry)) {
                            $result = '';
                            break;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Gets the two letter language code from the record.
     *
     * @param string $tableName
     * @param array $record
     * @return ?SiteLanguage
     */
    protected function getRecordSourceLanguage(string $tableName, array $record): ?SiteLanguage
    {
        $result = null;

        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
            $languageFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
            if (isset($record[$languageFieldName])) {
                // TODO Workspace support for pid
                try {
                    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($record['pid']);
                    $result = $site->getLanguageById($record[$languageFieldName]);
                } catch (SiteNotFoundException $exception) {
                    // Nothing to do, record is outside of sites
                } catch (\InvalidArgumentException $exception) {
                    // Nothing to do - language does not exist on the site but the record has it
                }
            }
        }

        return $result;
    }

    /**
     * DeepL deprecated some language codes. We need to fix those to be compatible.
     * Currently only target language codes 'en' and 'pt' are deprecated. They
     * must include country part. DeepL does not yet have a proper API for this.
     *
     * @param Locale $locale
     * @return string
     */
    protected function getTargetLanguageCodeFromLocale(Locale $locale): string
    {
        static $replacements = [
            LanguageCode::ENGLISH => [
                LanguageCode::ENGLISH_AMERICAN,
                LanguageCode::ENGLISH_BRITISH,
            ],
            LanguageCode::PORTUGUESE => [
                LanguageCode::PORTUGUESE_EUROPEAN,
                LanguageCode::PORTUGUESE_BRAZILIAN,
            ],
        ];
        $result = $languageCode = $locale->getLanguageCode();
        if ($replacements[$languageCode] ?? false) {
            $result = LanguageCode::standardizeLanguageCode(
                str_replace('_', '-', $locale->getName())
            );
            // Check if supported by DeepL
            if (!in_array($result, $replacements[$languageCode])) {
                // Fallback to the default
                $result = $replacements[$languageCode][0];
            }
        }

        return $result;
    }

    /**
     * Checks if the language is supported by DeepL.
     *
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $siteLanguage
     * @param \DeepL\Language[] $languages
     * @return bool
     */
    protected function isSupportedLanguage(SiteLanguage $siteLanguage, array $languages): bool
    {
        $languageCode = $siteLanguage->getLocale()->getLanguageCode();
        $matchingLanguages = array_filter($languages, function (Language $language) use ($languageCode): bool {
            [$testCode] = explode('-', $language->code);
            return strcasecmp($languageCode, $testCode) === 0;
        });

        return !empty($matchingLanguages);
    }

    /**
     * Preprocesses the field depending on its value.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param string $fieldValue
     * @param array $config
     * @return string
     */
    protected function preprocessValueDependingOnType(string $tableName, string $fieldName, string $fieldValue, array $config): string
    {
        if ($config['type'] === 'text' && isset($config['enableRichtext']) && $config['enableRichtext']) {
            $fieldValue = str_replace('&nbsp;', ' ', $fieldValue);
        }

        $event = GeneralUtility::makeInstance(PreprocessFieldValueEvent::class, $tableName, $fieldName, $fieldValue);
        $this->eventDispatcher->dispatch($event);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $fieldValue = $event->getFieldValue();

        return $fieldValue;
    }

    /**
     * Translates a single field.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param string $fieldValue
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $sourceLanguage
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @return string
     * @throws \DeepL\DeepLException
     */
    protected function translateFieldInternal(string $tableName, string $fieldName, string $fieldValue, array $tcaConfig, SiteLanguage $sourceLanguage, SiteLanguage $targetLanguage): string
    {
        $fieldValue = $this->preprocessValueDependingOnType($tableName, $fieldName, (string)$fieldValue, $tcaConfig);

        $event = GeneralUtility::makeInstance(BeforeFieldTranslationEvent::class, $tableName, $fieldName, $fieldValue, $sourceLanguage, $targetLanguage);
        $this->eventDispatcher->dispatch($event);
        $fieldValue = $event->getFieldValue();

        $fieldValue = $this->translateText(
            $fieldValue,
            $sourceLanguage->getLocale()->getLanguageCode(),
            $this->getTargetLanguageCodeFromLocale($targetLanguage->getLocale())
        );

        $event = GeneralUtility::makeInstance(AfterFieldTranslatedEvent::class, $tableName, $fieldName, $fieldValue, $sourceLanguage, $targetLanguage);
        $this->eventDispatcher->dispatch($event);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $fieldValue = $event->getFieldValue();

        return $fieldValue;
    }

    /**
     * Translates a single sheet.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param string $sheetName
     * @param array $fields
     * @param array $ds
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $sourceLanguage
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @return array
     * @throws \DeepL\DeepLException
     */
    protected function translateFlexformSheetFields(string $tableName, string $fieldName, string $sheetName, array $fields, array $ds, SiteLanguage $sourceLanguage, SiteLanguage $targetLanguage): array
    {
        foreach ($fields as $name => &$field) {
            if (($config = $ds['sheets'][$sheetName]['ROOT']['el'][$name] ?? false)) {
                $currentFlexformFieldName = $fieldName . '.' . $sheetName . '.' . $fieldName . '.' . $name;
                if ($field['vDEF'] ?? false) {
                    // Regular field
                    if ($this->canFieldBeTranslated($tableName, $fieldName . '.' . $name, $field['vDEF'], $config)) {
                        $field['vDEF'] = $this->translateFieldInternal(
                            $tableName,
                            $currentFlexformFieldName,
                            $field['vDEF'],
                            $config['config'],
                            $sourceLanguage,
                            $targetLanguage
                        );
                    }
                } elseif ($config['section'] ?? false) {
                    $field['el'] = $this->translateFlexformSection($tableName, $currentFlexformFieldName, $ds, $config, $field['el'], $sourceLanguage, $targetLanguage);
                }
            }
        }

        return $fields;
    }

    /**
     * Translates a single field.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param string $fieldValue
     * @param array $ds
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $sourceLanguage
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @return string
     * @throws \DeepL\DeepLException
     */
    protected function translateFlexformField(string $tableName, string $fieldName, string $fieldValue, array $ds, SiteLanguage $sourceLanguage, SiteLanguage $targetLanguage): string
    {
        $fields = GeneralUtility::xml2array($fieldValue);

        foreach ($fields['data'] as $sheetName => &$sheetData) {
            $sheetData['lDEF'] = $this->translateFlexformSheetFields(
                $tableName,
                $fieldName,
                $sheetName,
                $sheetData['lDEF'],
                $ds,
                $sourceLanguage,
                $targetLanguage
            );
        }

        $tools = GeneralUtility::makeInstance(FlexFormTools::class);
        /** @var FlexFormTools $tools */
        $fieldValue = $tools->flexArray2Xml($fields, true);

        return $fieldValue;
    }

    /**
     * Translates a single flexform section.
     *
     * @param string $tableName
     * @param string $currentFlexformFieldName
     * @param array $ds
     * @param array $config
     * @param array $section
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $sourceLanguage
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @return array
     * @throws \DeepL\DeepLException
     */
    protected function translateFlexformSection(string $tableName, string $currentFlexformFieldName, array $ds, array $config, array &$section, SiteLanguage $sourceLanguage, SiteLanguage $targetLanguage): array
    {
        $sectionField = array_key_first($config['el']);
        foreach ($section as $k => &$structure) {
            foreach ($structure[$sectionField]['el'] as $name => &$field) {
                $fullFieldName = $currentFlexformFieldName . '.' . $k . '.' . $name;
                $fieldTcaConfig = &$config['el'][$sectionField]['el'][$name];
                if ($this->canFieldBeTranslated($tableName, $fullFieldName, $field['vDEF'], $fieldTcaConfig)) {
                    $field['vDEF'] = $this->translateFieldInternal(
                        $tableName,
                        $currentFlexformFieldName,
                        $field['vDEF'],
                        $fieldTcaConfig['config'],
                        $sourceLanguage,
                        $targetLanguage
                    );
                }
            }
        }

        return $section;
    }
}
