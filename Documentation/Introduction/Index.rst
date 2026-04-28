..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

This extension translates TYPO3 content and records using the DeepL
translation service.
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

There are other similar extensions to this. One is `EXT:deepltranslate`_,
which is the first extension of the kind. It works for TYPO3 versions 8 and 9
but not later. There is a fork of this extension by web-vision GmbH named
`EXT:wv_deepltranslate`_. This extension works with newer TYPO3 versions but
it inherits a lot of old legacy code. While developers at web-vision did a
great job supporting and developing the fork, this extension follows a
separate implementation.

Other extensions do not allow you to modify the translation process, for
example to inspect and change field values before and after translation, or to
force or prevent translation of the field. This extension provides several
events that can alter translation behavior or modify field values before and
after the translation.

The extension can translate FlexForm fields, including sections. This is useful
for custom content elements.

This extension is not in any way based on two above mentioned extensions or contains any of their code. This is
completely new code.

.. _EXT:deepltranslate: https://extensions.typo3.org/extension/deepltranslate
.. _EXT:wv_deepltranslate: https://extensions.typo3.org/extension/wv_deepltranslate


Source code, bugs, etc
======================

The project is hosted on GitHub at https://github.com/dmitryd/dd_deepl.
Questions are not answered in the bug tracker. Use TYPO3 Slack for questions
and answers.
