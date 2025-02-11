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

use DateTimeImmutable;
use DateTimeInterface;

abstract class AbstractProgressIndicator
{
    /**
     * Operation start datetime.
     */
    private readonly DateTimeInterface $started_at;

    /**
     * Operation last update datetime.
     */
    private DateTimeInterface $updated_at;

    /**
     * Operation end datetime.
     */
    private ?DateTimeInterface $ended_at = null;

    /**
     * Indicates whether the operation failed.
     */
    private bool $failed = false;

    /**
     * Current step.
     */
    private int $current_step = 0;

    /**
     * Max steps.
     */
    private int $max_steps = 0;

    /**
     * Progress message.
     */
    private string $progress_message = '';

    public function __construct()
    {
        $this->started_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    /**
     * Mark the operation as ended.
     */
    final public function finish(): void
    {
        $this->computeUpdatedAt();

        $this->ended_at = new DateTimeImmutable();

        $this->update();
    }

    /**
     * Mark the operation as failed.
     */
    final public function fail(): void
    {
        $this->failed = true;
        $this->finish();
    }

    /**
     * Get the operation start datetime.
     */
    final public function getStartedAt(): DateTimeInterface
    {
        return $this->started_at;
    }

    /**
     * Get the operation last update datetime.
     */
    final public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * Get the operation end datetime.
     */
    final public function getEndedAt(): ?DateTimeInterface
    {
        return $this->ended_at;
    }

    /**
     * Indicates whether the operation failed.
     */
    final public function hasFailed(): bool
    {
        return $this->failed;
    }

    /**
     * Get the current step.
     */
    final public function getCurrentStep(): int
    {
        return $this->current_step;
    }

    /**
     * Define the current step.
     */
    final public function setCurrentStep(int $current_step): void
    {
        $this->computeUpdatedAt();

        $this->current_step = $current_step;
    }

    /**
     * Define the max steps count.
     */
    final public function setMaxSteps(int $max_steps): void
    {
        $this->computeUpdatedAt();

        $this->max_steps = $max_steps;
    }

    /**
     * Get the max steps count.
     */
    final public function getMaxSteps(): int
    {
        return $this->max_steps;
    }

    /**
     * Define the progress message.
     */
    final public function setProgressMessage(string $progress_message): void
    {
        $this->computeUpdatedAt();

        $this->progress_message = $progress_message;
    }

    /**
     * Get the progress message.
     */
    final public function getProgressMessage(): string
    {
        return $this->progress_message;
    }

    /**
     * Add an error message.
     */
    abstract public function addError(string $message);

    /**
     * Add a warning message.
     */
    abstract public function addWarning(string $message);

    /**
     * Add an informative message.
     */
    abstract public function addComment(string $message);

    /**
     * Compute the last update datetime.
     */
    private function computeUpdatedAt(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    /**
     * Update the progress indicator.
     */
    abstract public function update(): void;
}
