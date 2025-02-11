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
     * Whether the migration has been fully processed.
     */
    private bool $is_finished;

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
     * IDs of created items.
     * @var array<class-string<\CommonDBTM>, array<int, int>>
     */
    private array $created_items_ids = [];

    /**
     * IDs of updated items.
     * @var array<class-string<\CommonDBTM>, array<int, int>>
     */
    private array $updated_items_ids = [];

    /**
     * IDs of ignored items.
     * @var array<class-string<\CommonDBTM>, array<int, int>>
     */
    private array $ignored_items_ids = [];

    /**
     * Indicates whether the migration has been fully processed.
     */
    public function isFinished(): bool
    {
        return $this->is_finished;
    }

    /**
     * Defines whether the migration has been fully processed.
     */
    public function setFinished(bool $is_finished): void
    {
        $this->is_finished = $is_finished;
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

    /**
     * Mark an item as created.
     *
     * @param class-string<\CommonDBTM> $itemtype
     * @param int $id
     */
    public function markItemAsCreated(string $itemtype, int $id): void
    {
        if (!\array_key_exists($itemtype, $this->created_items_ids)) {
            $this->created_items_ids[$itemtype] = [];
        }

        $this->created_items_ids[$itemtype][] = $id;
    }

    /**
     * Return the IDs of the created items.
     *
     * @return array<class-string<\CommonDBTM>, array<int, int>>
     */
    public function getCreatedItemsIds(): array
    {
        return $this->created_items_ids;
    }

    /**
     * Mark an item as updated.
     *
     * @param class-string<\CommonDBTM> $itemtype
     * @param int $id
     */
    public function markItemAsUpdated(string $itemtype, int $id): void
    {
        if (!\array_key_exists($itemtype, $this->updated_items_ids)) {
            $this->updated_items_ids[$itemtype] = [];
        }

        $this->updated_items_ids[$itemtype][] = $id;
    }

    /**
     * Return the IDs of the updated items.
     *
     * @return array<class-string<\CommonDBTM>, array<int, int>>
     */
    public function getUpdatedItemsIds(): array
    {
        return $this->updated_items_ids;
    }

    /**
     * Mark an item as ignored.
     *
     * @param class-string<\CommonDBTM> $itemtype
     * @param int $id
     */
    public function markItemAsIgnored(string $itemtype, int $id): void
    {
        if (!\array_key_exists($itemtype, $this->ignored_items_ids)) {
            $this->ignored_items_ids[$itemtype] = [];
        }

        $this->ignored_items_ids[$itemtype][] = $id;
    }

    /**
     * Return the IDs of the ignored items.
     *
     * @return array<class-string<\CommonDBTM>, array<int, int>>
     */
    public function getIgnoredItemsIds(): array
    {
        return $this->ignored_items_ids;
    }
}
