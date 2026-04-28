# dd_deepl TYPO3 extension

This project contains a TYPO3 CMS extension that uses [DeepL](https://deepl.com/) official PHP API library to translate TYPO3 content
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
2. Add DeepL settings to the site configuration:
```yaml
ddDeepl:
  apiKey: '%env(TYPO3_DEEPL_API_KEY)%'
  timeout: 10
  maximumNumberOfGlossariesPerLanguage: 2
  glossaries:
    de-en: '1a7170f3-edab-4c66-949a-4db3dc6a233f'
```
3. Register with DeepL to get the API key.
4. Set the `TYPO3_DEEPL_API_KEY` environment variable to that API key. The
   `%env(TYPO3_DEEPL_API_KEY)%` value in the site configuration only reads this
   variable; it does not create it.
   You can also put the API key directly into the site configuration, but
   committing that value to git is a security risk.


**Warning!** Due to dependencies on various 3rd party packages, this extension works only if TYPO3 is installed in composer mode. There will be no support for non-composer installations.

## Usage

When you translate the page or content, you will see an additional option for using DeepL for translations.

In the List module each language button is duplicated with a small DeepL overlay on it. Clicking this button will localize and translate
the record using DeepL. Only tables with names starting from `tx_` can be translated (so no luck for `tt_address`, for example).

## Copyright

The extension is copyright (c) by Dmitry Dulepov, 2023.

Contact me by [email](mailto:dmitry.dulepov@gmail.com) if you need a custom TYPO3 extension made for you.
