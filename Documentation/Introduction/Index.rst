..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

This extension helps to translate content and records to various languages using DeepL translation service.
You will need to register an account with DeepL.

..  _screenshots:

Screenshots
===========

..  figure:: /Images/TranslationWindow.jpg
    :class: with-shadow
    :alt: Translation window
    :width: 500px

    Additional button to translate content using DeepL.

..  figure:: /Images/ListModule.jpg
    :class: with-shadow
    :alt: List module
    :width: 500px

    Additional button to translate content using DeepL inside the List module.

Why this extension?
===================

There are other similar extensions to this. One is `EXT:deepltranslate`_, which is the first extenstion of the kind.
It works for TYPO3 versions 8 and 9 but not later. There is a fork of this extension by web-vision Gmbh named
`EXT:wv_deepltranslate`_. This extension works with newer TYPO3 versions but it inherits a lot of old legacy code.
While developers at web-vision did really great job at supporting and developing of the fork, having a lot of
legacy code, overriding some internal TYPO3 classes, and rejecting some proposed features forced me to write
my own extension. Also there is code for supporting Google translator, which looks out of place there.

Also other extensions do not allow you to modify the translation process (such as inspect and change field values
before and after the translation, or force/prevent translation of the field). This extension provides several events
that can alter the behavior of translation or modify field values before and after the translation.

Starting from version 12.1.0 the extension can translate flexform fields (including sections), which is a
very useful feature for custom content elements.

This extension is not in any way based on two above mentioned extensions or contains any of their code. This is
completely new code.

.. _EXT:deepltranslate: https://extensions.typo3.org/extension/deepltranslate
.. _EXT:wv_deepltranslate: https://extensions.typo3.org/extension/wv_deepltranslate


Source code, bugs, etc
======================

Project is hosted on GitHib at https://github.com/dmitryd/dd_deepl. Please, note that questions will not be
answered in the bug tracker. You should use TYPO3 slack for questions and answers.
