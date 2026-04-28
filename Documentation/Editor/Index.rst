..  include:: /Includes.rst.txt

..  _for-editors:

===========
For Editors
===========

Editors can translate content and records using DeepL.

Start the regular TYPO3 localization wizard from the Page module or from a
record localization button. Select DeepL as the localization handler when the
wizard asks how the localization should be processed.

DeepL does not add a separate localization button. It is available as an
option in the standard TYPO3 localization wizard.

Records will get translations if the combination of source and target language
is supported by DeepL.

Large translations
==================

When a page contains many content elements, the localization wizard shows a
warning before the DeepL translation starts. Translating many elements can take
longer and may increase the chance of DeepL API timeouts.

If some elements cannot be translated due to DeepL errors, they are removed
from the localized page again. You can start the localization wizard later and
try translating those elements again.
