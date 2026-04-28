..  include:: /Includes.rst.txt
..  highlight:: typoscript
..  index::
    TSConfig
..  _configuration-tsconfig:

TSConfig reference
==================

Page TSConfig
=============

localization.enableDeepL
~~~~~~~~~~~~~~~~~~~~~~~~

:aspect:`Datatype`
   boolean


:aspect:`Description`
   Enables DeepL as a TYPO3 localization handler in the localization wizard.
   Note that direct :php:`DataHandler` translations are not affected by this
   option.

:aspect:`Default`
   1

:aspect:`Example`
   .. code-block:: typoscript

      mod {
         web_layout {
             localization.enableDeepL = 1
         }
      }
