<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\FRS;

use Planning_Milestone;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;

class AgileDashboardPaneInfo extends PaneInfo
{
    private $release_id;

    public function __construct(Planning_Milestone $milestone, $release_id)
    {
        parent::__construct($milestone);
        $this->release_id = $release_id;
    }

    /** @see PaneInfo::getIdentifier */
    public function getIdentifier()
    {
        return 'frs';
    }

    public function isExternalLink()
    {
        return true;
    }

    /** @see PaneInfo::getTitle */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_frs', 'File_release');
    }

    public function getUri()
    {
        return '/file/shownotes.php?release_id=' . (int)$this->release_id;
    }

    public function getIconName()
    {
        return 'fa-files-o icon-copy';
    }
}
