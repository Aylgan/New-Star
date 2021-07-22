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

class ShowResourcesPage extends AbstractGamePage
{
	public static $requireModule = MODULE_RESSOURCE_LIST;

	function __construct() 
	{
		parent::__construct();
	}
    
    function AllPlanets()
	{
		global $reslist, $resource, $USER, $PLANET, $LNG;
        
		$db = Database::get();
		$action = HTTP::_GP('action','');
			
		if ($action == 'on'){
            
            $sql = "UPDATE %%PLANETS%% SET
				last_update 		= :last_update
				WHERE id_owner = :userID;";
			$db->update($sql, array(
				':last_update'	=> TIMESTAMP,
				':userID'		=> $USER['id']
            ));	
            
            foreach($reslist['prod'] as $ProdID)
            { 
                $sql .= "UPDATE %%PLANETS%% SET
                    ".$resource[$ProdID]."_porcent = '11'
					WHERE id_owner = :userID;";
                $db->update($sql, array(
                    ':last_update'	=> TIMESTAMP,
                    ':userID'		=> $USER['id']
                ));	
            }

			$PLANET['last_update']	= TIMESTAMP;
			$this->ecoObj->setData($USER, $PLANET);
			$this->ecoObj->ReBuildCache();
			list($USER, $PLANET)	= $this->ecoObj->getData();
			$PLANET['eco_hash'] = $this->ecoObj->CreateHash();
			$this->save();
			
			$sql	= 'SELECT * FROM %%PLANETS%% WHERE id = :planetId;';
			$getPlanet = Database::get()->selectSingle($sql, array(
				':planetId'		=> $PLANET['id'],
			));
			
			$this->printMessage($LNG['res_cl_activate'], true, array('game.php?page=resources', 2));
            
		}elseif ($action == 'off'){
            
            $sql = "UPDATE %%PLANETS%% SET
				last_update 		= :last_update
				WHERE id_owner = :userID;";
			$db->update($sql, array(
				':last_update'	=> TIMESTAMP,
				':userID'		=> $USER['id']
            ));	
            
            foreach($reslist['prod'] as $ProdID)
            { 
                $sql .= "UPDATE %%PLANETS%% SET
                    ".$resource[$ProdID]."_porcent = '0'
					WHERE id_owner = :userID;";
                $db->update($sql, array(
                    ':last_update'	=> TIMESTAMP,
                    ':userID'		=> $USER['id']
                ));	
            }
            
			$this->ecoObj->setData($USER, $PLANET);
			$this->ecoObj->ReBuildCache();
			list($USER, $PLANET)	= $this->ecoObj->getData();
			$PLANET['eco_hash'] = $this->ecoObj->CreateHash();
			$this->save();
			
			$sql	= 'SELECT * FROM %%PLANETS%% WHERE id = :planetId;';
			$getPlanet = Database::get()->selectSingle($sql, array(
				':planetId'		=> $PLANET['id'],
			));
			
			$this->printMessage($LNG['res_cl_dactivate'], true, array('game.php?page=resources', 2));
		}
		
	}
	
	function send()
	{
		global $resource, $USER, $PLANET;
		if ($USER['urlaubs_modus'] == 0)
		{
			$updateSQL	= array();
			if(!isset($_POST['prod']))
				$_POST['prod'] = array();


			$param	= array(':planetId' => $PLANET['id']);
			
			foreach($_POST['prod'] as $resourceId => $Value)
			{
				$FieldName = $resource[$resourceId].'_porcent';
				if (!isset($PLANET[$FieldName]) || !in_array($Value, range(0, 10)))
					continue;
				
				$updateSQL[]	= $FieldName." = :".$FieldName;
				$param[':'.$FieldName]		= (int) $Value;
				$PLANET[$FieldName]			= $Value;
			}

			if(!empty($updateSQL))
			{
				$sql	= 'UPDATE %%PLANETS%% SET '.implode(', ', $updateSQL).' WHERE id = :planetId;';

				Database::get()->update($sql, $param);

				$this->ecoObj->setData($USER, $PLANET);
				$this->ecoObj->ReBuildCache();
				list($USER, $PLANET)	= $this->ecoObj->getData();
				$PLANET['eco_hash'] = $this->ecoObj->CreateHash();
			}
		}

		$this->save();
		$this->redirectTo('game.php?page=resources');
	}

    function getRes()
    {
        global $resource, $USER, $PLANET, $ProdGrid, $reslist;

        $this->setWindow('ajax');

        $tile 		= HTTP::_GP('tile', 0);
        $build 		= HTTP::_GP('build', 0);

        $config	= Config::get();

        $time = TIMESTAMP;

        if ($USER['urlaubs_modus'] == 0)
        {
            if(!in_array($build, $reslist['prod']))
                exit;

            $ressIDs	= array_merge(array(), $reslist['resstype'][1], $reslist['resstype'][2]);

            $temp	= array(
                901	=> array(
                    'plus'	=> 0,
                    'minus'	=> 0,
                ),
                902	=> array(
                    'plus'	=> 0,
                    'minus'	=> 0,
                ),
                903	=> array(
                    'plus'	=> 0,
                    'minus'	=> 0,
                ),
            );

            $BuildLevelFactor	= 10;
            $BuildLevel 		= $PLANET['tiles'][$tile]['build_lvl'];
            $BuildTemp		= $PLANET['temp_max'];

            foreach($ressIDs as $ID)
            {
                if(!isset($ProdGrid[$build]['production'][$ID]))
                    continue;

                $Production	= eval("return ".$ProdGrid[$build]['production'][$ID].";");

                if($Production > 0) {
                    $temp[$ID]['plus']	+= $Production;
                } else {
                    if(in_array($ID, $reslist['resstype'][1]) && $PLANET[$resource[$ID]] == 0) {
                        continue;
                    }

                    $temp[$ID]['minus']	+= $Production;
                }
            }

            $metal_perhour		= ($temp[901]['plus'] * (1 + $USER['factor']['Resource'] + 0.02 * $USER[$resource[131]]) + $temp[901]['minus']) * $config->resource_multiplier;
            $crystal_perhour	= ($temp[902]['plus'] * (1 + $USER['factor']['Resource'] + 0.02 * $USER[$resource[131]]) + $temp[902]['minus']) * $config->resource_multiplier;
            $deuterium_perhour 	= ($temp[903]['plus'] * (1 + $USER['factor']['Resource'] + 0.02 * $USER[$resource[131]]) + $temp[903]['minus']) * $config->resource_multiplier;

            $prodTime = min(($time - (int)$PLANET['tiles'][$tile]['lastupdate']),3600*$PLANET['tiles'][$tile]['upgrade_1']);

            if($prodTime < 30)
                exit;

            $MetalTheoretical		= floor($prodTime * $metal_perhour / 3600);
            $CristalTheoretical		= floor($prodTime * $crystal_perhour / 3600);
            $DeuteriumTheoretical	= floor($prodTime * $deuterium_perhour / 3600);

            $oldMetal = $PLANET['metal'];
            $oldCrystal = $PLANET['crystal'];
            $oldDeuterium = $PLANET['deuterium'];

            if($MetalTheoretical != 0)
            {
                $PLANET['metal']      = max($PLANET['metal'] + $MetalTheoretical, 0);
                $restype = 901;
                $rescount = $PLANET['metal']-$oldMetal;
            }
            if($CristalTheoretical != 0)
            {
                $PLANET['crystal']      = max($PLANET['crystal'] + $CristalTheoretical, 0);
                $restype = 902;
                $rescount = $PLANET['crystal']-$oldCrystal;
            }
            if($DeuteriumTheoretical != 0)
            {
                $PLANET['deuterium']      = max($PLANET['deuterium'] + $DeuteriumTheoretical, 0);
                $restype = 903;
                $rescount = $PLANET['deuterium']-$oldDeuterium;
            }

            $tparams	= array(
                ':lastupdate' => $time,
                ':planetId' => $PLANET['id'],
                ':tile' => $tile
            );

            $tSql	= "UPDATE %%BUILDS%% SET `lastupdate` = :lastupdate WHERE planet = :planetId AND tile = :tile;";

            Database::get()->update($tSql, $tparams);

            $PlanetRess	= new ResourceUpdate();

            list($USER, $PLANET)	= $PlanetRess->CalcResource($USER, $PLANET, true);

            $arr = array(
                "e" => $tile,
                "r" => $build,
                "metal"	=> floor($PLANET['metal']),
                "crystal"	=> floor($PLANET['crystal']),
                "deuterium"	=> floor($PLANET['deuterium']),
                "time"		=> $time,
                "oldMetal"	=> 	$oldMetal,
                "oldCrystal"	=> 	$oldCrystal,
                "oldDeuterium"	=> 	$oldDeuterium,
                "restype"		=> $restype,
                "rescount"		=> floor($rescount),
            );

            $this->sendJSON($arr);
        }
    }

    function getCurrentResources(){
        global $USER, $PLANET, $reslist, $resource;

        $config			= Config::get();

        $resourceTable	= array();
        $resourceSpeed	= $config->resource_multiplier;
        foreach($reslist['resstype'][1] as $resourceID)
        {
            $resourceTable[$resourceID]['name']			= $resource[$resourceID];
            $resourceTable[$resourceID]['current']		= (int)$PLANET[$resource[$resourceID]];
            $resourceTable[$resourceID]['max']			= (int)$PLANET[$resource[$resourceID].'_max'];
            if($USER['urlaubs_modus'] == 1 || $PLANET['planet_type'] != 1)
            {
                $resourceTable[$resourceID]['production']	= (int)$PLANET[$resource[$resourceID].'_perhour'];
            }
            else
            {
                $resourceTable[$resourceID]['production']	= (int)$PLANET[$resource[$resourceID].'_perhour'] + $config->{$resource[$resourceID].'_basic_income'} * $resourceSpeed;
            }
        }

        foreach($reslist['resstype'][2] as $resourceID)
        {
            $resourceTable[$resourceID]['name']			= $resource[$resourceID];
            $resourceTable[$resourceID]['used']			= (int)$PLANET[$resource[$resourceID].'_used'];
            $resourceTable[$resourceID]['max']			= (int)$PLANET[$resource[$resourceID]];
        }

        foreach($reslist['resstype'][3] as $resourceID)
        {
            $resourceTable[$resourceID]['name']			= $resource[$resourceID];
            $resourceTable[$resourceID]['current']		= (int)$USER[$resource[$resourceID]];
        }

        $this->data($resourceTable);
    }

	function show()
	{
		global $LNG, $ProdGrid, $resource, $reslist, $USER, $PLANET, $resglobal;

		$config	= Config::get();

        foreach(array_merge($reslist['resstype'][1], $reslist['resstype'][2]) as $res) {		
            $basicIncome[$res]	= $config->{$resource[$res].'_basic_income'};
        }
        
        foreach($reslist['planet_no_basic'] as $planetNoBasic) {
            foreach(array_merge($reslist['resstype'][1], $reslist['resstype'][2]) as $res) {
                if($USER['urlaubs_modus'] == 1 || $PLANET['planet_type'] == $planetNoBasic){
                    $basicIncome[$res]	= 0;
                }
            }
        }
        
        include('includes/subclasses/subclass.Temp.php');
		
		$ressIDs		= array_merge(array(), $reslist['resstype'][1], $reslist['resstype'][2]);

		$productionList	= array();

        if($PLANET[''.$resource[$resglobal['stop_product']].'_used'] != 0) {
            $prodLevel	= min(1, $PLANET[''.$resource[$resglobal['stop_product']].''] / abs($PLANET[''.$resource[$resglobal['stop_product']].'_used']));
        } else {
            $prodLevel	= 0;
        }

		$BuildTemp          = $PLANET['temp_max'];
        
		foreach($reslist['prod'] as $ProdID)
		{
			if(isset($PLANET[$resource[$ProdID]]) && $PLANET[$resource[$ProdID]] == 0)
				continue;

			if(isset($USER[$resource[$ProdID]]) && $USER[$resource[$ProdID]] == 0)
				continue;

			$productionList[$ProdID]	= array(
				'production'	=> $reslist['res_production'], //временно!!!
				'elementLevel'	=> $PLANET[$resource[$ProdID]],
				'prodLevel'		=> $PLANET[$resource[$ProdID].'_porcent'],
			);

			$BuildLevel			= $PLANET[$resource[$ProdID]];
			$BuildLevelFactor	= $PLANET[$resource[$ProdID].'_porcent'];

			foreach($ressIDs as $ID) 
			{
				if(!isset($ProdGrid[$ProdID]['production'][$ID]))
					continue;

				$Production	= eval(ResourceUpdate::getProd($ProdGrid[$ProdID]['production'][$ID]));

				if(in_array($ID, $reslist['resstype'][2]))
				{
					$Production	*= $config->energySpeed;
				}
				else
				{
					$Production	*= $prodLevel * $config->resource_multiplier;
				}
				
				$productionList[$ProdID]['production'][$ID]	= $Production;
				
				if($Production > 0) {
					if($PLANET[$resource[$ID]] == 0) continue;
					
					$temp[$ID]['plus']	+= $Production;
				} else {
					$temp[$ID]['minus']	+= $Production;
				}
			}
		}

        $storage	        = array();
		$basicProduction	= array();
		$totalProduction	= array();
		$bonusProduction	= array();
		$dailyProduction	= array();
		$weeklyProduction	= array();
        
        foreach($reslist['resstype'][1] as $resP)
        { 
            $storage	        += array(
			$resP => ($PLANET[$resource[$resP].'_max']));
            
            $basicProduction	+= array(
			$resP => $basicIncome[$resP] * $config->resource_multiplier);
            
            $totalProduction	+= array(
			$resP => $PLANET[$resource[$resP].'_perhour'] + $basicProduction[$resP]);
            
            $bonusProduction	+= array(
			$resP => $temp[$resP]['plus'] * ($USER['factor']['Resource'] + $USER['factor']['P'.$resource[$resP].'']));
            
            $dailyProduction	+= array(
			$resP => $totalProduction[$resP] * 24);
		
            $weeklyProduction	+= array(
			$resP => $totalProduction[$resP] * 168);
        }
        
        foreach($reslist['resstype'][2] as $resS)
        { 
            $basicProduction	+= array(
			$resS => $basicIncome[$resS] * $config->resource_multiplier);
            
            $totalProduction	+= array(
			$resS => $PLANET[$resource[$resS]] + $basicProduction[$resS] + $PLANET[$resource[$resS].'_used']);
            
            $bonusProduction	+= array(
			$resS => $temp[$resS]['plus'] * $USER['factor']['S'.$resource[$resS].'']);
            
            $dailyProduction	+= array(
			$resS => $totalProduction[$resS]);
		
            $weeklyProduction	+= array(
			$resS => $totalProduction[$resS]);
        }

		$prodSelector	= array();
		
		foreach(range(10, 0) as $percent) {
			$prodSelector[$percent]	= ($percent * 10).'%';
		}
		
		$this->assign(array(
            'resstype1'         => $reslist['resstype'][1],
            'resstype2'         => $reslist['resstype'][2],
			'header'			=> sprintf($LNG['rs_production_on_planet'], $PLANET['name']),
			'prodSelector'		=> $prodSelector,
			'productionList'	=> $productionList,
			'basicProduction'	=> $basicProduction,
			'totalProduction'	=> $totalProduction,
			'bonusProduction'	=> $bonusProduction,
			'dailyProduction'	=> $dailyProduction,
			'weeklyProduction'	=> $weeklyProduction,
			'storage'			=> $storage,
		));
		
		$this->display('page.resources.default.tpl');
	}
}
