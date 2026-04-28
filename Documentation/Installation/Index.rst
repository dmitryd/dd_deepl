..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

Install the extension in a TYPO3 14 Composer installation:

..  code-block:: bash

    composer require "dmitryd/dd-deepl"

Go to https://www.deepl.com/signup and sign up. Get a free API key.

Configure DeepL in the site configuration:

..  code-block:: yaml

    ddDeepl:
      apiKey: '%env(TYPO3_DEEPL_API_KEY)%'
      timeout: 10
      maximumNumberOfGlossariesPerLanguage: 2
      glossaries:
        de-en: '1a7170f3-edab-4c66-949a-4db3dc6a233f'

If the environment variable is not set, DeepL is treated as disabled for
that site.

Legacy TypoScript configuration is available only as a deprecated opt-in
fallback. See :ref:`migration` before using it.

..  warning::
    Due to dependencies on various third-party packages, this extension works
    only if TYPO3 is installed in Composer mode.
    There will be no support for non-composer installations.
