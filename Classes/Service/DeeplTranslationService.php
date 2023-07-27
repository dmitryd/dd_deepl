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
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a service to translate records and texts in TYPO3.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class DeeplTranslationService
{
    protected Configuration $configuration;

    protected EventDispatcher $eventDispatcher;

    protected Translator $translator;

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

        $deeplOptions = array_merge(
            [
                TranslatorOptions::SERVER_URL => $this->configuration->getApiHost(),
            ],
            $deeplOptions
        );
        $this->translator = new Translator($this->configuration->getApiKey(), $deeplOptions);
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
     */
    public function getGlossaryLanguages(): array
    {
        return $this->translator->getGlossaryLanguages();
    }

    /**
     * Checks if DeepL translation is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            // Best alternative to a ping function
            $result = $this->translator->getUsage();
        } catch (DeepLException) {
            $result = null;
        }

        return ($result instanceof Usage) && !$result->anyLimitReached();
    }

    /**
     * Gets information about all existing glossaries.
     * @return GlossaryInfo[] Array of GlossaryInfos containing details about all existing glossaries.
     * @throws DeepLException
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

        if (isset($GLOBALS['TCA'][$tableName])) {
            $sourceLanguage = $this->getRecordSourceLanguage($tableName, $record);
            foreach ($record as $fieldName => $fieldValue) {
                if (isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]) && !in_array($fieldName, $exceptFieldNames)) {
                    $config = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
                    if ($this->canFieldBeTranslated($tableName, $fieldName, $fieldValue, $config)) {
                        $translatedFields[$fieldName] = $this->translateField(
                            $tableName,
                            $fieldName,
                            $fieldValue,
                            $sourceLanguage,
                            $targetLanguage
                        );
                    }
                }
            }
        }

        $event = GeneralUtility::makeInstance(AfterRecordTranslatedEvent::class, $tableName, $record, $translatedFields);
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
        $fieldValue = $this->preprocessValueDependingOnType($tableName, $fieldName, (string)$fieldValue, $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']);

        $event = GeneralUtility::makeInstance(BeforeFieldTranslationEvent::class, $tableName, $fieldName, $fieldValue, $sourceLanguage, $targetLanguage);
        $this->eventDispatcher->dispatch($event);
        $fieldValue = $event->getFieldValue();

        $fieldValue = $this->translateText(
            $fieldValue,
            $this->updateLanguageCode($sourceLanguage->getTwoLetterIsoCode()),
            $this->updateLanguageCode($targetLanguage->getTwoLetterIsoCode())
        );

        $event = GeneralUtility::makeInstance(AfterFieldTranslatedEvent::class, $tableName, $fieldName, $fieldValue, $sourceLanguage, $targetLanguage);
        $this->eventDispatcher->dispatch($event);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $fieldValue = $event->getFieldValue();

        return $fieldValue;
    }

    /**
     * Translates the record.
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
        return empty($text) ? '' : $this->translator->translateText(
            $text,
            $sourceLanguage,
            $targetLanguage
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

        if (empty($fieldValue)) {
            $result = false;
        } elseif (isset($tcaConfiguration['translateWithDeepl']) && $tcaConfiguration['translateWithDeepl']) {
            $result = true;
        } elseif ($tcaConfiguration['type'] === 'input') {
            $result = true;
            if (isset($tcaConfiguration['renderType']) && $tcaConfiguration['renderType'] !== 'default') {
                // Not the usual input
                $result = false;
            }
            if (isset($tcaConfiguration['valuePicker'])) {
                // Value picker
                $result = false;
            }
            if (isset($tcaConfiguration['eval']) && preg_match('/alphanum|domainname|double2|int|is_in|md5|nospace|num|password|year/i', $tcaConfiguration['eval'])) {
                // All kind of special values
                $result = false;
            }
        } elseif ($tcaConfiguration['type'] === 'text') {
            $result = true;
            if (isset($tcaConfiguration['renderType']) && $tcaConfiguration['renderType'] !== 'default') {
                // Anything that is not default is not translatable
                $result = false;
            }
        }

        $event = GeneralUtility::makeInstance(CanFieldBeTranslatedCheckEvent::class, $tableName, $fieldName, $result);
        $this->eventDispatcher->dispatch($event);
        $result = $event->getCanBeTranslated();

        return (bool)$result;
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
     * Deepl needs some languages codes to be different from TYPO3 ones. This method updates such codes.
     *
     * @param string $languageCode
     * @return string
     */
    protected function updateLanguageCode(string $languageCode): string
    {
        return $languageCode === 'en' ? 'en-US' : $languageCode;
    }
}
