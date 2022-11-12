<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace wcf\system\event\listener;

use wcf\system\WCF;

/**
 * Handles additions to absence setting form.
 */
class AbsenceSettingsFormListener implements IParameterizedEventListener
{
    /**
     * form object
     */
    protected $eventObj;

    /**
     * absentReply
     */
    public $absentReply = 0;

    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $this->eventObj = $eventObj;

        $this->{$eventName}();
    }

    /**
     * Handles the readFormParameters event.
     */
    protected function readFormParameters()
    {
        $this->absentReply = 0;
        if (isset($_POST['absentReply'])) {
            $this->absentReply = \intval($_POST['absentReply']);
        }
    }

    /**
     * Handles the readParameters event.
     */
    protected function readParameters()
    {
        $this->absentReply = WCF::getUser()->absentReply;
    }

    /**
     * Handles the assignVariables event.
     */
    protected function assignVariables()
    {
        WCF::getTPL()->assign([
            'absentReply' => $this->absentReply,
        ]);
    }

    /**
     * Handles the save event.
     */
    protected function save()
    {
        $this->eventObj->additionalFields['absentReply'] = $this->absentReply;
    }
}
