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

use wcf\data\conversation\Conversation;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\user\UserList;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\user\absence\AbsenceHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Handles conversation auto replies for absent members.
 */
class AbsenceConversationListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $action = $eventObj->getActionName();

        // only create and not draft
        if ($action == 'create') {
            $return = $eventObj->getReturnValues();
            $draft = $return['returnValues']->isDraft;
            if ($draft) {
                return;
            }

            // get visible participants, exclude sender
            $conversationID = $return['returnValues']->conversationID;
            $participantIDs = [];
            $sql = "SELECT    participantID
                    FROM    wcf" . WCF_N . "_conversation_to_user
                    WHERE    conversationID = ? AND isInvisible = ? AND participantID <> ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$conversationID, 0, WCF::getUser()->userID]);
            while ($row = $statement->fetchArray()) {
                $participantIDs[] = $row['participantID'];
            }
            if (empty($participantIDs)) {
                return;
            }

            // get users
            $userList = new UserList();
            $userList->setObjectIDs($participantIDs);
            $userList->readObjects();
            $users = $userList->getObjects();
            if (!\count($users)) {
                return;
            }

            // create replies
            foreach ($users as $user) {
                $this->reply($conversationID, $user);
            }
        }

        // user added
        if ($action == 'addParticipants') {
            // get conversation
            $objects = $eventObj->getObjects();
            $conversationID = $objects[0]->conversationID;

            // get participants
            $participantIDs = [];
            $sql = "SELECT    participantID
                    FROM    wcf" . WCF_N . "_conversation_to_user
                    WHERE    conversationID = ? AND isInvisible = ? AND participantID <> ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$conversationID, 0, WCF::getUser()->userID]);
            while ($row = $statement->fetchArray()) {
                $participantIDs[] = $row['participantID'];
            }
            if (empty($participantIDs)) {
                return;
            }

            // get users
            $userList = new UserList();
            $userList->setObjectIDs($participantIDs);
            $userList->readObjects();
            $users = $userList->getObjects();
            if (!\count($users)) {
                return;
            }

            // create replies
            foreach ($users as $user) {
                $this->reply($conversationID, $user);
            }
        }
    }

    /**
     * Create reply
     */
    protected function reply($conversationID, $user)
    {
        // skip if not absent and/or no reply configured
        if (!AbsenceHandler::getInstance()->isAbsent($user) || !$user->absentReply) {
            return;
        }

        // create reply
        $language = $user->getLanguage();
        $absentRep = null;
        if (isset($user->absentRepID)) {
            $rep = AbsenceHandler::getInstance()->getRep($user->userID);
            if ($rep !== null) {
                $absentRep = '<a href="' . $rep->getLink() . '">' . StringUtil::encodeHTML($rep->username) . '</a>';
            }
        }
        $message = $language->getDynamicVariable('wcf.user.absence.conversation.reply', [
            'absentFrom' => $user->absentFrom,
            'absentTo' => $user->absentTo,
            'absentReason' => $user->absentReason,
            'absentRep' => $absentRep,
        ]);

        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process($message, 'com.woltlab.wcf.conversation.message', 0);

        $data = [
            'conversationID' => $conversationID,
            'time' => TIME_NOW,
            'userID' => $user->userID,
            'username' => $user->username,
        ];

        $conversation = new Conversation($conversationID);
        $messageAction = new ConversationMessageAction([], 'create', [
            'data' => $data,
            'conversation' => $conversation,
            'isFirstPost' => false,
            'attachmentHandler' => null,
            'htmlInputProcessor' => $htmlInputProcessor,
        ]);
        $messageAction->executeAction();
    }
}
