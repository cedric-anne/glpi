<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace tests\units;

use org\bovigo\vfs\vfsStream;

/* Test for inc/update.class.php */

class Update extends \GLPITestCase
{
    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);

        vfsStream::setup(
            'install',
            null,
            [
                'migrations' => [
                    'update_0.90.x_to_9.1.0.php'  => '',
                    'update_10.0.0_to_10.0.1.php' => '',
                    'update_10.0.1_to_10.0.2.php' => '',
                    'update_10.0.2_to_10.0.3.php' => '',
                    'update_10.0.3_to_10.0.4.php' => '',
                    'update_10.0.4_to_10.0.5.php' => '',
                    'update_10.0.5_to_10.0.6.php' => '',
                    'update_9.1.0_to_9.1.1.php'   => '',
                    'update_9.1.1_to_9.1.3.php'   => '',
                    'update_9.1.x_to_9.2.0.php'   => '',
                    'update_9.2.0_to_9.2.1.php'   => '',
                    'update_9.2.1_to_9.2.2.php'   => '',
                    'update_9.2.2_to_9.2.3.php'   => '',
                    'update_9.2.x_to_9.3.0.php'   => '',
                    'update_9.3.0_to_9.3.1.php'   => '',
                    'update_9.3.1_to_9.3.2.php'   => '',
                    'update_9.3.x_to_9.4.0.php'   => '',
                    'update_9.4.0_to_9.4.1.php'   => '',
                    'update_9.4.1_to_9.4.2.php'   => '',
                    'update_9.4.2_to_9.4.3.php'   => '',
                    'update_9.4.3_to_9.4.5.php'   => '',
                    'update_9.4.5_to_9.4.6.php'   => '',
                    'update_9.4.6_to_9.4.7.php'   => '',
                    'update_9.4.x_to_9.5.0.php'   => '',
                    'update_9.5.1_to_9.5.2.php'   => '',
                    'update_9.5.2_to_9.5.3.php'   => '',
                    'update_9.5.3_to_9.5.4.php'   => '',
                    'update_9.5.4_to_9.5.5.php'   => '',
                    'update_9.5.5_to_9.5.6.php'   => '',
                    'update_9.5.6_to_9.5.7.php'   => '',
                    'update_9.5.x_to_10.0.0.php'  => '',
                ],
            ]
        );
    }

    public function testCurrents()
    {
        global $DB;
        $update = new \Update($DB);

        $expected = [
            'dbversion' => GLPI_SCHEMA_VERSION,
            'language'  => 'en_GB',
            'version'   => GLPI_VERSION
        ];
        $this->array($update->getCurrents())->isEqualTo($expected);
    }

    public function testInitSession()
    {
        global $DB;

        $update = new \Update($DB);
        session_destroy();
        $this->variable(session_status())->isIdenticalTo(PHP_SESSION_NONE);

        $update->initSession();
        $this->variable(session_status())->isIdenticalTo(PHP_SESSION_ACTIVE);

        $this->array($_SESSION)->hasKeys([
            'glpilanguage',
            'glpi_currenttime',
        ])->notHasKeys([
            'glpi_use_mode',
            'debug_sql',
            'debug_vars',
            'use_log_in_files'
        ]);
    }

    public function testSetMigration()
    {
        global $DB;
        $update = new \Update($DB);
        $migration = null;
        $this->output(
            function () use (&$migration) {
                $migration = new \Migration(GLPI_VERSION);
            }
        )->isEmpty();

        $this->object($update->setMigration($migration))->isInstanceOf('Update');
    }

    protected function migrationsToDoProvider(): iterable
    {
        $path = vfsStream::url('install/migrations');

        // Validates version normalization (9.1 -> 9.1.0).
        yield [
            'current_version'     => '9.1',
            'force_latest'        => false,
            'expected_migrations' => [
                $path . '/update_9.1.0_to_9.1.1.php'  => 'update910to911',
                $path . '/update_9.1.1_to_9.1.3.php'  => 'update911to913',
                $path . '/update_9.1.x_to_9.2.0.php'  => 'update91xto920',
                $path . '/update_9.2.0_to_9.2.1.php'  => 'update920to921',
                $path . '/update_9.2.1_to_9.2.2.php'  => 'update921to922',
                $path . '/update_9.2.2_to_9.2.3.php'  => 'update922to923',
                $path . '/update_9.2.x_to_9.3.0.php'  => 'update92xto930',
                $path . '/update_9.3.0_to_9.3.1.php'  => 'update930to931',
                $path . '/update_9.3.1_to_9.3.2.php'  => 'update931to932',
                $path . '/update_9.3.x_to_9.4.0.php'  => 'update93xto940',
                $path . '/update_9.4.0_to_9.4.1.php'  => 'update940to941',
                $path . '/update_9.4.1_to_9.4.2.php'  => 'update941to942',
                $path . '/update_9.4.2_to_9.4.3.php'  => 'update942to943',
                $path . '/update_9.4.3_to_9.4.5.php'  => 'update943to945',
                $path . '/update_9.4.5_to_9.4.6.php'  => 'update945to946',
                $path . '/update_9.4.6_to_9.4.7.php'  => 'update946to947',
                $path . '/update_9.4.x_to_9.5.0.php'  => 'update94xto950',
                $path . '/update_9.5.1_to_9.5.2.php'  => 'update951to952',
                $path . '/update_9.5.2_to_9.5.3.php'  => 'update952to953',
                $path . '/update_9.5.3_to_9.5.4.php'  => 'update953to954',
                $path . '/update_9.5.4_to_9.5.5.php'  => 'update954to955',
                $path . '/update_9.5.5_to_9.5.6.php'  => 'update955to956',
                $path . '/update_9.5.6_to_9.5.7.php'  => 'update956to957',
                $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
                $path . '/update_10.0.0_to_10.0.1.php' => 'update1000to1001',
                $path . '/update_10.0.1_to_10.0.2.php' => 'update1001to1002',
                $path . '/update_10.0.2_to_10.0.3.php' => 'update1002to1003',
                $path . '/update_10.0.3_to_10.0.4.php' => 'update1003to1004',
                $path . '/update_10.0.4_to_10.0.5.php' => 'update1004to1005',
                $path . '/update_10.0.5_to_10.0.6.php' => 'update1005to1006',
            ],
        ];

        // Validate version normalization (9.4.1.1 -> 9.4.1).
        yield [
            'current_version'     => '9.4.1.1',
            'force_latest'        => false,
            'expected_migrations' => [
                $path . '/update_9.4.1_to_9.4.2.php'  => 'update941to942',
                $path . '/update_9.4.2_to_9.4.3.php'  => 'update942to943',
                $path . '/update_9.4.3_to_9.4.5.php'  => 'update943to945',
                $path . '/update_9.4.5_to_9.4.6.php'  => 'update945to946',
                $path . '/update_9.4.6_to_9.4.7.php'  => 'update946to947',
                $path . '/update_9.4.x_to_9.5.0.php'  => 'update94xto950',
                $path . '/update_9.5.1_to_9.5.2.php'  => 'update951to952',
                $path . '/update_9.5.2_to_9.5.3.php'  => 'update952to953',
                $path . '/update_9.5.3_to_9.5.4.php'  => 'update953to954',
                $path . '/update_9.5.4_to_9.5.5.php'  => 'update954to955',
                $path . '/update_9.5.5_to_9.5.6.php'  => 'update955to956',
                $path . '/update_9.5.6_to_9.5.7.php'  => 'update956to957',
                $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
                $path . '/update_10.0.0_to_10.0.1.php' => 'update1000to1001',
                $path . '/update_10.0.1_to_10.0.2.php' => 'update1001to1002',
                $path . '/update_10.0.2_to_10.0.3.php' => 'update1002to1003',
                $path . '/update_10.0.3_to_10.0.4.php' => 'update1003to1004',
                $path . '/update_10.0.4_to_10.0.5.php' => 'update1004to1005',
                $path . '/update_10.0.5_to_10.0.6.php' => 'update1005to1006',
            ],
        ];

        // Validate 9.2.2 specific case.
        yield [
            'current_version'     => '9.2.2',
            'force_latest'        => false,
            'expected_migrations' => [
                $path . '/update_9.2.1_to_9.2.2.php'  => 'update921to922',
                $path . '/update_9.2.2_to_9.2.3.php'  => 'update922to923',
                $path . '/update_9.2.x_to_9.3.0.php'  => 'update92xto930',
                $path . '/update_9.3.0_to_9.3.1.php'  => 'update930to931',
                $path . '/update_9.3.1_to_9.3.2.php'  => 'update931to932',
                $path . '/update_9.3.x_to_9.4.0.php'  => 'update93xto940',
                $path . '/update_9.4.0_to_9.4.1.php'  => 'update940to941',
                $path . '/update_9.4.1_to_9.4.2.php'  => 'update941to942',
                $path . '/update_9.4.2_to_9.4.3.php'  => 'update942to943',
                $path . '/update_9.4.3_to_9.4.5.php'  => 'update943to945',
                $path . '/update_9.4.5_to_9.4.6.php'  => 'update945to946',
                $path . '/update_9.4.6_to_9.4.7.php'  => 'update946to947',
                $path . '/update_9.4.x_to_9.5.0.php'  => 'update94xto950',
                $path . '/update_9.5.1_to_9.5.2.php'  => 'update951to952',
                $path . '/update_9.5.2_to_9.5.3.php'  => 'update952to953',
                $path . '/update_9.5.3_to_9.5.4.php'  => 'update953to954',
                $path . '/update_9.5.4_to_9.5.5.php'  => 'update954to955',
                $path . '/update_9.5.5_to_9.5.6.php'  => 'update955to956',
                $path . '/update_9.5.6_to_9.5.7.php'  => 'update956to957',
                $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
                $path . '/update_10.0.0_to_10.0.1.php' => 'update1000to1001',
                $path . '/update_10.0.1_to_10.0.2.php' => 'update1001to1002',
                $path . '/update_10.0.2_to_10.0.3.php' => 'update1002to1003',
                $path . '/update_10.0.3_to_10.0.4.php' => 'update1003to1004',
                $path . '/update_10.0.4_to_10.0.5.php' => 'update1004to1005',
                $path . '/update_10.0.5_to_10.0.6.php' => 'update1005to1006',
            ],
        ];

        // Dev versions always triggger latest migration
        foreach (['dev', 'alpha', 'alpha3', 'beta', 'beta1', 'rc', 'rc2'] as $version_suffix) {
            // "pre-release" version suffix should always trigger migration replay
            // when source version is a previous version
            yield [
                'current_version'     => sprintf('10.0.5-%s', $version_suffix),
                'force_latest'        => false,
                'expected_migrations' => [
                    $path . '/update_10.0.4_to_10.0.5.php' => 'update1004to1005',
                    $path . '/update_10.0.5_to_10.0.6.php' => 'update1005to1006',
                ],
            ];
            // and when source version is a latest version
            yield [
                'current_version'     => sprintf('10.0.6-%s', $version_suffix),
                'force_latest'        => false,
                'expected_migrations' => [
                    $path . '/update_10.0.5_to_10.0.6.php' => 'update1005to1006',
                ],
            ];

            // Force latests does not duplicate latest in list
            yield [
                'current_version'     => sprintf('10.0.6-%s', $version_suffix),
                'force_latest'        => true,
                'expected_migrations' => [
                    $path . '/update_10.0.5_to_10.0.6.php' => 'update1005to1006',
                ],
            ];
        }

        // Validate that list is empty when version matches latest version
        yield [
            'current_version'     => '10.0.6',
            'force_latest'        => false,
            'expected_migrations' => [
            ],
        ];

        // Validate force latest
        yield [
            'current_version'     => '10.0.6',
            'force_latest'        => true,
            'expected_migrations' => [
                $path . '/update_10.0.5_to_10.0.6.php' => 'update1005to1006',
            ],
        ];
    }

    /**
     * @dataProvider migrationsToDoProvider
     */
    public function testGetMigrationsToDo(string $current_version, bool $force_latest, array $expected_migrations)
    {
        $class = new \ReflectionClass(\Update::class);
        $method = $class->getMethod('getMigrationsToDo');
        $method->setAccessible(true);

        global $DB;
        $update = new \Update($DB, [], vfsStream::url('install/migrations'));
        $this->array($method->invokeArgs($update, [$current_version, null, $force_latest]))->isIdenticalTo($expected_migrations);
    }

    protected function hasMigrationsToDoProvider(): iterable
    {
        yield '9.1 specific case' => [
            'current_version' => '9.1',
            'target_version'  => '9.1.0',
            'expected'        => false,
        ];

        yield '9.2.3 to 9.5.2' => [
            'current_version' => '9.2.3',
            'target_version'  => '9.5.2',
            'expected'        => true,
        ];

        yield '10.0.5 to 10.0.6' => [
            'current_version' => '10.0.5',
            'target_version'  => '10.0.6',
            'expected'        => true,
        ];

        yield '10.0.6' => [
            'current_version' => '10.0.6',
            'target_version'  => '10.0.6',
            'expected'        => false,
        ];

        foreach (['alpha', 'alpha3', 'beta', 'beta1', 'rc', 'rc2'] as $version_suffix) {
            yield "when upgrading from a $version_suffix, always execute migrations to its stable version" => [
                'current_version' => sprintf('10.0.5-%s', $version_suffix),
                'target_version'  => '10.0.5',
                'expected'        => true,
            ];
        }
    }

    /**
     * @dataProvider hasMigrationsToDoProvider
     */
    public function testHasMigrationsToDo(string $current_version, string $target_version, bool $expected): void
    {
        global $DB;

        $update = new \Update($DB, [], vfsStream::url('install/migrations'));

        $class = new \ReflectionClass(\Update::class);
        $property = $class->getProperty('current_version');
        $property->setAccessible(true);
        $property->setValue($update, $current_version);

        $update->setTargetVersion($target_version);

        $this->boolean($update->hasMigrationsToDo())->isIdenticalTo($expected);
    }

    protected function nextVersionProvider(): iterable
    {

        $mapping = [
            '9.1'   => '9.1.1', // 9.1 specific case
            '9.1.1' => '9.1.3', // there is no 9.1.2 migration file
            '9.1.3' => '9.2.0',
            // ...
            '9.2.4' => '9.3.0',
            // ...
            '9.4.1.1' => '9.4.2', // 9.4.1.1 specific case
            // ...
            '10.0.3' => '10.0.4',
            '10.0.4' => '10.0.5',
            '10.0.5' => '10.0.6',
        ];
        foreach ($mapping as $source => $target) {
            yield $source => [
                'current_version' => $source,
                'next_version'    => $target,
            ];
        }

        // Dev versions always triggger latest migration
        foreach (['dev', 'alpha', 'alpha3', 'beta', 'beta1', 'rc', 'rc2'] as $version_suffix) {
            yield 'next version of a pre-release should always be the corresponding stable version' => [
                'current_version' => sprintf('10.0.6-%s', $version_suffix),
                'next_version'    => '10.0.6',
            ];
        }

        // Validate that list is empty when version matches latest version
        yield 'next version is null if the current version is the latest' => [
            'current_version' => '10.0.6',
            'next_version'    => null,
        ];
    }

    /**
     * @dataProvider nextVersionProvider
     */
    public function testGetNextVersion(string $current_version, ?string $next_version)
    {
        global $DB;

        $update = new \Update($DB, [], vfsStream::url('install/migrations'));

        $class = new \ReflectionClass(\Update::class);
        $property = $class->getProperty('current_version');
        $property->setAccessible(true);
        $property->setValue($update, $current_version);

        $this->variable($update->getNextVersion())->isIdenticalTo($next_version);
    }
}
