<?php
namespace wcf\system\event\listener;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Handles additions to absence setting form.
 * 
 * @author		2016-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.absence.conversation
 */
class AbsenceSettingsFormListener implements IParameterizedEventListener {
	/**
	 * form object
	 */
	protected $eventObj = null;
	
	/**
	 * absentReply
	 */
	public $absentReply = 0;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$this->eventObj = $eventObj;
		
		$this->$eventName();
	}
	
	/**
	 * Handles the readFormParameters event.
	 */
	protected function readFormParameters() {
		$this->absentReply = 0;
		if (isset($_POST['absentReply'])) $this->absentReply = intval($_POST['absentReply']);
	}
	
	/**
	 * Handles the readParameters event.
	 */
	protected function readParameters() {
		$this->absentReply = WCF::getUser()->absentReply;
	}
	
	/**
	 * Handles the assignVariables event.
	 */
	protected function assignVariables() {
		WCF::getTPL()->assign([
				'absentReply' => $this->absentReply
		]);
	}
	
	/**
	 * Handles the save event.
	 */
	protected function save() {
		$this->eventObj->additionalFields['absentReply'] = $this->absentReply;
	}
}
