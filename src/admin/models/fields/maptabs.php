<?php
/**
 * @package   ShackLocations
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2018 Open Source Training, LLC. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
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

defined('_JEXEC') or die();

class ShacklocationsFormFieldMaptabs extends JFormField
{
    /**
     * @param array $options
     *
     * @return string
     * @throws Exception
     */
    public function renderField($options = array())
    {
        if ($parent = $this->element->xpath('parent::fieldset')) {
            $this->addJS();

            $allFields = array();
            $values    = (array)$this->value;

            // Add our current tab name group
            $parent           = array_pop($parent);
            $tabGroup         = $parent->addChild('fields');
            $tabGroup['name'] = $this->fieldname;

            $baseGroup = $this->group . '.' . $tabGroup['name'];

            foreach ($values as $hash => $data) {
                $fieldGroup         = $tabGroup->addChild('fields');
                $fieldGroup['name'] = $hash;

                $groupName = $baseGroup . '.' . $hash;

                $nameFieldXml = sprintf(
                    '<field name="name" type="text" label="%s"/>',
                    JText::_('COM_FOCALPOINT_CUSTOMFIELD_NAME')
                );
                $nameField    = new SimpleXMLElement($nameFieldXml);
                $this->form->setField($nameField, $groupName);

                $contentFieldXml = '<field name="content" type="editor" label=""/>';
                $contentField    = new SimpleXMLElement($contentFieldXml);
                $this->form->setField($contentField, $groupName);

                $allFields = array_merge(
                    $allFields,
                    array(
                        '<div class="clearfix">',
                        $this->form->renderField('name', $groupName, null, $options),
                        $this->form->renderField('content', $groupName, null, $options),
                        '</div>'
                    )
                );
            }

            return join('', $allFields);
        }

        JFactory::getApplication()->enqueueMessage('Error with setup of custom tab field - ' . $this->name, 'error');
        return '';
    }

    protected function addJS()
    {
    }
}
