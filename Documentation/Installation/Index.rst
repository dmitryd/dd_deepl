..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

Install the extension using composer:

.. code-block:: bash

  composer require "dmitryd/dd-deepl"

Add the static TypoScript template from :file:`EXT:dd_deepl/Configuration/TypoScript/` to your site

Go to https://www.deepl.com/signup and sign up. Get a free API key.

Configure API key in the TypoScript constants like this:

.. code-block:: typoscript

  module.tx_dddeepl.settings.apiKey = <Insert your API key here>

If you use a free API key, change the API host like this:

.. code-block:: typoscript

  module.tx_dddeepl.settings.apiUrl = https://api-free.deepl.com

You can also set DeepL API key and host via web server environment varables :php:`TYPO3_DEEPL_API_KEY` and :php:`TYPO3_DEEPL_URL`.
