..  include:: /Includes.rst.txt

..  _migration:

=========
Migration
=========

Version 14 uses site configuration as the default configuration source.
Existing TypoScript settings are not read unless legacy mode is explicitly
enabled.

Migrate TypoScript settings
===========================

Move the former TypoScript settings to the :yaml:`ddDeepl` key in the site
configuration:

..  code-block:: typoscript

    module.tx_dddeepl.settings.apiKey = your-api-key
    module.tx_dddeepl.settings.timeout = 30
    module.tx_dddeepl.settings.maximumNumberOfGlossariesPerLanguage = 2
    module.tx_dddeepl.settings.glossaries.de-en = 1a7170f3-edab-4c66-949a-4db3dc6a233f

..  code-block:: yaml

    ddDeepl:
      apiKey: '%env(TYPO3_DEEPL_API_KEY)%'
      timeout: 30
      maximumNumberOfGlossariesPerLanguage: 2
      glossaries:
        de-en: '1a7170f3-edab-4c66-949a-4db3dc6a233f'

The :yaml:`ddDeepl.apiKey` value should normally use an environment variable
so the secret is not stored in the site configuration file.

Temporary legacy mode
=====================

Legacy TypoScript configuration can be enabled temporarily in the extension
configuration. In Composer-based TYPO3 installations this configuration can be
set in :file:`config/system/additional.php`:

..  code-block:: php

    <?php

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['dd_deepl']['configurationSource'] = 'legacyTypoScript';

The same value can also be stored through the TYPO3 extension configuration UI,
if it is available in the project. The resulting configuration value must be:

..  code-block:: text

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['dd_deepl']['configurationSource']
        = 'legacyTypoScript'

This mode is deprecated, emits a deprecation warning, and should only be used
as a temporary rollback path while migrating site configuration. There is no
automatic fallback from site configuration to TypoScript.
