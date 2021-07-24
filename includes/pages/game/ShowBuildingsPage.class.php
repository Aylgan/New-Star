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

require_once('includes/classes/class.FleetFunctions.php');
class ShowBuildingsPage extends AbstractGamePage
{	
	private $build_anz=0;
	private $bOnInsert=FALSE;
	public static $requireModule = MODULE_BUILDING;

	function __construct() 
	{
		parent::__construct();
	}
	
	private function FastBuildingFromQueue($Element)
	{
		global $PLANET, $USER, $resource;	

		$CurrentQueue  = unserialize($PLANET['b_building_id']);	
		if (empty($CurrentQueue)){
			$PLANET['b_building_id']	= '';
			$PLANET['b_building']		= 0;
			return;		
		}

		$uKey = -1;

		foreach($CurrentQueue as $key => $QueueElem){
			if($Element == $QueueElem[0])
				$uKey = $key;
		}

		if($uKey == -1)
			return false;

		$Element             	= $CurrentQueue[$uKey][0];
		$BuildMode          	= $CurrentQueue[$uKey][4];
		$fast                   = $resource[$Element];
		$BuildEndTime 			= $CurrentQueue[$uKey][3];

		if ($PLANET['planet_type']==3){  
            $NeededDm           = (1000*(($BuildEndTime-TIMESTAMP)/3600));
		}else{ 
			$NeededDm           = (200*(($BuildEndTime-TIMESTAMP)/3600));
		}

		if($NeededDm < 10)
			$NeededDm=10;

        if ($USER['darkmatter'] >= $NeededDm){
			$USER['darkmatter']		        -= $NeededDm;

			if ($BuildMode == 'destroy'){
				$PLANET['field_current'] -=1;
				$PLANET[$resource[$Element]] -= 1;

				$sql = "UPDATE %%PLANETS%% SET ".$fast." = ".$fast." - 1 WHERE id = :planetId;";
			}
			else{
				$PLANET['field_current'] +=1;
				$PLANET[$resource[$Element]] += 1;
				$sql = "UPDATE %%PLANETS%% SET ".$fast." = ".$fast." + 1 WHERE id = :planetId;";
			}

			Database::get()->update($sql, array(':planetId'	=> $PLANET['id']));

			unset($CurrentQueue[$uKey]);
			if (count($CurrentQueue) == 0) {
				$PLANET['b_building']    	= 0;
				$PLANET['b_building_id'] 	= '';
			} else {
				$NewQueueArray	= array();
				$b_building = 0;
				foreach($CurrentQueue as $ListIDArray) {
					$NewQueueArray[]	= $ListIDArray;	

					$BuildEndTime 	= $ListIDArray[3];

					if ($BuildEndTime > TIMESTAMP){

						if($b_building == 0)
							$b_building = $BuildEndTime;
						elseif($b_building > $BuildEndTime)
							$b_building = $BuildEndTime;
					}
				}

				if(!empty($NewQueueArray)) {
					$PLANET['b_building']    	= $b_building;
					$PLANET['b_building_id'] 	= serialize($NewQueueArray);
					$this->ecoObj->setData($USER, $PLANET);
					list($USER, $PLANET)		= $this->ecoObj->getData();
				} else {
					$PLANET['b_building']    	= 0;
					$PLANET['b_building_id'] 	= '';
				}
			}

			 return true;
		}
	}
	
	private function CancelBuildingFromQueue($Element)
	{
		global $PLANET, $USER, $resource, $reslist;
		$CurrentQueue  = unserialize($PLANET['b_building_id']);
		if (empty($CurrentQueue))
		{
			$PLANET['b_building_id']	= '';
			$PLANET['b_building']		= 0;
			return false;
		}
		
		$uKey = -1;

		foreach($CurrentQueue as $key => $QueueElem){
			if($Element == $QueueElem[0])
				$uKey = $key;
		}

		if($uKey == -1)
			return false;

		$Element             	= $CurrentQueue[$uKey][0];
        $BuildLevel          	= $CurrentQueue[$uKey][1];
		$BuildMode          	= $CurrentQueue[$uKey][4];
		
		$costResources			= BuildFunctions::getElementPrice($USER, $PLANET, $Element, $BuildMode == 'destroy');
        require_once('includes/subclasses/subclass.ResPlus.php');
		
		unset($CurrentQueue[$uKey]);
		array_shift($CurrentQueue);
		if (count($CurrentQueue) == 0) {
			$PLANET['b_building']    	= 0;
			$PLANET['b_building_id'] 	= '';
		} else {
			$BuildEndTime	= TIMESTAMP;
			$NewQueueArray	= array();
			$b_building 	= 0;
			foreach($CurrentQueue as $ListIDArray) {
				$NewQueueArray[]	= $ListIDArray;	

				$BuildEndTime 	= $ListIDArray[3];

				if ($BuildEndTime > TIMESTAMP){

					if($b_building == 0)
						$b_building = $BuildEndTime;
					elseif($b_building > $BuildEndTime)
						$b_building = $BuildEndTime;
				}		
			}
			
			if(!empty($NewQueueArray)) {
				$PLANET['b_building'] 		= $b_building;
				$PLANET['b_building_id'] 	= serialize($NewQueueArray);
				$this->ecoObj->setData($USER, $PLANET);
				$this->ecoObj->SetNextQueueElementOnTop();
				list($USER, $PLANET)		= $this->ecoObj->getData();
			} else {
				$PLANET['b_building']    	= 0;
				$PLANET['b_building_id'] 	= '';
			}
		}
		return true;
	}

	private function AddBuildingToQueue($Element, $AddMode = true, $tile = 0, $type = 'normal')
	{
        global $PLANET, $USER, $resource, $reslist, $pricelist;

        if(!in_array($Element, $reslist['allow'][$PLANET['planet_type']])
            || !BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element)
            || ($Element == 31 && $USER["b_tech_planet"] != 0)
            || (($Element == 15 || $Element == 21) && !empty($PLANET['b_hangar_id']))
            || (!$AddMode && $PLANET[$resource[$Element]] == 0
            || $tile == 0
            || $PLANET['tiles'][$tile]['build_end_time'] > 0
            || ($PLANET['tiles'][$tile]['build_id'] != 0 && $PLANET['tiles'][$tile]['build_id'] != $Element))
        )
            return;

        $BuildMode 			= $AddMode ? 'build' : 'destroy';

        if($type == 'normal')
            $BuildLevel			= $PLANET[$resource[$Element]] + (int) $AddMode;
        else{
            if($AddMode) {
                $BuildLevel = $PLANET['tiles'][$tile]['build_lvl'] + 1;
            }else{
                $BuildLevel			= max($PLANET['tiles'][$tile]['build_lvl'] - 1, 0);
            }
        }

        if($pricelist[$Element]['max'] < $BuildLevel)
            return;

        $costResources		= BuildFunctions::getElementPrice($USER, $PLANET, $Element, !$AddMode, $BuildLevel);

        if(!BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources))
            return;

        if(isset($costResources[901])) { $PLANET[$resource[901]]	-= $costResources[901]; }
        if(isset($costResources[902])) { $PLANET[$resource[902]]	-= $costResources[902]; }
        if(isset($costResources[903])) { $PLANET[$resource[903]]	-= $costResources[903]; }
        if(isset($costResources[921])) { $USER[$resource[921]]		-= $costResources[921]; }

        $elementTime    			= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $costResources);
        $BuildEndTime				= TIMESTAMP + $elementTime;

        $PLANET['tiles'][$tile]['build_id']         = $Element;
        $PLANET['tiles'][$tile]['build_end_time']   = $BuildEndTime;
        $PLANET['tiles'][$tile]['build_mode']       = $BuildMode;
        $PLANET['tiles'][$tile]['isUpdate']         = true;

        $this->queue();
	}
	 
	private function DoAddBuildingToQueue($Element, $AddMode = true)
    {
		global $PLANET, $USER, $resource, $reslist, $pricelist;

		if(!in_array($Element, $reslist['allow'][$PLANET['planet_type']])
			|| !BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element)
			|| (!$AddMode && $PLANET[$resource[$Element]] == 0)
		)
			return;

        foreach($reslist['lab'] as $lab) {
            if($Element == $lab && $USER["b_tech_planet"] != 0)
                return;
        }

        foreach($reslist['shipyard'] as $shipyard) {
            if($Element == $shipyard && !empty($PLANET['b_hangar_id']))
                return;
        }

		$CurrentQueue  		= unserialize($PLANET['b_building_id']);

		if (!empty($CurrentQueue)) {
			$ActualCount	= count($CurrentQueue);
		} else {
			$CurrentQueue	= array();
			$ActualCount	= 0;
		}

		$CurrentMaxFields  	= CalculateMaxPlanetFields($PLANET);

        $config	= Config::get();

		if (($AddMode && $PLANET["field_current"] >= ($CurrentMaxFields - $ActualCount)))
		{
			return;
		}

		foreach($CurrentQueue as $QueueSubArray)
		{
			if($QueueSubArray[0] == $Element)
				return;
		}

		$BuildMode 			= $AddMode ? 'build' : 'destroy';
		$BuildLevel			= $PLANET[$resource[$Element]] + (int) $AddMode;

		if($pricelist[$Element]['max'] < $BuildLevel)
			return;

		$costResources		= BuildFunctions::getElementPrice($USER, $PLANET, $Element, !$AddMode, $BuildLevel);

		if(!BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources))
			return;

		if(isset($costResources[901])) { $PLANET[$resource[901]]	-= $costResources[901]; }
		if(isset($costResources[902])) { $PLANET[$resource[902]]	-= $costResources[902]; }
		if(isset($costResources[903])) { $PLANET[$resource[903]]	-= $costResources[903]; }
		if(isset($costResources[921])) { $USER[$resource[921]]		-= $costResources[921]; }

		$elementTime    			= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $costResources);
		$BuildEndTime				= TIMESTAMP + $elementTime;
		$CurrentQueue[]				= array($Element, $BuildLevel, $elementTime, $BuildEndTime, $BuildMode);
		$PLANET['b_building_id']	= serialize($CurrentQueue);

		$b_building = 0;
		foreach($CurrentQueue as $key => $builElem)
		{
			$Element      	= $builElem[0];
			$BuildEndTime 	= $builElem[3];
			$BuildMode    	= $builElem[4];

			if ($BuildEndTime > TIMESTAMP){
				if($b_building == 0)
					$b_building = $BuildEndTime;
				elseif($b_building > $BuildEndTime)
					$b_building = $BuildEndTime;
			}
		}

		$PLANET['b_building']		= $BuildEndTime;
    }

    private function getQueueData()
    {
        global $LNG, $PLANET, $USER;
        $scriptData     = [];
        $buildQueue		= [];

        foreach ($PLANET['tiles'] as $tile => $buildElem){
            if($buildElem['build_end_time'] != 0 && $buildElem['build_end_time'] > TIMESTAMP ){
                $Element = $buildElem['build_id'];
                $BuildLevel = $buildElem['build_mode'] === 'destroy' ? $buildElem['build_lvl']-1 : $buildElem['build_lvl']+1;
                $elementTime = 0;
                $BuildEndTime = $buildElem['build_end_time'];
                $BuildMode = $buildElem['build_mode'];
                $buildQueue[] = array($Element, $BuildLevel, $elementTime, $BuildEndTime, $BuildMode, $tile);
            }
        }

        function cmp($a, $b)
        {
            return strcmp($a[3], $b[3]);
        }

        usort($buildQueue, "cmp");



        foreach($buildQueue as $BuildArray) {
            if ($BuildArray[3] < TIMESTAMP)
                continue;

            if ($PLANET['planet_type']==3){
                $dm_fast = floor(1000*($BuildArray[3]-TIMESTAMP)/3600);
            }
            else{
                $dm_fast = floor(200*($BuildArray[3]-TIMESTAMP)/3600);
            }

            $scriptData[] = array(
                'element'	=> $BuildArray[0],
                'name'      => $LNG['tech'][$BuildArray[0]],
                'level' 	=> $BuildArray[1],
                'time' 		=> $BuildArray[2],
                'resttime' 	=> ($BuildArray[3] - TIMESTAMP),
                'destroy' 	=> ($BuildArray[4] == 'destroy'),
                'endtime' 	=> _date('U', $BuildArray[3], $USER['timezone']),
                'display' 	=> _date($LNG['php_tdformat'], $BuildArray[3], $USER['timezone']),
                'need_dm' 	=> $dm_fast,
                'tile'      => $BuildArray[5],
            );
        }

        return array('queue' => $scriptData);
    }
	
	private static function getHighestLevelOfElement($QueueList, $ElementID, $default){
		$level = $default;
		foreach($QueueList as $queue){
			if($queue['element'] == $ElementID && $queue['level'] > $level){
				$level = $queue['level'];
			}
		}
		return $level;
	}

    public function tile()
    {
        global $PLANET;

        $data = $PLANET['tiles'];

        $this->data($data);
        exit();
    }

    public function queue()
    {
        $queueData	 		= $this->getQueueData();
        $Queue	 			= $queueData['queue'];

        $this->data($Queue);
        exit();
    }

	public function show()
	{
		global $ProdGrid, $LNG, $resource, $reslist, $PLANET, $USER, $pricelist, $requeriments, $THEME;
		
		$TheCommand		= HTTP::_JSON('cmd', '');

        $type			= HTTP::_JSON('type', 'normal');
        $tile			= HTTP::_JSON('tile', 1);

		// wellformed buildURLs
		if(!empty($TheCommand) && $_SERVER['REQUEST_METHOD'] === 'POST' && $USER['urlaubs_modus'] == 0)
		{
			$Element     	= HTTP::_JSON('building', 0);
			$ListID      	= HTTP::_GP('listid', 0);
			/*
			$lvlup      			= HTTP::_GP('lvlup', 0);
			$lvlup1      			= HTTP::_GP('lvlup1', 0);
			$levelToBuildInFo      	= HTTP::_GP('levelToBuildInFo', 0);
			*/

			switch($TheCommand)
			{
				case 'cancel':
					$this->CancelBuildingFromQueue();
				break;
				case 'insert':
					$this->AddBuildingToQueue($Element, true, $tile, $type);
				break;
				case 'destroy':
					$this->AddBuildingToQueue($Element, false, $tile, $type);
				break;
				case 'fast':
				    $this->FastBuildingFromQueue($Element);
				break;
			}

            $this->queue();
		}
        $config				= Config::get();

		$queueData	 		= $this->getQueueData();
		$Queue	 			= $queueData['queue'];
		$QueueCount			= count($Queue);
		$CanBuildElement 	= !isVacationMode($USER);

        if($type == 'normal'){

            $CurrentMaxFields   = CalculateMaxPlanetFields($PLANET);

            $RoomIsOk 			= $PLANET['field_current'] < ($CurrentMaxFields - $QueueCount);

            $BuildEnergy		= $USER[$resource[113]];
            $BuildLevelFactor   = 10;
            $BuildTemp          = $PLANET['temp_max'];

            $BuildInfoList      = array();

            $Elements			= $reslist['allow'][$PLANET['planet_type']];

            foreach($Elements as $Element)
            {
                if (!BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element))
                    continue;

                $infoEnergy	= "";

                if(isset($queueData['quickinfo'][$Element]))
                {
                    $levelToBuild	= $queueData['quickinfo'][$Element];
                }
                else
                {
                    $levelToBuild	= $PLANET[$resource[$Element]];
                }

                if(in_array($Element, $reslist['prod']))
                {
                    $BuildLevel	= $PLANET[$resource[$Element]];
                    $Need		= eval(ResourceUpdate::getProd($ProdGrid[$Element]['production'][911]));

                    $BuildLevel	= $levelToBuild + 1;
                    $Prod		= eval(ResourceUpdate::getProd($ProdGrid[$Element]['production'][911]));

                    $requireEnergy	= $Prod - $Need;
                    $requireEnergy	= round($requireEnergy * $config->energySpeed);

                    if($requireEnergy < 0) {
                        $infoEnergy	= sprintf($LNG['bd_need_engine'], pretty_number(abs($requireEnergy)), $LNG['tech'][911]);
                    } else {
                        $infoEnergy	= sprintf($LNG['bd_more_engine'], pretty_number(abs($requireEnergy)), $LNG['tech'][911]);
                    }
                }

                $costResources		= BuildFunctions::getElementPrice($USER, $PLANET, $Element, false, $levelToBuild+1);
                $costOverflow		= BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
                $elementTime    	= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $costResources);
                $destroyResources	= BuildFunctions::getElementPrice($USER, $PLANET, $Element, true);
                $destroyTime		= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $destroyResources);
                $destroyOverflow	= BuildFunctions::getRestPrice($USER, $PLANET, $Element, $destroyResources);
                $buyable			= $QueueCount != 0 || BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);

                $BuildInfoList[$Element]	= array(
                    'name'				=> $LNG['tech'][$Element] . (($levelToBuild>0) ? " (".$LNG['bd_lvl']." ".$levelToBuild.")" : ""),
                    'level'				=> $PLANET[$resource[$Element]],
                    'maxLevel'			=> $pricelist[$Element]['max'],
                    'infoEnergy'		=> $infoEnergy,
                    'costResources'		=> $costResources,
                    'costOverflow'		=> $costOverflow,
                    'elementTime'    	=> $elementTime,
                    'destroyResources'	=> $destroyResources,
                    'destroyTime'		=> $destroyTime,
                    'destroyOverflow'	=> $destroyOverflow,
                    'buyable'			=> $buyable,
                    'levelToBuild'		=> $levelToBuild,
                );
            }

        }else{
            $RoomIsOk 			= true;

            $BuildInfoList      = array();

            if($PLANET['tiles'][$tile]['id'] == 0){

                $Elements			= $reslist['allow'][$PLANET['planet_type']];

                foreach($Elements as $Element)
                {
                    if (!BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element))
                        continue;

                    $infoEnergy	= "";

                    if(isset($queueData['quickinfoTile'][$tile][$Element]))
                    {
                        $levelToBuild	= $queueData['quickinfoTile'][$tile][$Element];
                    }
                    else
                    {
                        $levelToBuild	= 0;
                    }

                    $costResources		= BuildFunctions::getElementPrice($USER, $PLANET, $Element, false, $levelToBuild+1);
                    $costOverflow		= BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
                    $elementTime    	= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $costResources);
                    $destroyResources	= BuildFunctions::getElementPrice($USER, $PLANET, $Element, true);
                    $destroyTime		= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $destroyResources);
                    $destroyOverflow	= BuildFunctions::getRestPrice($USER, $PLANET, $Element, $destroyResources);
                    $buyable			= $QueueCount != 0 || BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);

                    $BuildInfoList[$Element]	= array(
                        'name'				=> $LNG['tech'][$Element],
                        'image'				=> $THEME->getTheme().'gebaeude/'.$Element.'.gif',
                        'level'				=> 0,
                        'maxLevel'			=> $pricelist[$Element]['max'],
                        'infoEnergy'		=> sprintf($LNG['bd_more_engine'], 0, $LNG['tech'][911]),
                        'costResources'		=> $costResources,
                        'costOverflow'		=> $costOverflow,
                        'elementTime'    	=> $elementTime,
                        'destroyResources'	=> $destroyResources,
                        'destroyTime'		=> $destroyTime,
                        'destroyOverflow'	=> $destroyOverflow,
                        'buyable'			=> $buyable,
                        'levelToBuild'		=> $levelToBuild,
                    );
                }
            }else{
                $Elements			= $reslist['allow'][$PLANET['planet_type']];

                foreach($Elements as $Element)
                {
                    if (!BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element))
                        continue;

                    if($Element != $PLANET['tiles'][$tile]['build_id'])
                        continue;

                    $infoEnergy	= "";

                    if(isset($queueData['quickinfoTile'][$tile][$Element]))
                    {
                        $levelToBuild	= $queueData['quickinfoTile'][$tile][$Element];
                    }
                    else
                    {
                        $levelToBuild	= $PLANET['tiles'][$tile]['build_lvl'];
                    }

                    $costResources		= BuildFunctions::getElementPrice($USER, $PLANET, $Element, false, $levelToBuild+1);
                    $costOverflow		= BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
                    $elementTime    	= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $costResources);
                    $destroyResources	= BuildFunctions::getElementPrice($USER, $PLANET, $Element, true);
                    $destroyTime		= BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $destroyResources);
                    $destroyOverflow	= BuildFunctions::getRestPrice($USER, $PLANET, $Element, $destroyResources);
                    $buyable			= $QueueCount != 0 || BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);

                    $BuildInfoList[$Element]	= array(
                        'name'				=> $LNG['tech'][$Element]. " (".$LNG['bd_lvl']." ".$levelToBuild.")",
                        'image'				=> $THEME->getTheme().'gebaeude/'.$Element.'.gif',
                        'level'				=> $levelToBuild,
                        'maxLevel'			=> $pricelist[$Element]['max'],
                        'infoEnergy'		=> sprintf($LNG['bd_more_engine'], 0, $LNG['tech'][911]),
                        'costResources'		=> $costResources,
                        'costOverflow'		=> $costOverflow,
                        'elementTime'    	=> $elementTime,
                        'destroyResources'	=> $destroyResources,
                        'destroyTime'		=> $destroyTime,
                        'destroyOverflow'	=> $destroyOverflow,
                        'buyable'			=> $buyable,
                        'levelToBuild'		=> $levelToBuild,
                    );
                }
            }
        }

		$this->assign(array(
            'HaveMissiles'		    => (bool) $PLANET[$resource[503]] + $PLANET[$resource[502]],
			'BuildInfoList'		    => $BuildInfoList,
			'CanBuildElement'	    => $CanBuildElement,
			'RoomIsOk'			    => $RoomIsOk,
			'Queue'				    => $Queue,
            'tile'				    => $tile,
            'type'				    => $type,
            'pageName'			    => $LNG['lm_buildings'],
			'isBusy'			    => array('shipyard' => !empty($PLANET['b_hangar_id']), 'research' => $USER['b_tech_planet'] != 0),
            'need_dm'		        => floor(10 + ((400*($PLANET['b_building']-TIMESTAMP))/3600)),	
			'field_used'		    => $PLANET['field_current'],
			'field_max'		        => CalculateMaxPlanetFields($PLANET),
			'field_left'		    => CalculateMaxPlanetFields($PLANET) - $PLANET['field_current'],
			'field_percent'		    => $PLANET['field_current'] * 100 / CalculateMaxPlanetFields($PLANET),
			'planet_field_current' 	=> $PLANET['field_current'],
			'planet_field_max' 		=> CalculateMaxPlanetFields($PLANET),
			'raz'					=> max(0, min(100,round($PLANET['field_current']/max(1,(CalculateMaxPlanetFields($PLANET)))*100))),
		));
			
		$this->display('page.buildings.default.tpl');
	}
}