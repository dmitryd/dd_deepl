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
   Enables the translation using DeepL in the user interface. Note that :php:`DataHandler` translations are not affected by these options.

:aspect:`Default`
   1

:aspect:`Example`
   .. code-block:: typoscript

      mod {
         web_layout {
             localization.enableDeepL = 1
         }
         web_list {
             localization.enableDeepL = 1
         }
      }
