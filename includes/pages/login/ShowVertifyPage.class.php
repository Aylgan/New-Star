<?php

/*
 * ╔══╗╔══╗╔╗──╔╗╔═══╗╔══╗╔╗─╔╗╔╗╔╗──╔╗╔══╗╔══╗╔══╗
 * ║╔═╝║╔╗║║║──║║║╔═╗║║╔╗║║╚═╝║║║║║─╔╝║╚═╗║║╔═╝╚═╗║
 * ║║──║║║║║╚╗╔╝║║╚═╝║║╚╝║║╔╗─║║╚╝║─╚╗║╔═╝║║╚═╗──║║
 * ║║──║║║║║╔╗╔╗║║╔══╝║╔╗║║║╚╗║╚═╗║──║║╚═╗║║╔╗║──║║
 * ║╚═╗║╚╝║║║╚╝║║║║───║║║║║║─║║─╔╝║──║║╔═╝║║╚╝║──║║
 * ╚══╝╚══╝╚╝──╚╝╚╝───╚╝╚╝╚╝─╚╝─╚═╝──╚╝╚══╝╚══╝──╚╝
 *
 * @author Tsvira Yaroslav <https://github.com/Yaro2709>
 * @info ***
 * @link https://github.com/Yaro2709/New-Star
 * @Basis 2Moons: XG-Project v2.8.0
 * @Basis New-Star: 2Moons v1.8.0
 */

class ShowVertifyPage extends AbstractLoginPage
{
	public static $requireModule = 0;

	function __construct()
	{
		parent::__construct();
	}

	private function _activeUser()
	{
		global $LNG;

		$validationID	= HTTP::_JSON('id', 0);
		$validationKey	= HTTP::_JSON('key', '');

		$db = Database::get();

		$sql = "SELECT * FROM %%USERS_VALID%%
		WHERE validationID	= :validationID
		AND validationKey	= :validationKey
		AND universe		= :universe;";

		$userData = $db->selectSingle($sql, array(
			':validationKey'	=> $validationKey,
			':validationID'		=> $validationID,
			':universe'			=> Universe::current()
		));

		if(empty($userData))
		{
			$this->returnJson(array(
				'error'		=> true,
				'message'	=> $LNG['vertifyNoUserFound'],
				'validationID'	=> $validationID,
				'validationKey'	=> $validationKey,
			));
		}

		$config	= Config::get();

		$sql = "DELETE FROM %%USERS_VALID%% WHERE validationID = :validationID;";
		$db->delete($sql, array(
			':validationID'	=> $validationID
		));

		list($userID, $planetID) = PlayerUtil::createPlayer($userData['universe'], $userData['userName'], $userData['password'], $userData['email'], $userData['language']);

		if($config->mail_active == 1)
		{
			require('includes/classes/Mail.class.php');
			$MailSubject	= sprintf($LNG['registerMailCompleteTitle'], $config->game_name, Universe::current());
			$MailRAW		= $LNG->getTemplate('email_reg_done');
			$MailContent	= str_replace(array(
				'{USERNAME}',
				'{GAMENAME}',
				'{GAMEMAIL}',
			), array(
				$userData['userName'],
				$config->game_name.' - '.$config->uni_name,
				$config->smtp_sendmail,
			), $MailRAW);

			try {
				Mail::send($userData['email'], $userData['userName'], $MailSubject, $MailContent);
			}
			catch (Exception $e)
			{
				// This mail is wayne.
			}
		}

		if(!empty($userData['referralID']))
		{
			$sql = "UPDATE %%USERS%% SET
			`ref_id`	= :referralId,
			`ref_bonus`	= 1
			WHERE
			`id`		= :userID;";

			$db->update($sql, array(
				':referralId'	=> $userData['referralID'],
				':userID'		=> $userID
			));
		}

		if(!empty($userData['externalAuthUID']))
		{
			$sql ="INSERT INTO %%USERS_AUTH%% SET
			`id`		= :userID,
			`account`	= :externalAuthUID,
			`mode`		= :externalAuthMethod;";
			$db->insert($sql, array(
				':userID'				=> $userID,
				':externalAuthUID'		=> $userData['externalAuthUID'],
				':externalAuthMethod'	=> $userData['externalAuthMethod']
			));
		}

		$senderName = $LNG['registerWelcomePMSenderName'];
		$subject 	= $LNG['registerWelcomePMSubject'];
		$message 	= sprintf($LNG['registerWelcomePMText'], $config->game_name, $userData['universe']);

		PlayerUtil::sendMessage($userID, 1, $senderName, 1, $subject, $message, TIMESTAMP);
		
		return array(
			'userID'	=> $userID,
			'userName'	=> $userData['userName'],
			'planetID'	=> $planetID
		);
	}

	function show()
	{
		$userData	= $this->_activeUser();

		$session = new Session;
		$session->__set('userID', (int) $userData['userID']);
		$session->create();

		$this->returnJson(array(
			'idToken'		=> $session->__get('authKey'),
			'expiresIn'		=> $session->__get('expire') - TIMESTAMP
		));
	}

	function json()
	{
		global $LNG;
		$userData	= $this->_activeUser();
		$this->sendJSON(sprintf($LNG['vertifyAdminMessage'], $userData['userName']));
	}
}