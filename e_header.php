<?php

/**
 * @file
 * This file is loaded in the header of each page of your site.
 */

if(!defined('e107_INIT'))
{
	exit;
}

// [PLUGINS]/e107projects/languages/[LANGUAGE]/[LANGUAGE]_front.php
e107::lan('e107projects', false, true);


/**
 * Class e107projects_header.
 */
class e107projects_header
{

	/**
	 * Plugin preferences.
	 *
	 * @var array
	 */
	private $plugPrefs = array();

	/**
	 * @var bool
	 */
	private $needCSS = false;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->plugPrefs = e107::getPlugConfig('e107projects')->getPref();

		if(USER_AREA && defset('e_PAGE') != 'usersettings.php' && $this->incompleteUserAccount())
		{
			// TODO - alert.
			// e107::getMessage()->add(LAN_E107PROJECTS_FRONT_05, E_WARNING, true);
			e107::redirect('/usersettings.php');
		}

		if(USER_AREA && e107::getMenu()->isLoaded('e107projects_openlayers'))
		{
			$this->loadOpenLayers();
		}

		if(USER_AREA && e107::getMenu()->isLoaded('e107projects_summary'))
		{
			$this->needCSS = true;
		}

		if(defset('e_PAGE') == 'usersettings.php')
		{
			$this->loadGeoComplete();
		}

		if(defset('e_URL_LEGACY') == 'e107_plugins/e107projects/submit.php' || defset('e_URL_LEGACY') == 'e107_plugins/e107projects/projects.php'
		)
		{
			e107::js('e107projects', 'js/e107projects.submit.js');
			$this->needCSS = true;
		}

		$this->needCSS = true;
		e107::js('footer', '{e_PLUGIN}e107projects/js/e107projects.nodejs.js', 'jquery', 5);

		if($this->needCSS === true)
		{
			e107::css('e107projects', 'css/styles.css');
		}
	}

	/**
	 * Check if account is incomplete.
	 *
	 * @return bool
	 */
	public function incompleteUserAccount()
	{
		$db = e107::getDb();
		$user = e107::getUser();

		if($user->isGuest())
		{
			return false;
		}

		$uid = (int) $user->getId();
		$location = $db->retrieve('user_extended', 'user_plugin_e107projects_location', 'user_extended_id = ' . $uid);

		return empty($location);
	}

	/**
	 * Load OpenLayers library.
	 */
	public function loadOpenLayers()
	{
		if(($library = e107::library('load', 'openlayers')) && !empty($library['loaded']))
		{
			e107::js('e107projects', 'js/e107projects.openlayers.js');
			$this->needCSS = true;

			// FIXME - Move this to an async Ajax request?

			$db = e107::getDb();
			$db->gen("SELECT l.location_lat, l.location_lon FROM #user_extended AS ue 
			LEFT JOIN #e107projects_location AS l ON l.location_name = ue.user_plugin_e107projects_location");

			$locations = array();
			while($row = $db->fetch())
			{
				$locations[] = array(
					'lat' => $row['location_lat'],
					'lon' => $row['location_lon'],
				);
			}

			e107::js('settings', array(
				'e107projects' => array(
					'marker'    => SITEURL . e_PLUGIN . 'e107projects/images/marker.png',
					'locations' => $locations,
				),
			));
		}
	}

	/**
	 * Load GeoComplete library.
	 */
	public function loadGeoComplete()
	{
		$apiKey = varset($this->plugPrefs['google_places_api_key']);

		if(empty($apiKey))
		{
			return;
		}

		$query = array(
			'key'       => $apiKey,
			'language'  => defset('e_LAN', 'en'),
			'libraries' => 'places',
		);

		$url = 'https://maps.googleapis.com/maps/api/js?' . http_build_query($query);

		e107::js('url', $url, array('zone' => 2));

		if(($library = e107::library('load', 'geocomplete', 'minified')) && !empty($library['loaded']))
		{
			e107::js('e107projects', 'js/e107projects.geocomplete.js');
		}
	}

}


new e107projects_header();
