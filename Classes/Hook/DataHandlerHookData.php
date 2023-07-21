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

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * This class contains a data structure about the current command that
 * DataHAndlerTranslationHook needs to save for future use. Since there
 * can be many DataHandler instances, this data has to be saved per
 * instance. Such approach saves us from examinining all kind internal
 * TYPO3 request parameters that can change in any version.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @internal
 */
final class DataHandlerHookData
{
    protected array $record = [];

    protected ?SiteLanguage $sourceLanguage = null;

    protected string $tableName = '';

    protected ?SiteLanguage $targetLanguage = null;

    protected bool $translationEnabled = false;

    /**
     * Fetches the record.
     *
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * Sets the record.
     *
     * @param array $record
     */
    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    /**
     * Fetches the source language.
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     */
    public function getSourceLanguage(): ?SiteLanguage
    {
        return $this->sourceLanguage;
    }

    /**
     * Sets the source language.
     *
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null $sourceLanguage
     */
    public function setSourceLanguage(?SiteLanguage $sourceLanguage): void
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    /**
     * Fetches the table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Sets the table name.
     *
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Gets the target language.
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     */
    public function getTargetLanguage(): ?SiteLanguage
    {
        return $this->targetLanguage;
    }

    /**
     * Sets the target language.
     *
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null $targetLanguage
     */
    public function setTargetLanguage(?SiteLanguage $targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * Reports if the translation is enabled.
     *
     * @return bool
     */
    public function isTranslationEnabled(): bool
    {
        return $this->translationEnabled;
    }

    /**
     * Sets the enabbe status for translation.
     *
     * @param bool $translationEnabled
     */
    public function setTranslationEnabled(bool $translationEnabled): void
    {
        $this->translationEnabled = $translationEnabled;
    }
}
