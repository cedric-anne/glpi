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

use CommonDBTM;
use DBmysql;
use Glpi\Progress\AbstractProgressIndicator;
use Psr\Log\LoggerInterface;

abstract class AbstractPluginMigration
{

    /**
     * Progress indicator.
     */
    protected DBmysql $db;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Progress indicator.
     */
    protected ?AbstractProgressIndicator $progress_indicator;

    /**
     * Current execution results.
     */
    protected MigrationResult $result;

    /**
     * Mapping between plugin items and GLPI core items.
     *
     * @var array<class-string<\CommonDBTM>, array<int, array{itemtype: class-string<\CommonDBTM>, items_id: int}>>
     */
    private array $target_items_mapping = [];

    public function __construct(DBmysql $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Defines the progress indicator.
     */
    final public function setProgressIndicator(AbstractProgressIndicator $progress_indicator): void
    {
        $this->progress_indicator = $progress_indicator;
    }

    /**
     * Execute (or simulate) the plugin migration.
     *
     * @param bool $simulate Whether the process should be simulated or actually executed.
     * @return MigrationResult
     */
    final public function execute(bool $simulate = false): MigrationResult
    {
        $this->result = new MigrationResult();

        $fully_processed = false;
        try {
            if ($this->validatePrerequisites()) {
                $this->db->beginTransaction();

                $fully_processed = $this->processMigration();

                if ($simulate === false && $fully_processed === true) {
                    $this->db->commit();
                } else {
                    $this->db->rollBack();
                }
            }
        } catch (\Throwable $e) {
            $this->result->addError(__('An unexpected error occured.'));

            $this->logger->error($e->getMessage(), context: ['exception' => $e]);

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
        }

        $this->result->setFullyProcessed($fully_processed);

        return $this->result;
    }

    /**
     * Migrate a plugin item.
     *
     * @param string $itemtype                      Target itemtype.
     * @param array $input                          Creation/update input.
     * @param array|null $reconciliation_criteria   Fields used to reconciliate input with a potential existing item.
     *
     * @return CommonDBTM|null
     *      The created/update item, or null if the creation/update failed.
     */
    final protected function migrateItem(
        string $itemtype,
        array $input,
        ?array $reconciliation_criteria = null
    ): ?CommonDBTM {
        $item = \getItemForItemtype($itemtype);
        if ($item === false) {
            throw new \RuntimeException(sprintf('Invalid itemtype `%s`.', $itemtype));
        }

        if ($reconciliation_criteria !== null && $item->getFromDBByCrit($reconciliation_criteria)) {
            // Update the corresponding item.
            $input[$itemtype::getIndexName()] = $item->getID();

            if ($item->update($input) === false) {
                $this->addError(
                    sprintf(
                        __('Unable to update %s "%s" (%d).'),
                        $itemtype::getTypeName(1),
                        $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
                return null;
            } else {
                $this->result->markItemAsUpdated($itemtype, $item->getID());
                $this->addComment(
                    sprintf(
                        __('%s "%s" (%d) has been updated.'),
                        $itemtype::getTypeName(1),
                        $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
            }
        } else {
            // Create a new item.
            if ($item->add($input) === false) {
                $this->addError(
                    sprintf(
                        __('Unable to create %s "%s".'),
                        $itemtype::getTypeName(1),
                        $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                    )
                );
                return null;
            } else {
                $this->result->markItemAsCreated($itemtype, $item->getID());
                $this->addComment(
                    sprintf(
                        __('%s "%s" (%d) has been created.'),
                        $itemtype::getTypeName(1),
                        $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
            }
        }

        return $item;
    }

    /**
     * Map the target item with the given source item.
     *
     * @param class-string<\CommonDBTM> $source_itemtype
     * @param int                       $source_items_id
     * @param class-string<\CommonDBTM> $target_itemtype
     * @param int                       $target_items_id
     *
     * @return void
     */
    final protected function mapItem(
        string $source_itemtype,
        int $source_items_id,
        string $target_itemtype,
        int $target_items_id,
    ): void {
        if (!array_key_exists($source_itemtype, $this->target_items_mapping)) {
            $this->target_items_mapping[$source_itemtype] = [];
        }

        $this->target_items_mapping[$source_itemtype][$source_items_id] = [
            'itemtype' => $target_itemtype,
            'items_id' => $target_items_id,
        ];
    }

    /**
     * Replace any source item key/value by the corresponding target key/value based on the items mapping.
     */
    final protected function replacedMappedValues(array $values): array
    {
        $out_values = $values;

        foreach ($values as $key => $value) {
            $key_matches = [];
            if (
                \preg_match('/^itemtype(?<suffix>(_.+)?)$/', $key, $key_matches) === 1
                && \array_key_exists($items_id_key = 'items_id' . $key_matches['suffix'], $values)
            ) {
                $source_itemtype = $value;
                $source_items_id = $values[$items_id_key];

                $target = $this->getMappedItemTarget($source_itemtype, $source_items_id);
                if ($target !== null) {
                    $out_values[$key] = $target['itemtype'];
                    $out_values[$items_id_key] = $target['items_id'];
                }
            } elseif (\isForeignKeyField($key)) {
                $source_itemtype = \getItemtypeForForeignKeyField($key);
                $source_items_id = $value;

                $target = $this->getMappedItemTarget($source_itemtype, $source_items_id);
                if ($target !== null) {
                    $key_suffix = \preg_replace(
                        '/^' . \getForeignKeyFieldForItemType($source_itemtype) . '/',
                        '',
                        $key
                    );
                    $target_key = \getForeignKeyFieldForItemType($target['itemtype']) . $key_suffix;

                    $out_values[$target_key] = $target['items_id'];
                    unset($out_values[$key]);
                }
            }
        }

        return $out_values;
    }

    /**
     * Return the GLPI core item corresponding to the given plugin item.
     *
     * @return array{itemtype: class-string<\CommonDBTM>, items_id: int}
     */
    private function getMappedItemTarget(string $source_itemtype, int $source_items_id): ?array
    {
        if (
            !array_key_exists($source_itemtype, $this->target_items_mapping)
            || !array_key_exists($source_items_id, $this->target_items_mapping[$source_itemtype])
        ) {
            return null;
        }

        return $this->target_items_mapping[$source_itemtype][$source_items_id];
    }

    /**
     * Add an error message.
     */
    final protected function addError(string $message): void
    {
        $this->result->addError($message);

        if ($this->progress_indicator !== null) {
            $this->progress_indicator->addError($message);
        }
    }

    /**
     * Add a warning message.
     */
    final protected function addWarning(string $message): void
    {
        $this->result->addWarning($message);

        if ($this->progress_indicator !== null) {
            $this->progress_indicator->addWarning($message);
        }
    }

    /**
     * Add an informative message.
     */
    final protected function addComment(string $message): void
    {
        $this->result->addComment($message);

        if ($this->progress_indicator !== null) {
            $this->progress_indicator->addComment($message);
        }
    }

    /**
     * Validates the plugin migration prerequisites.
     * If the prerequisites are not validated, the migration will not be processed.
     *
     * @return bool `true` if the prerequisites are valdiated, `false` otherwise.
     */
    abstract protected function validatePrerequisites(): bool;

    /**
     * Process the migation.
     *
     * A database transation is started before calling this method.
     * This transaction will be commited if the migration is not a simulation and is fully processed,
     * otherwise a rollback will be done.
     *
     * @return bool `true` if the migration has been fully processed, `false` otherwise.
     */
    abstract protected function processMigration(): bool;
}
