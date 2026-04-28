..  include:: /Includes.rst.txt

..  _for-integrators:

===============
For Integrators
===============

Integrators can do the following with the extension:

* Add configuration (API key)
* Manage glossaries

Add configuration
=================

In order to use DeepL, you need to obtain the API key and configure it in the
extension. There are two types of the API key: free and paid.

Free API key is useful for development and testing. It has enough limits for
both these tasks. Free key ends with :yaml:`:fx`.

Paid API key has much higher limits. It is meant to be used in production.

To obtain the API key you need to register with DeepL and get the key in your
DeepL `account settings <https://www.deepl.com/pro-account>`__.

Add the API key to the site configuration under :yaml:`ddDeepl.apiKey`. The
value can use TYPO3 environment placeholders, for example
:yaml:`%env(TYPO3_DEEPL_API_KEY)%`. Environment variables are recommended
because they keep secrets out of the site configuration file.

Legacy TypoScript configuration can still be enabled as a deprecated
temporary fallback. See :ref:`migration`.

Translation errors and retries
==============================

DeepL translations are executed while TYPO3 creates localized records. If a
single content element or related record cannot be translated, the extension
removes that localized record again instead of keeping a translated record with
original text.

Editors see a generic message that some elements were not translated and can
retry the translation later. Technical details, including the table, record
identifier, field identifier and DeepL exception message, are written to the
TYPO3 log.

The timeout for DeepL requests is configured with :yaml:`ddDeepl.timeout`.
The default timeout is :yaml:`30` seconds.

Manage glossaries
=================

Glossaries are a way in DeepL to specify alternative translations to certain
words. Some words can have generic accepted translations, but if your site is
specific to a certain industry or activity, then words of one language may map
to something other than DeepL would typically produce. This is where glossaries
come in. They contain word pairs that map source words to target words.

DeepL supports glossaries for `various language pairs <https://support.deepl.com/hc/en-us/articles/360021634540-About-the-glossary-feature>`__.
There can be multiple glossaries per language combination. The extension
allows you to add, delete, download and remove glossaries, and to see what
glossaries you have.

..  attention::
    Glossaries are added per API key. Make sure that you add them for both
    your free key (development and testing contexts) and paid key
    (production) if you use separate keys for contexts.

The extension allows you to specify the limit on the amount of glossaries per
language pair. While DeepL itself does not impose any limits, it is good to
have that number under control. Use
:yaml:`ddDeepl.maximumNumberOfGlossariesPerLanguage` in site configuration.

.. _how-to-manage-glossaries:

How to manage glossaries
========================

Glossary management happens via the shell command or Backend module.

When the command is executed without :bash:`--site` or :bash:`--root`, the
extension auto-selects the site only if the TYPO3 instance has exactly one
configured site. Instances with multiple sites must pass either :bash:`--site`
or :bash:`--root`. Passing both options is an error.

Here is the output of the shell command's help screen:

..  code-block:: shell

    $ vendor/bin/typo3 deepl:glossary --help
    Description:
      Uploads, downloads, lists, or deletes DeepL glossaries using account settings in the current TYPO3 version

    Usage:
      deepl:glossary [options] [--] <action>
      This command manages DeepL glossaries.

    Usage:
      vendor/bin/typo3 deepl:glossary info
        Fetches information about supported language combinations and existing glossaries.
      vendor/bin/typo3 deepl:glossary add -f file.csv -g "My glossary" -s en-us -t de
        Adds a glossary.
      vendor/bin/typo3 deepl:glossary get -i a1b33a94-ec7e-4ef5-8830-2f7309fab155
        Fetches the glossary by its id. To see the id use the "info" command. Fetched file will be named according to the id.
      vendor/bin/typo3 deepl:glossary delete -i a1b33a94-ec7e-4ef5-8830-2f7309fab155
        Removes the glossary by its id. To see the id use the "info" command.

    Arguments:
      action                                   What to do: add, get, delete glossaries or show the information

    Options:
      -f, --file[=FILE]                        Glossary in CSV format
      -i, --id[=ID]                            Glossary id
      -g, --name[=NAME]                        Glossary name
      -r, --root[=ROOT]                        Root page id to use (if your instance has more than one)
          --site[=SITE]                        Site identifier to use (if your instance has more than one)
      -s, --source-language[=SOURCE-LANGUAGE]  Source language
      -t, --target-language[=TARGET-LANGUAGE]  Target language


Backend module
==============

There is also a Backend module where it is possible to view current API limits
as well as information about uploaded glossaries. You can also upload
glossaries via this module.

The module is available as :guilabel:`Site > DeepL` in the main menu.
