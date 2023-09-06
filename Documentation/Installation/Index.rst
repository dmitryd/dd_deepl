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

You can also set DeepL API key via web server environment varable :php:`TYPO3_DEEPL_API_KEY`.
