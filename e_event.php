<?php

/**
 * @file
 *
 */

if(!defined('e107_INIT'))
{
	exit;
}


/**
 * Class e107projects_event.
 */
class e107projects_event
{

	/**
	 * Configure functions/methods to run when specific e107 events are triggered.
	 *
	 * @return array
	 */
	function config()
	{
		$event = array();
		
		// After XUP login.
		$event[] = array(
			'name'     => 'user_xup_profile_updated',
			'function' => 'e107projects_user_xup_profile_updated_callback',
		);

		// After a user updated his profile.
		$event[] = array(
			'name'     => 'postuserset', // TODO this may change in core.
			'function' => 'e107projects_user_settings_changed_callback',
		);

		// After a project has been submitted.
		$event[] = array(
			'name'     => 'e107projects_user_project_submitted',
			'function' => 'e107projects_user_project_submitted_callback',
		);

		// After a Github Push Webhook IPN has arrived.
		$event[] = array(
			'name'     => 'e107projects_webhook_push',
			'function' => 'e107projects_webhook_push_callback',
		);

		return $event;
	}

	/**
	 * After login / xup login.
	 * 
	 * @param $data
	 */
	function e107projects_user_xup_profile_updated_callback($data)
	{
		// Common functions.
		e107_require_once(e_PLUGIN . 'e107projects/includes/e107projects.common.php');
		// Try to update Access Token on hooks (are saved in database)
		// belong to the logged in user.
		e107projects_update_access_token(null, $data['user_id']);
	}

	/**
	 * After updating user settings. Try to geocode user's location.
	 *
	 * @param $data
	 */
	function e107projects_user_settings_changed_callback($data)
	{
		if(!varset($data['ue']['user_plugin_e107projects_location'], false))
		{
			return;
		}

		e107_require_once(e_PLUGIN . 'e107projects/includes/e107projects.geocode.php');
		$geo = new e107projectsGeocode();

		if($geo->isGeocoded($data['ue']['user_plugin_e107projects_location']))
		{
			return;
		}

		$details = $geo->geocodeAddress($data['ue']['user_plugin_e107projects_location']);

		if(!$details)
		{
			return;
		}

		$db = e107::getDb();
		$tp = e107::getParser();

		$insert = array(
			'data' => array(
				'location_name' => $tp->toDB($data['ue']['user_plugin_e107projects_location']),
				'location_lat'  => $details['lat'],
				'location_lon'  => $details['lng'],
			),
		);

		$db->insert('e107projects_location', $insert, false);
	}

	/**
	 * After project submission.
	 *
	 * @param $data
	 */
	function e107projects_user_project_submitted_callback($data)
	{
		$user_id = (int) $data['project_author'];

		if($user_id > 0)
		{
			e107_require_once(e_PLUGIN . 'nodejs/nodejs.main.php');

			// TODO - more details?
			$subject = LAN_PLUGIN_E107PROJECTS_SUBMIT_SUCCESS_SUBJECT;
			$message = LAN_PLUGIN_E107PROJECTS_SUBMIT_SUCCESS_MESSAGE;

			$package = (object) array(
				'channel'  => 'nodejs_user_' . $user_id,
				'callback' => 'e107projectsNotify',
				'type'     => 'projectSubmitted',
				'subject'  => $subject,
				'markup'   => $message,
			);

			nodejs_enqueue_message($package);
		}
	}

	/**
	 * Any Git push to a Repository, including editing tags or branches.
	 * Commits via API actions that update references are also counted.
	 *
	 * @param array $data
	 *  Payload data.
	 */
	function e107projects_webhook_push_callback($data)
	{
		// Common functions.
		e107_require_once(e_PLUGIN . 'e107projects/includes/e107projects.common.php');
		// Helper functions for event callbacks.
		e107_require_once(e_PLUGIN . 'e107projects/includes/e107projects.event.php');

		// Repository pushed to.
		$repository = varset($data['repository'], false);

		// Try to update project details in database.
		if(varset($repository['id'], false))
		{
			// Github API data is cached, and will be updated later...
			// So it makes no sense to update project here...
			// e107projects_update_project($repository['id']);
			// TODO
			// update project and contribution details with data from
			// webhook payload.
		}

		// Send broadcast notification.
		e107projects_webhook_push_notification($data);
	}

}
