<?php

namespace Dmitryd\DdDeepl\Event;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

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

/**
 * This event will fire after the record is translated to do any necessary
 * post-processing after the translation.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
final class AfterRecordTranslatedEvent
{
    /**
     * Creates the instance of the class.
     *
     * @param string $tableName
     * @param array $record
     * @param array $translatedFields
     */
    public function __construct(protected string $tableName, protected array $record, protected SiteLanguage $targetLanguage, protected array $translatedFields, protected bool $wasTranslated)
    {
    }

    /**
     * Fetches the table name for the event.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Fetches the record for the event.
     *
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * Fetches the target language.
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getTargetLanguage(): SiteLanguage
    {
        return $this->targetLanguage;
    }

    /**
     * Fetches translated fields.
     *
     * @return array
     */
    public function getTranslatedFields(): array
    {
        return $this->translatedFields;
    }

    /**
     * Sets translated fields.
     *
     * @param array $translatedFields
     */
    public function setTranslatedFields(array $translatedFields): void
    {
        $this->translatedFields = $translatedFields;
    }

    /**
     * Tells if any field was translated.
     *
     * @return bool
     */
    public function getWasTranslated(): bool
    {
        return $this->wasTranslated;
    }
}
