<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

class NetworkEquipmentModelStencil extends Stencil
{
    public static function getTypeName($nb = 0): string
    {
        return __('Graphical slot definition');
    }

    public function getPicturesFields(): array
    {
        return ['picture_front', 'picture_rear'];
    }

    public function getParams(bool $editor): array
    {
        if ($editor) {
            return [
                'nb_zones_label' => __('Set number of ports'),
                'define_zones_label' => __('Define port data in image'),
                'zone_label' => __('Port Label'),
                'zone_number_label' => __('Port Number'),
                'save_zone_data_label' => __('Save port data'),
                'add_zone_label' => __('Add a new port'),
                'remove_zone_label' => __('Remove last port'),
            ];
        } else {
            return [
                'anchor_id' => 'port_number_',
            ];
        }
    }

    public function getMaxZoneNumber(): int
    {
        return 256;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        $stencil = Stencil::getStencilFromItem($item);
        if ($stencil != null) {
            $stencil->displayStencilEditor();
            return true;
        }

        return false;
    }

    public function getZoneLabel(bool $editor, array $zone): string
    {
        $zoneLabel = parent::getZoneLabel($editor, $zone);
        if (!$editor) {
            $portInformation = self::getPortInformation($zone);
            $statusHtml = TemplateRenderer::getInstance()->render('stencil/parts/port/status.html.twig', [
                'port' => $portInformation,
                'with_text' => false,
            ]);
            $zoneLabel .= $statusHtml;
        }
        return $zoneLabel;
    }

    public function getZonePopover(bool $editor, array $zone): string
    {
        $zonePopover = parent::getZonePopover($editor, $zone);
        if (!$editor) {
            $portInformation = self::getPortInformation($zone);
            $popoverHtml = TemplateRenderer::getInstance()->render('stencil/parts/port/popover.html.twig', [
                'port' => $portInformation,
            ]);
            $zonePopover .= $popoverHtml;
        }
        return $zonePopover;
    }

    private function getPortInformation(array $port): array
    {
        $networkPort = new NetworkPort();

        $port['ifstatus'] = -1;
        if (
            $networkPort->getFromDBByCrit([
                'logical_number' => $port['number'],
                'items_id'  => $this->getStencilItem()->getID(),
                'itemtype'  => $this->getStencilItem()::class,
                'is_deleted' => 0,
            ]) && $networkPort->fields['ifstatus']
        ) {
            $port['ifstatus'] = $networkPort->fields['ifstatus'];
        }

        return $port;
    }

    /**
     * Validate that the given zones array contains valid values.
     *
     * @param mixed $zones
     * @return bool
     */
    protected function validateZoneArray(mixed $zones): bool
    {
        if (!is_array($zones)) {
            return false;
        }

        $expected_properties = [
            'side',
            'image',
            'label',
            'number',
            'selection',
            'x_percent',
            'y_percent',
            'width_percent',
            'height_percent',
        ];

        $is_valid = true;

        foreach ($zones as $zone_index => $zone_specs) {
            if (!is_int($zone_index) && !ctype_digit($zone_index)) {
                $is_valid = false;
                break;
            }

            if (!is_array($zone_specs)) {
                $is_valid = false;
                break;
            }

            $actual_properties = array_keys($zone_specs);
            if (
                array_diff($actual_properties, $expected_properties) !== []
                || array_diff($expected_properties, $actual_properties) !== []
            ) {
                // missing or unexpected properties
                $is_valid = false;
                break;
            }

            if (!in_array($zone_specs['side'], [Rack::FRONT, Rack::REAR], true)) {
                $is_valid = false;
                break;
            }

            if (
                !is_array($zone_specs['image'])
                || !array_is_list($zone_specs['image'])
                || count($zone_specs['image']) !== 6
                || count(array_filter($zone_specs['image'], fn($val) => !is_numeric($val))) > 0
            ) {
                // `image` is the transform matrix used by cropper.js and should contain a list of 6 numeric values
                $is_valid = false;
                break;
            }

            if (!is_string($zone_specs['label'])) {
                $is_valid = false;
                break;
            }

            if (!is_int($zone_specs['number']) && !ctype_digit($zone_specs['number'])) {
                $is_valid = false;
                break;
            }

            // `selection` should contain x, y, width and height int properties
            $expected_selection_properties = ['x', 'y', 'width', 'height'];
            $selection = is_array($zone_specs['selection'])
                ? array_filter(
                    $zone_specs['selection'],
                    fn($key) => in_array($key, $expected_selection_properties, true),
                    ARRAY_FILTER_USE_KEY
                )
                : [];

            foreach ($expected_selection_properties as $key) {
                if (
                    !array_key_exists($key, $selection)
                    || (!is_int($selection[$key]) && !ctype_digit($selection[$key]))
                ) {
                    $is_valid = false;
                    break 2;
                }
            }

            // `selection` should contain x, y, width and height numeric (int/float) properties
            $expected_coord_properties = ['x_percent', 'y_percent', 'width_percent', 'height_percent'];
            foreach ($expected_coord_properties as $key) {
                if (
                    !array_key_exists($key, $zone_specs)
                    || !is_numeric($zone_specs[$key])
                ) {
                    $is_valid = false;
                    break 2;
                }
            }
        }

        return $is_valid;
    }
}
