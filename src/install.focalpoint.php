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

use Alledia\Installer\AbstractScript;

defined('_JEXEC') or die;

// Adapt for install and uninstall environments
if (file_exists(__DIR__ . '/admin/library/Installer/AbstractScript.php')) {
    require_once __DIR__ . '/admin/library/Installer/AbstractScript.php';
} else {
    require_once __DIR__ . '/library/Installer/AbstractScript.php';
}

class com_focalpointInstallerScript extends AbstractScript
{
    /**
     * @param JInstallerAdapter $parent
     *
     * @return bool
     * @throws Exception
     */
    public function update($parent)
    {
        $app = JFactory::getApplication();

        try {
            if (parent::update($parent)) {
                if (version_compare($this->previousManifest->version, '1.2', 'lt')) {
                    $this->setMessage(
                        'FocalPoint v1.2 included a new batch of icon markers and cluster icons.'
                        . ' Since you are upgrading to Shack Locations from an earlier version, these markers'
                        . ' cannot be moved to your images folder without overwriting the original images/markers'
                        . ' directory which we do not wish to do.'
                        . ' The new markers can be found on your server in the media/com_focalpoint folder.'
                        . ' Alternatively, you can extract the installation archive on your local machine where you can'
                        . ' find the markers in the media folder. You are free to use these new markers as you wish.'
                        . ' There are over 200 of them. You can upload or move them via FTP or through your hosting'
                        . ' control panel. For new installations, the new markers have been moved to images/markers.',
                        'notice',
                        true
                    );

                }

                return true;

            } else {
                $app->enqueueMessage("It's the parent!");

            }

        } catch (Exception $e) {
            $app->enqueueMessage(sprintf('%s:%s<br>%s', $e->getFile(), $e->getLine(), $e->getMessage()));

        } catch (Throwable $e) {
            $app->enqueueMessage(sprintf('%s:%s<br>%s', $e->getFile(), $e->getLine(), $e->getMessage()));
        }

        return false;
    }

    /**
     * @param string            $type
     * @param JInstallerAdapter $parent
     *
     * @throws Exception
     */
    public function postflight($type, $parent)
    {
        $app = JFactory::getApplication();

        try {
            switch ($type) {
                case 'install':
                case 'discover_install':
                    $this->moveMarkers();
                    break;

                case 'update':
                    $this->updateTabsdata();
                    $this->updateCustomFields();
                    $this->removeLanguageFiles();
                    break;
            }

            parent::postFlight($type, $parent);

        } catch (Exception $e) {
            $app->enqueueMessage(sprintf('%s:%s<br>%s', $e->getFile(), $e->getLine(), $e->getMessage()));

        } catch (Throwable $e) {
            $app->enqueueMessage(sprintf('%s:%s<br>%s', $e->getFile(), $e->getLine(), $e->getMessage()));
        }
    }

    /**
     * Move the markers to the images folder (on new install only)
     */
    protected function moveMarkers()
    {
        $source      = $this->installer->getPath('source') . '/assets/markers';
        $destination = JPATH_SITE . '/images/markers';

        if (JFolder::move($source, $destination)) {
            $this->setMessage('Successully copied markers to ' . $destination, 'notice');

        } else {
            $message = array(
                '<p>Unable to move the markers folder to your /images folder. This is usaully due to;</p>',
                '<ol>',
                '<li>incorrect file permission settings. Please go to System &gt; System Information &gt; Directory Permissions and check that the images, media and tmp folders are writable.</li>',
                '<li>You already have an /images/markers folder.</li>',
                '</ol>'
            );

            $this->setMessage(join('', $message), 'notice');
        }
    }

    /**
     * Removes all non-localized language files
     */
    public function removeLanguageFiles()
    {
        $files = array_merge(
            JFolder::files(JPATH_SITE . '/language', '.*focalpoint.*', true, true),
            JFolder::files(JPATH_ADMINISTRATOR . '/language', '.*focalpoint.*', true, true)
        );

        foreach ($files as $file) {
            JFile::delete($file);
        }
    }

    /**
     * Reformats the tabsdata field for changes made in v1.4.0
     *
     * @return void
     */
    protected function updateTabsdata()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select(
                array(
                    'id',
                    'tabsdata'
                )
            )
            ->from('#__focalpoint_maps');

        $maps = $db->setQuery($query)->loadObjectList();

        $fixed = 0;
        foreach ($maps as $map) {
            if ($tabsdata = json_decode($map->tabsdata)) {
                $newData = array();
                if (!isset($tabsdata->tabs)) {
                    foreach ($tabsdata as $hash => $tab) {
                        if (in_array($hash, array('mapstyle'))) {
                            $newData[$hash] = $tab;
                        } else {
                            $newData['tabs'][$hash] = $tab;
                        }
                    }
                }
                if ($newData) {
                    $map->tabsdata = json_encode($newData);
                    $fixed         += (int)$db->updateObject('#__focalpoint_maps', $map, array('id'));
                }
            }
        }
    }

    /**
     * update the custom fields in location types made in v1.4.0
     *
     * @return void
     */
    public function updateCustomFields()
    {
        $db = JFactory::getDbo();

        $locationTypes = $db->setQuery(
            $db->getQuery(true)
                ->select(
                    array(
                        'id',
                        'customfields'
                    )
                )
                ->from('#__focalpoint_locationtypes')
                ->where('customfields != ' . $db->quote(''))
        )
            ->loadObjectList();

        foreach ($locationTypes as $locationType) {
            $customFields = json_decode($locationType->customfields, true);
            if (substr_count(key($customFields), '.') == 1) {
                $locationType->customfields = array();
                foreach ($customFields as $key => $customField) {
                    list($type, $hash) = explode('.', $key);

                    $customField['type']               = $type;
                    $locationType->customfields[$hash] = $customField;
                }

                $locationType->customfields = json_encode($locationType->customfields);
                $db->updateObject('#__focalpoint_locationtypes', $locationType, array('id'));
            }
        }
    }
}
