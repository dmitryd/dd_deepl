<?php

namespace Dmitryd\DdDeepl\Command;

/***************************************************************
*  Copyright notice
*
*  (c) 2023 Dmitry Dulepov <dmitry.dulepov@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use DeepL\DeepLException;
use Dmitryd\DdDeepl\Configuration\Configuration;
use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use LucidFrame\Console\ConsoleTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a console command to manage DeepL glossaries.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class ManageDeeplGlossariesCommand extends Command
{
    protected const DATE_FORMAT = 'd.m.Y H:i';

    protected Configuration $configuration;

    protected DeeplTranslationService $deeplTranslationService;

    protected InputInterface $input;

    protected OutputInterface $output;

    /** @inheritDoc */
    protected function configure()
    {
        if (!defined('TAB')) {
            define('TAB', "\t");
        }

        $this->setDescription('Manage DeepL glossaries');

        $this->addUsage(
            'This command manages DeepL glossaries.' . LF .
            LF .
            'Usage:' . LF .
            '  vendor/bin/typo3 deepl:glossary info' . LF .
            '    Fetches information about supported language combinations and existing glossaries.' . LF .
            '  vendor/bin/typo3 deepl:glossary add -f file.csv -g "My glossary" -s en-us -t de' . LF .
            '    Adds a glossary.' . LF .
            '  vendor/bin/typo3 deepl:glossary get -i a1b33a94-ec7e-4ef5-8830-2f7309fab155' . LF .
            '    Fetches the glossary by its id. To see the id use the "info" command. Fetched file will be named according to the id.' . LF .
            '  vendor/bin/typo3 deepl:glossary delete -i a1b33a94-ec7e-4ef5-8830-2f7309fab155' . LF .
            '    Removes the glossary by its id. To see the id use the "info" command.' . LF .
            LF
        );
        $this->addArgument('action', InputArgument::REQUIRED, 'What to do: add, get, delete glossaries or show the information');

        $this->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Glossary in CSV format');
        $this->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'Glossary id');
        $this->addOption('name', 'g', InputOption::VALUE_OPTIONAL, 'Glossary name');
        $this->addOption('root', 'r', InputOption::VALUE_OPTIONAL, 'Root page id to use (if your instance has more than one)');
        $this->addOption('source-language', 's', InputOption::VALUE_OPTIONAL, 'Source language');
        $this->addOption('target-language', 't', InputOption::VALUE_OPTIONAL, 'Target language');
    }

    /** @inheritDoc */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->setRootPageId();

        $this->configuration = GeneralUtility::makeInstance(Configuration::class);
        $this->deeplTranslationService = GeneralUtility::makeInstance(DeeplTranslationService::class);

        if (!$this->configuration->isConfigured()) {
            $this->output->writeln('DeepL is not confiured for this site');
            $result = 1;
        } else {
            $action = $input->getArgument('action');
            $method = $action . 'Action';
            if (method_exists($this, $method)) {
                $result = $this->{$method}();
            } else {
                $this->output->writeln('Unknown command: ' . $action);
                $result = 2;
            }
        }

        return $result;
    }

    /**
     * Fetches the glossary.
     *
     * @return int
     */
    protected function getAction(): int
    {
        $glossaryId = $this->input->getOption('id');
        if (empty($glossaryId)) {
            $this->output->writeln('Glossary id is required (-i option). Try --help for more information.');
            return 1;
        }

        try {
            $glossaryEntries = $this->deeplTranslationService->getGlossaryEntries($glossaryId);
            $glossaryInfo = $this->deeplTranslationService->getGlossary($glossaryId);
        } catch (DeepLException $exception) {
            $this->output->writeln('Cannot find such glossary. Use the "info" command to find the correct id.');
            return 1;
        }

        // Note: glossary id is a GUID. No sanitization here is needed.
        $fileName = getcwd() . DIRECTORY_SEPARATOR . $glossaryId . '.csv';
        $file = fopen($fileName, 'wt+');
        foreach ($glossaryEntries as $sourceWord => $targetWord) {
            fputcsv(
                $file,
                [
                    $sourceWord,
                    $targetWord,
                ]
            );
        }
        fclose($file);

        $table = new ConsoleTable();
        $table->addRow(['Name:', $glossaryInfo->name])
            ->addRow(['Id:', $glossaryInfo->glossaryId])
            ->addRow(['Source:', $glossaryInfo->sourceLang])
            ->addRow(['Target:', $glossaryInfo->targetLang])
            ->addRow(['Created:', $glossaryInfo->creationTime->format(self::DATE_FORMAT)])
            ->addRow(['Words:', $glossaryInfo->entryCount])
            ->addRow(['Saved to:', $fileName])
        ;
        $this->output->writeln($table->getTable());

        return 0;
    }

    /**
     * Shows information about glossaries.
     *
     * @return int
     * @throws \DeepL\DeepLException
     */
    protected function infoAction(): int
    {
        $this->listLanguageCombinations();
        $this->listExistingGlossaries();

        return 0;
    }

    /**
     * Shows existing glossaries.
     *
     * @throws \DeepL\DeepLException
     */
    protected function listExistingGlossaries(): void
    {
        $glossaries = $this->deeplTranslationService->listGlossaries();

        if (count($glossaries) === 0) {
            $this->output->writeln('Currently you have no glossaries.');
        } else {
            $this->output->writeln('You have the following glossaries:');
            $table = new ConsoleTable();
            $table->addHeader('Name')
                ->addHeader('Source')
                ->addHeader('Target')
                ->addHeader('ID')
                ->addHeader('Word count')
                ->addHeader('Added')
            ;
            foreach ($glossaries as $glossary) {
                $table->addRow()
                    ->addColumn($glossary->name)
                    ->addColumn($glossary->sourceLang)
                    ->addColumn($glossary->targetLang)
                    ->addColumn($glossary->glossaryId)
                    ->addColumn($glossary->entryCount)
                    ->addColumn($glossary->creationTime->format(self::DATE_FORMAT))
                ;
            }
            $this->output->writeln($table->getTable());
        }

        $this->output->writeln('');
    }

    /**
     * Lists possible language combinations for glossaries.
     *
     * @throws \DeepL\DeepLException
     */
    protected function listLanguageCombinations(): void
    {
        $languages = $this->deeplTranslationService->getGlossaryLanguages();

        $table = new ConsoleTable();
        $table->addHeader('Source language')->addHeader('Target language');
        foreach ($languages as $language) {
            $table->addRow()
                ->addColumn($language->sourceLang)
                ->addColumn($language->targetLang)
            ;
        }

        $this->output->writeln('Glossaries can be created for the following language combinations:');
        $this->output->writeln($table->getTable());
        $this->output->writeln('');
    }

    /**
     * Sets the root page for the configuration to use.
     */
    protected function setRootPageId(): void
    {
        $pid = $this->input->getOption('root');
        if (!empty($pid)) {
            $_GET['id'] = $pid;
        }
    }
}
