<?php
/**
 * @package   ShackLocations
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 * @copyright 2013-2017 John Pitchers <john@viperfish.com.au> - http://viperfish.com.au
 * @copyright 2018 Joomlashack <https://www.joomlashack.com
 *
 * This file is part of ShackLocations.
 *
 * ShackLocations is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * ShackLocations is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ShackLocations.  If not, see <http://www.gnu.org/licenses/>.
 */

use Joomla\CMS\Form\Form;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die();

class FocalpointModellocation extends JModelAdmin
{
    protected $text_prefix = 'COM_FOCALPOINT';

    /**
     * @var CMSObject
     */
    protected $item = null;

    public function getTable($type = 'Location', $prefix = 'FocalpointTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * @param array $data
     * @param bool  $loadData
     *
     * @return Form
     * @throws Exception
     */
    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm(
            'com_focalpoint.location',
            'location',
            array('control' => 'jform', 'load_data' => $loadData)
        );

        return $form;
    }

    /**
     * @return array|CMSObject|mixed
     * @throws Exception
     */
    protected function loadFormData()
    {
        $app  = JFactory::getApplication();
        $data = $app->getUserState('com_focalpoint.edit.location.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * @param int $pk
     *
     * @return CMSObject
     */
    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk)) {
            $item->description = trim($item->fulldescription) == ''
                ? $item->description
                : $item->description . '<hr id="system-readmore" />' . $item->fulldescription;

            $item->metadata         = json_decode($item->metadata, true);
            $item->othertypes       = json_decode($item->othertypes, true);
            $item->customfieldsdata = json_decode($item->customfieldsdata, true);
        }

        if (empty($item->id)) {
            $item->created_by = JFactory::getUser()->id;
        }

        return $item;
    }

    /**
     * @param Table $table
     *
     * @return void
     */
    protected function prepareTable($table)
    {
        $table->alias = JFilterOutput::stringURLSafe($table->alias ?: $table->title);

        if (!$table->id) {
            $table->ordering = $table->getNextOrder();
        }

        $parts = preg_split('#(<hr\s+id="system-readmore"\s*/>)#', $table->description);
        if (count($parts) == 2) {
            $table->fulldescription = trim(array_pop($parts));
            $table->description     = trim(array_pop($parts));

        } else {
            $table->fulldescription = '';
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function save($data)
    {
        if (empty($data['othertypes'])) {
            $data['othertypes'] = array();
        }

        if (parent::save($data)) {
            $id = $data['id'] ?: $this->getDbo()->insertid();
            $this->updateTypes($id, $data);

            return true;
        }

        return false;
    }

    /**
     * @param int   $id
     * @param array $data
     *
     * @return void
     */
    protected function updateTypes($id, array $data)
    {
        $db = $this->getDbo();

        // Remove existing xrefs
        $db->setQuery(
            $db->getQuery(true)
                ->delete('#__focalpoint_location_type_xref')
                ->where('location_id = ' . $id)
        )
            ->execute();

        // normalize/filter selected ids between type and othertypes
        $typeIds = array_merge(
            array($data['type']),
            empty($data['othertypes']) ? array() : $data['othertypes']
        );
        $typeValues = array_map(
            function ($typeId) use ($id) {
                return sprintf('(%s, %s)', $id, $typeId);
            },
            array_filter(array_unique($typeIds))
        );

        // Insert the new xrefs
        $db->setQuery(
            'INSERT #__focalpoint_location_type_xref '
            . ' (location_id, locationtype_id)'
            . ' VALUES ' . join(',', $typeValues)
        )
            ->execute();
    }
}
