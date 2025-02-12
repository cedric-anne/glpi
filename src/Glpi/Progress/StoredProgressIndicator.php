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

final class StoredProgressIndicator extends AbstractProgressIndicator
{
    /**
     * Storage service used to store the currnet indicator.
     */
    private readonly ProgressStorage $progress_storage;

    /**
     * Storage key.
     */
    private readonly string $storage_key;

    /**
     * Error messages.
     *
     * @var string[]
     */
    private array $errors = [];

    /**
     * Warning messages.
     *
     * @var string[]
     */
    private array $warnings = [];

    /**
     * Informative messages.
     *
     * @var string[]
     */
    private array $comments = [];

    /**
     * Debug messages.
     *
     * @var string[]
     */
    private array $debug_messages = [];

    public function __construct(ProgressStorage $progress_storage, string $storage_key)
    {
        parent::__construct();

        $this->progress_storage = $progress_storage;
        $this->storage_key      = $storage_key;

        $this->store();
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function addComment(string $message): void
    {
        $this->comments[] = $message;
    }

    public function addDebugMessage(string $message): void
    {
        $this->debug_messages[] = $message;
    }

    public function update(): void
    {
        $this->store();
    }

    /**
     * Pull the error messages.
     */
    public function pullErrors(): array
    {
        $messages = $this->errors;
        $this->errors = [];
        return $messages;
    }

    /**
     * Return the warning messages.
     */
    public function pullWarnings(): array
    {
        $messages = $this->warnings;
        $this->warnings = [];
        return $messages;
    }

    /**
     * Return the informative messages.
     */
    public function pullComments(): array
    {
        $messages = $this->comments;
        $this->comments = [];
        return $messages;
    }

    /**
     * Get the sorage key.
     */
    public function getStorageKey(): string
    {
        return $this->storage_key;
    }

    /**
     * Store the indicator into the storage.
     */
    private function store(): void
    {
        $this->progress_storage->save($this);
    }
}
