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

    public function update(): void
    {
        $this->store();
    }

    /**
     * Return the error messages.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Return the warning messages.
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Return the informative messages.
     */
    public function getComments(): array
    {
        return $this->comments;
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
