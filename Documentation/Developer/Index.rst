..  include:: /Includes.rst.txt
..  highlight:: php

..  _developer:

==============
For Developers
==============

You can use the :php:`Dmitryd\DdDeepl\Service\DeeplTranslationService` class
to translate TYPO3 records, certain fields, or plain texts. Records must have
an entry in :php:`$GLOBALS['TCA']`.

Create the service through dependency injection or
:php:`GeneralUtility::makeInstance()`. If the current request does not contain
a site context, call :php:`setSite()` before invoking DeepL operations.

If no site can be resolved, or if the site has no :yaml:`ddDeepl.apiKey`, DeepL
is treated as disabled.

There are several events that can alter the behavior of the service. You can
use them to get notified about translations, force or prevent a field from
being translated, or alter the field value before and after it is sent to
DeepL.

..  _developer-api:

API
===

Translation service
-------------------

..  php:namespace:: Dmitryd\DdDeepl\Service

..  php:class:: DeeplTranslationService

    This is the class you, as a developer, would use to translate data using DeepL.

    ..  php:method:: __construct(array $deeplOptions = [], ?FrontendInterface $runtimeCache = null, ?ConfigurationFactory $configurationFactory = null)

        Creates the instance of the class. You can pass additional DeepL
        options as described in the `DeepL documentation`_. The runtime cache
        and configuration factory are injected by TYPO3 during normal service
        creation.

        .. _DeepL documentation: https://github.com/DeepLcom/deepl-php#configuration

    ..  php:method:: setSite(?Site $site): self

        :returntype: :php:`self`
        :returns: The current service instance.

        Sets the site context for the next DeepL operations. Use this method
        for CLI tasks, backend actions without a request site attribute, or
        custom integrations that already know the target site.

    ..  php:method:: isAvailable(): bool

        :returntype: bool
        :returns: :php:`true` if DeepL can process requests with the current
            site configuration and API limits.

    ..  php:method:: translateRecord(string $tableName, array $record, SiteLanguage $targetLanguage, array $exceptFieldNames = []): array

        :returntype: array
        :returns: Array with translated fields

        The method will go through each field in the record, evaluate if it can
        be translated and call DeepL for translation. The result is an array
        with translations.

    ..  php:method:: translateField(string $tableName, string $fieldName, string $fieldValue, SiteLanguage $sourceLanguage, SiteLanguage $targetLanguage): string

        :returntype: string
        :returns: Translated field value

        The method will get the value of the field and call DeepL for
        translation. Unlike in :php:`translateRecord()`, there are no checks
        if the field can be translated.

    ..  php:method:: translateText(string $text, string $sourceLanguage, string $targetLanguage): string

        :returntype: string
        :returns: Translated field value

        The method will get the text and call DeepL for translation.

Events
------

..  php:namespace:: Dmitryd\DdDeepl\Event

..  php:class:: AfterFieldTranslatedEvent

    This event is fired after the field was translated by :php:`translateRecord` or :php:`translateField`
    and allows to modify the translated value.

    ..  php:method:: getTableName(): string

        :returntype: string
        :returns: Table name of the field

    ..  php:method:: getFieldName(): string

        :returntype: string
        :returns: The field name

    ..  php:method:: getFieldValue(): string

        :returntype: string
        :returns: The current (translated) field value

    ..  php:method:: getSourceLanguage(): SiteLanguage

        :returntype: \\TYPO3\\CMS\\Core\\Site\\Entity\\SiteLanguage
        :returns: Source language to translate from

    ..  php:method:: getTargetLanguage(): SiteLanguage

        :returntype: \\TYPO3\\CMS\\Core\\Site\\Entity\\SiteLanguage
        :returns: Target language to translate to

    ..  php:method:: setFieldValue(string $fieldValue): void

        Sets the new value of the field.


..  php:class:: AfterRecordTranslatedEvent

    This event is fired after the record was translated by :php:`translateRecord`.
    You can examine fields and alter their contents by using :php:`getTranslatedFields` and
    :php:`setTranslatedFields`. Note that there is no method for getting the source language
    because you can get this information from the record.

    ..  php:method:: getTableName(): string

        :returntype: string
        :returns: Table name of the field

    ..  php:method:: getRecord(): array

        :returntype: array
        :returns: Original (non-translated) record

    ..  php:method:: getTargetLanguage(): SiteLanguage

        :returntype: \\TYPO3\\CMS\\Core\\Site\\Entity\\SiteLanguage
        :returns: Target language to translate to

    ..  php:method:: getTranslatedFields(): array

        :returntype: array
        :returns: The current (translated) field values

    ..  php:method:: setTranslatedFields(array $translatedFields): void

        Replaces translated fields with a new array of fields.


..  php:class:: BeforeFieldTranslationEvent

    This event is fired before the field is translated by :php:`translateRecord` or :php:`translateField`
    and allows to modify the original field value before it is sent to DeepL.

    ..  php:method:: getTableName(): string

        :returntype: string
        :returns: Table name of the field

    ..  php:method:: getFieldName(): string

        :returntype: string
        :returns: The field name

    ..  php:method:: getFieldValue(): string

        :returntype: string
        :returns: The current field value

    ..  php:method:: getSourceLanguage(): SiteLanguage

        :returntype: \\TYPO3\\CMS\\Core\\Site\\Entity\\SiteLanguage
        :returns: Source language to translate from

    ..  php:method:: getTargetLanguage(): SiteLanguage

        :returntype: \\TYPO3\\CMS\\Core\\Site\\Entity\\SiteLanguage
        :returns: Target language to translate to

    ..  php:method:: setFieldValue(string $fieldValue): void

        Sets the new value of the field.


..  php:class:: BeforeRecordTranslationEvent

    This event is fired before the record is translated by :php:`translateRecord`.
    You can examine fields and alter their contents by using :php:`getTranslatedFields` and
    :php:`setTranslatedFields`. Note that there is no method for getting the source language
    because you can get this information from the record.

    ..  php:method:: getTableName(): string

        :returntype: string
        :returns: Table name of the field

    ..  php:method:: getRecord(): array

        :returntype: array
        :returns: Original (non-translated) record

    ..  php:method:: getTargetLanguage(): SiteLanguage

        :returntype: \\TYPO3\\CMS\\Core\\Site\\Entity\\SiteLanguage
        :returns: Target language to translate to

    ..  php:method:: getTranslatedFields(): array

        :returntype: array
        :returns: The current field values

    ..  php:method:: setTranslatedFields(array $translatedFields): void

        Replaces translated fields with a new array of fields.


..  php:class:: CanFieldBeTranslatedCheckEvent

    This event is fired after the DeepL translation service evaluated whether
    the field can be translated.

    ..  php:method:: getTableName(): string

        :returntype: string
        :returns: Table name of the field

    ..  php:method:: getFieldName(): string

        :returntype: string
        :returns: The field name

    ..  php:method:: getCanBeTranslated()

        :returntype: bool|null
        :returns: :php:`true`, if the service thinks that the field can be
            translated, :php:`false`, if definitely not, :php:`null`, if the
            service could not decide.

    ..  php:method:: setCanBeTranslated(): void

        Pass :php:`true`, if the service thinks that the field can be
        translated, :php:`false`, if not. Note that you cannot pass
        :php:`null` here. If you are unsure, do not set any value. The service
        will not translate the field unless the value after all events is set
        to :php:`true`.


..  php:class:: PreprocessFieldValueEvent

    This event is fired before the field is set to DeepL for translation and allows you to modify the value.
    A typical example would be, for example, doing data clean up or replacing :html:`&nbsp;` with a normal space
    in texts, or removing several :html:`<br>` tags.

    ..  php:method:: getTableName(): string

        :returntype: string
        :returns: Table name of the field

    ..  php:method:: getFieldName(): string

        :returntype: string
        :returns: The field name

    ..  php:method:: getFieldValue(): string

        :returntype: string
        :returns: The current field value

    ..  php:method:: setFieldValue(string $fieldValue): void

        Sets the new value of the field.

Examples
--------

Translating a record:

..  code-block:: php
    :linenos:
    :emphasize-lines: 7-7

    $languageId = 1;
    $newsId = 1;
    $newsRecord = BackendUtility::getRecord('tx_news_domain_model_news', $newsId);
    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($newsRecord['pid']);
    $targetLanguage = $site->getLanguageById($languageId);
    $service = GeneralUtility::makeInstance(\Dmitryd\DdDeepl\Service\DeeplTranslationService::class);
    $translatedFields = $service->translateRecord('tx_news_domain_model_news', $newsRecord, $targetLanguage);
