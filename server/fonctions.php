<?php
class APIRiot
{
	const API_KEY = "API_KEY";
	public $_ARR_PLATEFORMS = array(
			"BR" => "BR1",
			"EUNE" => "EUN1",
			"EUW" => "EUW1",
			"JP" => "JP1",
			"KR" => "KR",
			"LAN" => "LA1",
			"LAS" => "LA2",
			"NA" => "NA1",
			"OCE" => "OC1",
			"TR" => "TR1",
			"RU" => "RU",
			"PBE" => "PBE1"
		);
	private $_URL_STATIC_CHAMPION;
	private $_URL_SUMMONER_NAME;
	private $_URL_MASTERIES_ALL;
	private $_region;
	private $_pseudo;
	private $_plateform;
	private $_summoner_id;
	private $_data_champion;
	private $_version;
	
	public function __construct($pseudo, $region)
	{
		$this->_URL_STATIC_CHAMPION = "https://global.api.pvp.net/api/lol/static-data/%s/v1.2/champion?locale=fr_FR&champData=all&api_key=".self::API_KEY;
		$this->_URL_SUMMONER_NAME = "https://%s.api.pvp.net/api/lol/%s/v1.4/summoner/by-name/%s?api_key=".self::API_KEY;
		$this->_URL_MASTERIES_ALL = "https://%s.api.pvp.net/championmastery/location/%s/player/%s/champions?api_key=".self::API_KEY;
		$this->_URL_SPRITE = "http://ddragon.leagueoflegends.com/cdn/%s/img/sprite/%s";
		$this->_URL_SPLASHART = "http://ddragon.leagueoflegends.com/cdn/img/champion/splash/%s";
		$this->_URL_VERSION = "https://global.api.pvp.net/api/lol/static-data/%s/v1.2/realm?api_key=".self::API_KEY;
		$this->_pseudo = $pseudo;
		$this->_region = strtolower($region);
		$this->_file_champions = "data/champions.json";
		$this->_file_version = "data/version.txt";
		$this->_dir_data = "data";
		$this->_dir_images = "data/images";
		$this->_dir_sprite = $this->_dir_images."/sprite";
		$this->_dir_splashart = $this->_dir_images."/splashart";
		$this->_dir_to_create = array($this->_dir_data, $this->_dir_images, $this->_dir_splashart, $this->_dir_sprite);
	}
	
	/*
	 * Function update()
	 * Create directories for the project and update different files like icone or champion data
	 * return : (bool) worked ?
	 */
	public function update()
	{
		$request = self::request(sprintf($this->_URL_STATIC_CHAMPION, $this->_region));
		foreach($this->_dir_to_create as $dir)
		{
			if(file_exists($dir))
			{
				if(is_dir($dir))
				{
					foreach(scandir($dir) as $file)
					{
						if($file != "." && $file != ".." && is_file($dir."/".$file))
							unlink($dir."/".$file);
					}
				}
				else
					unlink($dir);
			}
			else
				mkdir($dir);
		}
		$this->_version = $this->getVersion();
		file_put_contents($this->_file_version, $this->_version);
		
		if($request !== false)
		{
			file_put_contents($this->_file_champions, $request);
			$champ_data = json_decode($request);
			foreach($champ_data->data as $champ)
			{
				if(!file_exists($this->_dir_sprite."/".$champ->image->sprite))
					self::download(sprintf($this->_URL_SPRITE, $this->_version, $champ->image->sprite), $this->_dir_sprite."/".$champ->image->sprite);
			}
		}
		return $this->load();
	}
	
	/*
	 * Function load()
	 * Check if there is the last update and initialize the object : get the summoner id and the version
	 * return : (bool) worked ?
	 */
	public function load()
	{
		if(file_exists("data/version.txt"))
		{
			$this->_version = $this->getVersion();
			if($this->_version == file_get_contents($this->_file_version))
			{
				$this->_summoner_id = $this->getSummonerId();
				$this->_data_champion = json_decode(file_get_contents($this->_file_champions));
				return !($this->_summoner_id == "" || $this->_version == "");
			}
			else
				return $this->update();
		}
		else
			return $this->update();
	}
	
	/*
	 * Function getPlateform(string region)
	 * Return the plateform of the region
	 * return : (string) region
	 */
	public function getPlateform($region)
	{
		return $this->_ARR_PLATEFORMS[$region];
	}
	
	/*
	 * Function getMasteries()
	 * Request and analyze some data from Riot with the summoner name and the region
	 * return : (string) data in JSON
	 */
	public function getMasteries()
	{
		$request = self::request(sprintf($this->_URL_MASTERIES_ALL, $this->_region, $this->getPlateform(strtoupper($this->_region)), $this->_summoner_id));
		if($request !== false)
		{
			$json = json_decode($request);
			$arr_level = array();
			$tot_points = 0;
			$arr_final = array();
			$compte = 0;
			$somme_diff = 0;
			$somme_attack = 0;
			$somme_defense = 0;
			$somme_magic = 0;
			$somme_pond_diff = 0;
			foreach($json as $key => $champ_std)
			{
				$id_champ = $champ_std->championId;
				$name_champ = $this->_data_champion->keys->$id_champ;
				$arr_final[$key] = array(
					"championLevel" => $champ_std->championLevel,
					"championPoints" => $champ_std->championPoints,
					"champion" => array(
						"tags" => $this->_data_champion->data->$name_champ->tags,
						"image" => $this->_data_champion->data->$name_champ->image,
						"info" => $this->_data_champion->data->$name_champ->info,
						"name" => $this->_data_champion->data->$name_champ->name,
						"key" => $this->_data_champion->data->$name_champ->key,
						"title" => $this->_data_champion->data->$name_champ->title
					)
				);
				if(isset($champ_std->highestGrade))
					$arr_final[$key]["highestGrade"] = $champ_std->highestGrade;
				else
					$arr_final[$key]["highestGrade"] = "N/A";
				if(!array_key_exists($champ_std->championLevel, $arr_level))
					$arr_level[$champ_std->championLevel] = 1;
				else
					$arr_level[$champ_std->championLevel]++;
				$somme_diff += $this->_data_champion->data->$name_champ->info->difficulty * $champ_std->championPoints;
				$somme_attack += $this->_data_champion->data->$name_champ->info->attack * $champ_std->championPoints;
				$somme_defense += $this->_data_champion->data->$name_champ->info->defense * $champ_std->championPoints;
				$somme_magic += $this->_data_champion->data->$name_champ->info->magic * $champ_std->championPoints;
				$somme_pond_diff += $this->_data_champion->data->$name_champ->info->difficulty * $champ_std->championPoints;
				$tot_points += $champ_std->championPoints;
				$compte++;
			}
			return json_encode(
				array(
					"champions" => $arr_final,
					"stats" => array(
						"compte" => $compte,
						"somme_diff" => $somme_diff,
						"somme_attack" => $somme_attack,
						"somme_defense" => $somme_defense,
						"somme_magic" => $somme_magic,
						"somme_pond_diff" => $somme_pond_diff,
						"count_level" => $arr_level,
						"tot_points" => $tot_points
					)
				)
			);
		}
		else
			return "";
	}

	/*
	 * Function getSummonerId()
	 * Get the summoner Id from a previous request or from a fresh request
	 * return : (string) summoner ID or (string) empty if it failed
	 */
	public function getSummonerId()
	{
		if($this->_summoner_id != "")
			return $this->_summoner_id;
		else
		{
			$name = $this->_pseudo;
			$retour = self::request(sprintf($this->_URL_SUMMONER_NAME, $this->_region, $this->_region, $name));
			if($retour !== false)
			{
				$json = json_decode($retour);
				return $json->$name->id;
			}
			else
				return "";
		}
	}
	
	/*
	 * Function getVersion()
	 * Get the version from a previous request or from a fresh request
	 * return : (string) version or (string) empty if it failed
	 */
	public function getVersion()
	{
		if($this->_version != "")
			return $this->_version;
		else
		{
			$name = $this->_pseudo;
			$retour = self::request(sprintf($this->_URL_VERSION, $this->_region));
			if($retour !== false)
			{
				$json = json_decode($retour);
				return $json->v;
			}
			else
				return "";
		}
	}
	
	/********************* OTHERS FUNCTIONS USEFUL FOR THE PROJECT **************************/
	/*
	 * Function request(string $url)
	 * Make a HTTP Request
	 * return : (string) response or (bool) === false if it failed
	 */
	public static function request($url)
	{
		@$retour = file_get_contents($url);
		if($retour == "")
			return false;
		else
			return $retour;
	}
	
	/*
	 * Function download(string $url, string $path)
	 * Download a file from the $url and copy it to $path (+filename)
	 * return : void
	 */
	public static function download($url, $path)
	{
		file_put_contents($path, file_get_contents($url));
	}
}
?>