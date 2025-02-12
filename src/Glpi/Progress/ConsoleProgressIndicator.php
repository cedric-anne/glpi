<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Progress;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleProgressIndicator extends AbstractProgressIndicator
{
    /**
     * Console output.
     */
    private readonly OutputInterface $output;

    private readonly ProgressBar $progress_bar;

    public function __construct(OutputInterface $output)
    {
        parent::__construct();

        $this->output = $output;

        $this->progress_bar = new ProgressBar($output);
        $this->progress_bar->setFormat('[%bar%] %percent:3s%%' . PHP_EOL . '<comment>%message%</comment>' . PHP_EOL);
        $this->progress_bar->setMessage(''); // Empty message on iteration start
        $this->progress_bar->start();
    }

    public function addError(string $message): void
    {
        $this->outputMessage(
            '<error>' . $message . '</error>',
            OutputInterface::VERBOSITY_QUIET
        );
    }

    public function addWarning(string $message): void
    {
        $this->outputMessage(
            '<comment>' . $message . '</comment>',
            OutputInterface::VERBOSITY_QUIET
        );
    }

    public function addComment(string $message): void
    {
        $this->outputMessage(
            '<info>' . $message . '</info>',
            OutputInterface::VERBOSITY_NORMAL
        );
    }

    public function addDebugMessage(string $message): void
    {
        $this->outputMessage(
            '<comment>' . $message . '</comment>',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        );
    }

    public function update(): void
    {
        $this->progress_bar->setMaxSteps($this->getMaxSteps());
        $this->progress_bar->setProgress($this->getCurrentStep());
        $this->progress_bar->setMessage($this->getProgressMessage());

        if ($this->getEndedAt() !== null) {
            $this->progress_bar->finish();
        }
    }

    /**
     * Output a message.
     *
     * @param string $message
     * @param \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_* $verbosity
     */
    private function outputMessage(
        string $message,
        int $verbosity
    ) {
        if ($verbosity > $this->output->getVerbosity()) {
            return; // Do nothing if message will not be output due to its too high verbosity
        }

        $this->progress_bar->clear();
        $this->output->writeln(
            $message,
            $verbosity
        );
        $this->progress_bar->display();
    }
}
