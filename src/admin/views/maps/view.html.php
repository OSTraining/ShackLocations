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

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class FocalpointViewMaps extends JViewLegacy
{
    /**
     * @var object[]
     */
    protected $items = null;

    /**
     * @var Pagination
     */
    protected $pagination;

    /**
     * @var Form
     */
    public $filterForm = null;

    /**
     * @var mixed[]
     */
    public $activeFilters = null;

    /**
     * @var Registry
     */
    protected $state = null;

    /**
     * @param string $tpl
     *
     * @return void
     * @throws Exception
     */
    public function display($tpl = null)
    {
        /** @var AdministratorApplication $app */
        $app = JFactory::getApplication();

        try {
            /** @var FocalpointModelMaps $model */
            $model = $this->getModel();

            $this->state         = $model->getState();
            $this->items         = $model->getItems();
            $this->pagination    = $model->getPagination();
            $this->filterForm    = $model->getFilterForm();
            $this->activeFilters = $model->getActiveFilters();

            if ($errors = $model->getErrors()) {
                throw new Exception(implode('<br>', $errors));
            }

            FocalpointHelper::addSubmenu('maps');
            $this->sidebar = JHtmlSidebar::render();

            $this->addToolbar();

            /*
             * This is part of the getting started walk through. If we've gotten this far then the
             * user has successfully saved their configuration.
             * Check we have at least one map defined
             */
            $db = JFactory::getDbo();

            $query = $db->getQuery(true)
                ->select('COUNT(*)')->from('#__focalpoint_maps');

            $mapsExist = $db->setQuery($query)->loadResult();
            if (!$mapsExist) {
                $app->input->set('task', 'showhelp');
            }

            parent::display($tpl);

            echo FocalpointHelper::renderAdminFooter();

        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');

        } catch (Throwable $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Add the page title and toolbar.
     *
     * @since    1.6
     */
    protected function addToolbar()
    {
        $user = JFactory::getUser();

        JToolBarHelper::title(JText::_('COM_FOCALPOINT_TITLE_MAPS'), 'compass');

        if ($user->authorise('core.create', 'com_focalpoint')) {
            JToolBarHelper::addNew('map.add', 'JTOOLBAR_NEW');
        }

        if ($user->authorise('core.edit', 'com_focalpoint')) {
            JToolBarHelper::editList('map.edit', 'JTOOLBAR_EDIT');
        }

        if ($user->authorise('core.edit.state', 'com_focalpoint')) {
            JToolBarHelper::divider();
            JToolBarHelper::custom('maps.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
            JToolBarHelper::custom('maps.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);

            JToolBarHelper::divider();
            JToolBarHelper::archiveList('maps.archive', 'JTOOLBAR_ARCHIVE');

            JToolBarHelper::custom('maps.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
        }

        if ($user->authorise('core.delete', 'com_focalpoint')) {
            if ($this->state->get('filter.state') == -2) {
                JToolBarHelper::deleteList('', 'maps.delete', 'JTOOLBAR_EMPTY_TRASH');
            } else {
                JToolBarHelper::trash('maps.trash', 'JTOOLBAR_TRASH');
            }

            if ($user->authorise('core.admin', 'com_focalpoint')) {
                JToolBarHelper::preferences('com_focalpoint');
            }
        }
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     *
     * @since   3.0
     */
    protected function getSortFields()
    {
        return array(
            'a.ordering'   => JText::_('JGRID_HEADING_ORDERING'),
            'a.state'      => JText::_('JSTATUS'),
            'a.title'      => JText::_('JGLOBAL_TITLE'),
            'a.created_by' => JText::_('JAUTHOR'),
            'a.id'         => JText::_('JGRID_HEADING_ID')
        );
    }
}
