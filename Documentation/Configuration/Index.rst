..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

Configure the extension in the site configuration:

..  code-block:: yaml

    ddDeepl:
      apiKey: '%env(TYPO3_DEEPL_API_KEY)%'
      timeout: 30
      maximumNumberOfGlossariesPerLanguage: 2
      glossaries:
        de-en: '1a7170f3-edab-4c66-949a-4db3dc6a233f'

If the :yaml:`ddDeepl` key is missing, DeepL is disabled for that site.

Site configuration reference
============================

..  confval-menu::
    :name: dd-deepl-site-configuration
    :display: table
    :type:
    :default:

    ..  confval:: ddDeepl.apiKey
        :type: string
        :default: empty

        DeepL API key for the site. Free API keys end with :yaml:`:fx` and
        automatically use the DeepL free API endpoint.

        The value can use a TYPO3 environment placeholder, for example
        :yaml:`%env(TYPO3_DEEPL_API_KEY)%`. If the placeholder is still
        unresolved at runtime, the API key is treated as empty, DeepL is
        disabled for the site, and a notice is written to the TYPO3 log.

    ..  confval:: ddDeepl.timeout
        :type: integer
        :default: 30

        Timeout in seconds for network requests to DeepL. Values are limited
        to the effective range from :yaml:`3` to :yaml:`60`.

        Larger pages may perform many DeepL requests. If DeepL does not
        respond before the timeout, the affected localized records are removed
        again so editors can retry them later. The technical error details are
        written to the TYPO3 log.

    ..  confval:: ddDeepl.maximumNumberOfGlossariesPerLanguage
        :type: integer
        :default: 2

        Maximum number of DeepL glossaries that can be created per language
        pair through this extension.

    ..  confval:: ddDeepl.glossaries
        :type: map
        :default: empty

        Maps a language pair to a glossary id. The key format is
        :yaml:`source-target`, for example :yaml:`de-en`.

..  toctree::
    :maxdepth: 5
    :titlesonly:

    TypoScriptReference/Index
    TSConfig
