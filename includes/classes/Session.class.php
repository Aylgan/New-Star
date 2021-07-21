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

class Session
{
	static private $obj = NULL;
	static private $iniSet	= false;
	private $data = NULL;
	private $error = NULL;

	/**
	 * Set PHP session settings
	 *
	 * @return bool
	 */

	static public function init()
	{
		if(self::$iniSet === true)
		{
			return false;
		}
		self::$iniSet = true;
		return true;
	}

	static private function getTempPath()
	{
		require_once 'includes/libs/wcf/BasicFileUtil.class.php';
		return BasicFileUtil::getTempFolder();
	}


	/**
	 * Create an empty session
	 *
	 * @return String
	 */

	static public function getClientIp()
    {
		if(!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        }
		elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
			$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif(!empty($_SERVER['HTTP_X_FORWARDED']))
        {
			$ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        }
        elseif(!empty($_SERVER['HTTP_FORWARDED_FOR']))
        {
			$ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        }
        elseif(!empty($_SERVER['HTTP_FORWARDED']))
        {
			$ipAddress = $_SERVER['HTTP_FORWARDED'];
        }
        elseif(!empty($_SERVER['REMOTE_ADDR']))
        {
			$ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        else
        {
			$ipAddress = 'UNKNOWN';
        }
        return $ipAddress;
	}

	/**
	 * Create an empty session
	 *
	 * @return Session data
	 */

	public function create()
	{
		$activeSession = $this->existsActiveSession();

		$data = array(
			'authKey' => md5($this->userID .uniqid()),
			'expire' => TIMESTAMP + 60*60*24,
			'lastonline' => TIMESTAMP
		);

		$this->data = array_merge($data, $this->data);

		if($activeSession){
			$this->data['authKey'] = $activeSession['authKey'];
		}

		register_shutdown_function(array($this, 'save'));

		return $this->data;
	}

	/**
	 * Wake an active session
	 *
	 * @return Session
	 */

	public function load($authKey)
	{
		$sql	= "SELECT * FROM %%AUTH%% WHERE authKey = :authKey;";
		$auth	= Database::get()->selectSingle($sql, array(
			':authKey'	=> $authKey,
		));

		if (!isset($auth)){
			$this->error = array(
				'error' => true,
				'errorType' => 'authError',
				'message' => 'authKey not found!'
			);
			return false;
		}else{
			if( $auth['expire'] < TIMESTAMP ){
				$this->error = array(
					'error' => true,
					'errorType' => 'authError',
					'message' => 'authKey expired!'
				);
				return false;
			}

		}

		$this->data = $auth;

		register_shutdown_function(array($this, 'save'));

		return $this->data;
	}
	
	/**
	 * Return error
	 *
	 * @return array
	 */
	
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Check if an active session exists
	 *
	 * @return array
	 */

	public function existsActiveSession()
	{
		$sql	= "SELECT * FROM %%AUTH%% WHERE userID = :userID;";
		$auth	= Database::get()->selectSingle($sql, array(
			':userID'	=> $this->userID,
		));

		return $auth;
	}

	public function __construct()
	{
		self::init();
	}

	public function __sleep()
	{
		return array('data');
	}

	public function __wakeup()
	{

	}

	public function __set($name, $value)
	{
		$this->data[$name]	= $value;
	}

	public function __get($name)
	{
		if(isset($this->data[$name]))
		{
			return $this->data[$name];
		}
		else
		{
			return NULL;
		}
	}

	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	public function save()
	{
	    // sessions require an valid user.
	    if(empty($this->data['userID']) || $this->data['expire'] < TIMESTAMP) {
	        $this->delete();
	    }

        $userIpAddress = self::getClientIp();
		
		$sql	= 'REPLACE INTO %%AUTH%% SET
		authKey	= :authKey,
		userID		= :userId,
		lastonline	= :lastActivity,
		userIP		= :userAddress,
		expire		= :expire;';

		$db		= Database::get();

		$db->replace($sql, array(
			':authKey'		=> $this->data['authKey'],
			':userId'		=> $this->data['userID'],
			':lastActivity'	=> TIMESTAMP,
			':userAddress'	=> $userIpAddress,
			':expire'		=> TIMESTAMP + 60*60*24
		));

		$sql = 'UPDATE %%USERS%% SET
		onlinetime	= :lastActivity,
		user_lastip = :userAddress
		WHERE
		id = :userId;';

		$db->update($sql, array(
		   ':userAddress'	=> $userIpAddress,
		   ':lastActivity'	=> TIMESTAMP,
		   ':userId'		=> $this->data['userID'],
		));

		$this->data['lastActivity']  	= TIMESTAMP;
		$this->data['authKey'] 			= $this->data['authKey'];
		$this->data['sessionId']	 	= session_id();
		$this->data['userIpAddress'] 	= $userIpAddress;
		$this->data['requestPath']	 	= $this->getRequestPath();

		$_SESSION['obj']	= serialize($this);

		@session_write_close();
        
	}

	public function delete()
	{
		$sql	= 'DELETE FROM %%AUTH%% WHERE authKey = :authKey;';
		$db		= Database::get();

		$db->delete($sql, array(
			':authKey'	=> $this->data['authKey'],
		));
	}

	public function isValidSession()
	{
		return false;
	}

	public function selectActivePlanet()
	{
		$httpData	= HTTP::_GP('cp', 0);

		if(!empty($httpData))
		{
			$sql	= 'SELECT id FROM %%PLANETS%% WHERE id = :planetId AND id_owner = :userId;';

			$db	= Database::get();
			$planetId	= $db->selectSingle($sql, array(
				':userId'	=> $this->data['userID'],
				':planetId'	=> $httpData,
			), 'id');

			if(!empty($planetId))
			{
				$this->data['planetId']	= $planetId;
			}
		}
	}

	private function getRequestPath()
	{
		return HTTP_ROOT.(!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');
	}
	
	private function compareIpAddress($ip1, $ip2, $blockCount)
	{
		if (strpos($ip2, ':') !== false && strpos($ip1, ':') !== false)
		{
			$s_ip = $this->short_ipv6($ip1, $blockCount);
			$u_ip = $this->short_ipv6($ip2, $blockCount);
		}
		else
		{
			$s_ip = implode('.', array_slice(explode('.', $ip1), 0, $blockCount));
			$u_ip = implode('.', array_slice(explode('.', $ip2), 0, $blockCount));
		}
		
		return ($s_ip == $u_ip);
	}

	private function short_ipv6($ip, $length)
	{
		if ($length < 1)
		{
			return '';
		}

		$blocks = substr_count($ip, ':') + 1;
		if ($blocks < 9)
		{
			$ip = str_replace('::', ':' . str_repeat('0000:', 9 - $blocks), $ip);
		}
		if ($ip[0] == ':')
		{
			$ip = '0000' . $ip;
		}
		if ($length < 4)
		{
			$ip = implode(':', array_slice(explode(':', $ip), 0, 1 + $length));
		}

		return $ip;
	}
}