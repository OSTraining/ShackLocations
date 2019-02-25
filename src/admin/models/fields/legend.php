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

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('predefinedlist');

class ShacklocationsFormFieldLegend extends JFormFieldPredefinedList
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  3.2
     */
    public $type = 'legend';

    /**
     * Available statuses
     *
     * @var  array
     * @since  3.2
     */
    protected $predefinedOptions = array();

    /**
     * ShacklocationsFormFieldLegend constructor.
     *
     * @param Form $form
     */
    public function __construct($form = null)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('id,title')
            ->from('`#__focalpoint_legends`')
            ->where('`state` > -1');

        $results = $db->setQuery($query)->loadObjectList();

        foreach ($results as $result) {
            $this->predefinedOptions[$result->id] = $result->title;
        }

        parent::__construct($form);
    }
}
