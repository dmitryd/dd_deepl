..  include:: /Includes.rst.txt
..  highlight:: typoscript
..  index::
    TypoScript; Setup
..  _configuration-typoscript-setup:

Setup
=====

..  confval:: apiUrl

    :type: string / stdWrap
    :Default: :typoscript:`{$module.tx_dddeepl.settings.apiUrl}`

    Default is a constant. But if there is environment variable named :php:`TYPO3_DEEPL_URL`, it will be used instead.

..  confval:: apiKey

    :type: string / stdWrap
    :Default: empty

    Default is a constant. But if there is environment variable named :php:`TYPO3_DEEPL_API_KEY`, it will be used instead.

..  confval:: maximumNumberOfGlossariesPerLanguage

    :type: integer
    :Default: :typoscript:`{$module.tx_dddeepl.settings.maximumNumberOfGlossariesPerLanguage}`

    Maximum number of glossaries that can be added.

