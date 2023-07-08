<?php
namespace Dmitryd\DdDeepl\Hook;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class DataHandlerHookData
{
    protected array $record = [];

    protected ?SiteLanguage $sourceLanguage = null;

    protected string $tableName = '';

    protected ?SiteLanguage $targetLanguage = null;

    protected bool $translationEnabled = false;

    /**
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * @param array $record
     */
    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    /**
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     */
    public function getSourceLanguage(): ?SiteLanguage
    {
        return $this->sourceLanguage;
    }

    /**
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null $sourceLanguage
     */
    public function setSourceLanguage(?SiteLanguage $sourceLanguage): void
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     */
    public function getTargetLanguage(): ?SiteLanguage
    {
        return $this->targetLanguage;
    }

    /**
     * @param \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null $targetLanguage
     */
    public function setTargetLanguage(?SiteLanguage $targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * @return bool
     */
    public function isTranslationEnabled(): bool
    {
        return $this->translationEnabled;
    }

    /**
     * @param bool $translationEnabled
     */
    public function setTranslationEnabled(bool $translationEnabled): void
    {
        $this->translationEnabled = $translationEnabled;
    }
}
