..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

Install the extension using composer:

.. code-block:: bash

  composer require "dmitryd/dd-deepl"

If you need to use the console command, require also `undkonsorten/extbase-cli-aware-configuration-manager`
and read the documebtation at https://github.com/undkonsorten/extbase-cli-aware-configuration-manager.

Go to https://www.deepl.com/signup and sign up. Get a free API key.

Configure API key in the TypoScript constants like this:

.. code-block:: typoscript

  module.tx_dddeepl.settings.apiKey = <Insert your API key here>

You can also set DeepL API key via web server environment varable :php:`TYPO3_DEEPL_API_KEY`.

..  warning::
    Due to dependencies on various 3rd party packages, this extension works only if TYPO3 is installed in composer mode.
    There will be no support for non-composer installations.
