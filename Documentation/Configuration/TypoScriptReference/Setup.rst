..  include:: /Includes.rst.txt
..  highlight:: typoscript
..  index::
    TypoScript; Setup
..  _configuration-typoscript-setup:

Setup
=====

..  confval:: apiKey

    :type: string / stdWrap
    :Default: empty

    Default is a constant. But if there is environment variable named :php:`TYPO3_DEEPL_API_KEY`, it will be used instead.

..  confval:: glossaries

    :type: array
    :Default: empty

    An array of mappings betweem language pairs and glossary id values. You can find glossary id in the Backend module or
    in the output of the glossary console command (see :ref:`_how-to-manage-glossaries`). In most cases DeepL uses the
    glossary automaically but sometimes you need to specify it to be used for translations.

    Example:

..  code-block:: typoscript

    module.tx_dddeepl {
      settings {
        glossaries {
          de-en = 1a7170f3-edab-4c66-949a-4db3dc6a233f
          de-fr = 00526740-a941-414c-8bbe-6aa69e619222
          de-it = 513e3440-0704-11ef-b551-6a4a7949937b
        }
      }
    }

..  confval:: maximumNumberOfGlossariesPerLanguage

    :type: integer
    :Default: :typoscript:`{$module.tx_dddeepl.settings.maximumNumberOfGlossariesPerLanguage}`

    Maximum number of glossaries that can be added.

..  confval:: timeout

    :type: integer
    :Default: :typoscript:`{$module.tx_dddeepl.settings.timeout}`

    How long to wait for network requests to DeepL servers

    Example::

       module.tx_dddeepl.settings.timeout = 5
