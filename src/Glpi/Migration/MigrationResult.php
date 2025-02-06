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

namespace Glpi\Migration;

final class MigrationResult
{
    /**
     * Whether the migration was successful.
     */
    private bool $is_success;

    /**
     * Migration error messages.
     *
     * @var string[]
     */
    private array $errors = [];

    /**
     * Migration warning messages.
     *
     * @var string[]
     */
    private array $warnings = [];

    /**
     * Migration informative messages.
     *
     * @var string[]
     */
    private array $comments = [];

    /**
     * Indicates whether the migration was successful.
     */
    public function isSuccess(): bool
    {
        return $this->is_success;
    }

    /**
     * Defines whether the migration was successful.
     */
    public function setSuccess(bool $is_success): void
    {
        $this->is_success = $is_success;
    }

    /**
     * Add an error message.
     */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * Return the error messages.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add a warning message.
     */
    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    /**
     * Return the warning messages.
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Add an informative message.
     */
    public function addComment(string $message): void
    {
        $this->comments[] = $message;
    }

    /**
     * Return the informative messages.
     */
    public function getComments(): array
    {
        return $this->comments;
    }
}
