# dd_deepl TYPO3 extension

This projects contains a TYPO3 CMS extnsions that uses [DeepL](https://deepl.com/) official PHP API library to translate TYPO3 content
to various languages. DeepL is possibly the best available online translator on the Internet.

Differences from other similar extensions are:

* No legacy code (the extension is made from scratch for TYPO3 11/12)
* The extension uses official API instead of https calls
* The extension allows to manage & use glossaries in an easy way
* The code is minimalistic to make sure very little of needs to be changed in future

## Installation

1. Install using composer:  
```
composer req "dmitryd/dd-deepl"
```
2. Add static Typoscript from `EXT:dd_deepl/Configuration/TypoScript/`
3. Add DeepL API key either to TypoScript or to the `TYPO3_DEEPL_API_KEY` environment variable. You can get the key by registering with DeepL.
4. If you use a free license, set API host to https://api-free.deepl.com/ either via TypoScript or `TYPO3_DEEPL_URL` environment variable.

## Usage

When you translate the page or content, you will see an additional option for using DeepL for translations.

In the List module each language button is duplicated with a small DeepL overlay on it. Clicking this button will localize and translate
the record using DeepL. Only tables with names starting from `tx_` can be translated (so no luck for `tt_address`, for example).

## Copyright

The extension is copyright (c) by Dmitry Dulepov, 2023.

Contact me by [email](mailto:dmitry.dulepov@gmail.com) if you need a custom TYPO3 extension made for you.
