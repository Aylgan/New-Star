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

class ShowLoginPage extends AbstractLoginPage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
	}
	
	function show() 
	{
		if (empty(HTTP::_JSON())) {
			$this->returnJson(array(
				'error'		=> true,
				'message'	=> 'empty data'
			));

			exit;
		}

		$db = Database::get();

		$username = HTTP::_JSON('username', '', UTF8_SUPPORT);
		$password = HTTP::_JSON('password', '', true);

		$sql = "SELECT id, password FROM %%USERS%% WHERE universe = :universe AND username = :username;";
		$loginData = $db->selectSingle($sql, array(
			':universe'	=> Universe::current(),
			':username'	=> $username
		));

		if ($loginData)
		{
			$hashedPassword = PlayerUtil::cryptPassword($password);
			if($loginData['password'] != $hashedPassword)
			{
				// Fallback pre 1.7
				if($loginData['password'] == md5($password)) {
					$sql = "UPDATE %%USERS%% SET password = :hashedPassword WHERE id = :loginID;";
					$db->update($sql, array(
						':hashedPassword'	=> $hashedPassword,
						':loginID'			=> $loginData['id']
					));
				} else {
					$this->returnJson(array(
						'error'		=> true,
						'message'	=> 'wrong password'
					));
				}
			}

			$session = new Session;
			$session->__set('userID', (int) $loginData['id']);
			$session->create();

			$this->returnJson(array(
				'idToken'		=> $session->__get('authKey'),
				'expiresIn'		=> $session->__get('expire') - TIMESTAMP
			));
		}
		else
		{
			$this->returnJson(array(
				'error'		=> true,
				'message'	=> 'user not find'
			));
		}
	}
}
