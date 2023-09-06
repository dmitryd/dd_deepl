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

In order to use DeepL, you need to obtain the API key and configure it in the extension. There are two types of the API key: free and paid.

Free API key is great for the development and testing. It has enough limits for both these tasks. Free key ends with `:fx`.

Paid API key has much higher limits. It is meant to be used in production.

To obtain the API key you need to register with DeepL and get the key in your DeepL `account settings <https://www.deepl.com/pro-account>`__.

You can add API key via a :ref:`TypoScript constant <_configuration-typoscript-constants:>` or via the environment variable named `TYPO3_DEEPL_API_KEY`.
Environment is recommended because you can easily set up different keys for different contexts: development, testing and production.

Manage glossaries
=================

Glossaries is a way in DeepL to specify alternative translations to certain words. Some words can have generic accepted translations
but if your site is very specific to a certain industry or activity kind, then words of one language may map to something other then
DeepL would typically produce. This is where glossaries come in. They contain word pairs that map source words to target words.

DeepL supports glossaries for `various language pairs <https://support.deepl.com/hc/en-us/articles/360021634540-About-the-glossary-feature>`__.
There can be multiple glossaries per language combination. The extension allows you to add, delete, download and remove glossaries as well
as see what glossaries you have.

.. attention::
   Glossaries are added per API key. Make sure that you add them for both your free key (development and testing contexts) as well as
   paid key (production) if you use separate keys for contexts.

The extension allows you to specify the limit on the amount of glossaries per language pair. While DeepL itself does not impose any limits,
it is good to have that number under control. Refer to the :ref:`TypoScript configuration <__configuration-typoscript>` reference for more
information

How to manage glossaries
========================

Glossary management happens via the shell command or Backend module.

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
      -s, --source-language[=SOURCE-LANGUAGE]  Source language
      -t, --target-language[=TARGET-LANGUAGE]  Target language


Backend module
==============

There is also Backend module where it is possible to view current API limits as well as information about uploaded glossaries.
You can also upload glossaries via this module.

The module is avaiable as `Site > DeepL` in the main menu.
