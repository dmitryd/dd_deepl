..  include:: /Includes.rst.txt
..  highlight:: typoscript

..  index::
    TypoScript; Constants
..  _configuration-typoscript-constants:

Constants
=========

..  confval:: apiKey

    :type: string
    :Default: empty

    This is an API key for DeepL.

    Example::

       module.tx_dddeepl.apiKey = <your value here>

..  confval:: maximumNumberOfGlossariesPerLanguage

    :type: integer
    :Default: 2

    Maximum number of glossaries that can be added.

    Example::

       module.tx_dddeepl.maximumNumberOfGlossariesPerLanguage = 2

