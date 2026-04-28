..  include:: /Includes.rst.txt
..  highlight:: typoscript

..  index::
    TypoScript; Constants
..  _configuration-typoscript-constants:

Constants
=========

..  confval:: apiKey
    :name: typoscript-constant-api-key

    :type: string
    :Default: empty

    This is an API key for DeepL.

    Example::

       module.tx_dddeepl.settings.apiKey = <your value here>

..  confval:: maximumNumberOfGlossariesPerLanguage
    :name: typoscript-constant-maximum-number-of-glossaries

    :type: integer
    :Default: 2

    Maximum number of glossaries that can be added.

    Example::

       module.tx_dddeepl.settings.maximumNumberOfGlossariesPerLanguage = 2

..  confval:: timeout
    :name: typoscript-constant-timeout

    :type: integer
    :Default: 10

    How long to wait for network requests to DeepL servers

    Example::

       module.tx_dddeepl.settings.timeout = 5
