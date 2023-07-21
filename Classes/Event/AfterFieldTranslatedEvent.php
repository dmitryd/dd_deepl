<?php

namespace Dmitryd\DdDeepl\Event;

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
 * This event will fire after the field is translated to do any necessary
 * pre-processing before the translation.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
final class AfterFieldTranslatedEvent
{
    /**
     * Creates the instance of the class.
     *
     * @param string $tableName
     * @param array $record
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage $targetLanguage
     * @param array $exceptFieldNames
     */
    public function __construct(protected string $tableName, protected string $fieldName, protected string $fieldValue, protected SiteLanguage $sourceLanguage, protected SiteLanguage $targetLanguage)
    {
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
     * Fetches the field name.
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Fetches the field value.
     *
     * @return string
     */
    public function getFieldValue(): string
    {
        return $this->fieldValue;
    }

    /**
     * Fetches the source language.
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getSourceLanguage(): SiteLanguage
    {
        return $this->sourceLanguage;
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
     * Sets the new field value.
     *
     * @param string $fieldValue
     */
    public function setFieldValue(string $fieldValue): void
    {
        $this->fieldValue = $fieldValue;
    }
}
