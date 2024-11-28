<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Controller;

use Config;
use DB;
use Glpi\Http\Firewall;
use Glpi\Progress\ProgressStorage;
use Glpi\Security\Attribute\SecurityStrategy;
use Migration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Toolbox;
use Update;
use Glpi\Cache\CacheManager;

class InstallController extends AbstractController
{
    public const PROGRESS_KEY_INIT_DATABASE = 'init_database';
    public const PROGRESS_KEY_UPDATE_DATABASE = 'update_database';

    public function __construct(
        private readonly ProgressStorage $progress_storage,
    ) {
    }

    #[Route("/Install/InitDatabase", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function initDatabase(): Response
    {
        ini_set('max_execution_time', '300'); // Allow up to 5 minutes to prevent unexpected timeout

        $progress_storage = $this->progress_storage;

        $this->progress_storage->startProgress(self::PROGRESS_KEY_INIT_DATABASE);

        return new StreamedResponse(function () use ($progress_storage) {
            try {
                $progress_callback = static function (int $current, ?int $max = null, ?string $message = null) use ($progress_storage) {
                    $progress = $progress_storage->getCurrentProgress(self::PROGRESS_KEY_INIT_DATABASE);
                    $progress->setCurrent($current);
                    if ($max !== null) {
                        $progress->setMax($max);
                    }
                    if ($message !== null) {
                        $progress->setMessage($message);
                    }
                    $progress_storage->save($progress);
                };
                Toolbox::createSchema($_SESSION["glpilanguage"], null, $progress_callback);
            } catch (\Throwable $e) {
                $progress_storage->abortProgress(self::PROGRESS_KEY_INIT_DATABASE);
                // Try to remove the config file, to be able to restart the process.
                @unlink(GLPI_CONFIG_DIR . '/config_db.php');
            }

            $this->progress_storage->endProgress(self::PROGRESS_KEY_INIT_DATABASE);
        });
    }

    #[Route("/Install/UpdateDatabase", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function updateDatabase(): Response
    {
        ini_set('max_execution_time', '300'); // Allow up to 5 minutes to prevent unexpected timeout

        if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
            throw new \RuntimeException('Missing database configuration file.');
        } else {
            include_once(GLPI_CONFIG_DIR . '/config_db.php');
            if (!\class_exists(DB::class)) {
                throw new \RuntimeException('Invalid database configuration file.');
            }
        }

        /** @var \DBmysql $DB */
        global $DB;
        $DB = new DB();
        $DB->disableTableCaching(); // Prevents issues on fieldExists upgrading from old versions

        // Required, at least, by usage of `Plugin::unactivateAll()`
        // FIXME: We should not have to load the configuration before running the update process.
        Config::loadLegacyConfiguration();

        /** @var \Migration $migration */
        global $migration; // Migration scripts are using global `$migration`
        $migration = new Migration(GLPI_VERSION);

        $update = new Update($DB);
        $update->setMigration($migration);

        $progress_storage = $this->progress_storage;

        $this->progress_storage->startProgress(self::PROGRESS_KEY_UPDATE_DATABASE);

        return new StreamedResponse(function () use ($update, $progress_storage) {
            try {
                $current_step_message = '';
                $progress_callback = static function (int $current, ?int $max = null, ?string $message = null) use ($progress_storage, &$current_step_message) {
                    $progress = $progress_storage->getCurrentProgress(self::PROGRESS_KEY_UPDATE_DATABASE);
                    $progress->setCurrent($current);
                    if ($max !== null) {
                        $progress->setMax($max);
                    }
                    if ($message !== null) {
                        $progress->setMessage($message);
                        $current_step_message = $message;
                    }
                    $progress_storage->save($progress);
                };

                $output_callback = static function (string $message, ?string $style) use ($progress_storage, &$current_step_message) {
                    $progress = $progress_storage->getCurrentProgress(self::PROGRESS_KEY_UPDATE_DATABASE);
                    $progress->setMessage($current_step_message . "\n" . $message);
                    $progress_storage->save($progress);
                };

                $update->doUpdates(
                    $update->getCurrents()['version'],
                    progress_callback: $progress_callback,
                    output_callback: $output_callback,
                );

                // Force cache cleaning to ensure it will not contain stale data
                (new CacheManager())->resetAllCaches();

            } catch (\Throwable $e) {
                echo $e->getMessage();
                    $progress = $progress_storage->getCurrentProgress(self::PROGRESS_KEY_UPDATE_DATABASE);
                    $progress->setData($e->getMessage());
                    $progress_storage->save($progress);
                $progress_storage->abortProgress(self::PROGRESS_KEY_UPDATE_DATABASE);
            }

            $this->progress_storage->endProgress(self::PROGRESS_KEY_UPDATE_DATABASE);
        });
    }
}
