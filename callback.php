<?php

/**
 * @file
 * GitHub Webhook handler.
 */

if(!defined('e107_INIT'))
{
	require_once('../../class2.php');
}

if(!e107::isInstalled('e107projects'))
{
	exit;
}

define('E107PROJECTS_CALLBACK_DEBUG', false);


/**
 * Class e107ProjectsCallback.
 */
class e107ProjectsCallback
{

	/**
	 * Plugin preferences.
	 *
	 * @var
	 */
	private $plugPrefs;

	/**
	 * @var
	 */
	private $payload;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->plugPrefs = e107::getPlugConfig('e107projects')->getPref();

		if(!$this->isValid())
		{
			echo 'It works!';
			exit;
		}

		$event = e107::getEvent();

		$this->debugMsg($this->payload);

		switch(strtolower($_SERVER['HTTP_X_GITHUB_EVENT']))
		{
			case 'ping':
				$event->trigger('e107projects_webhook_ping', $this->payload);
				echo 'pong';
				break;

			// Any Git push to a Repository, including editing tags or branches.
			// Commits via API actions that update references are also counted.
			// This is the default event.
			case 'push':
				$event->trigger('e107projects_webhook_push', $this->payload);
				break;

			// Any time a Commit is commented on.
			case 'commit_comment':
				$event->trigger('e107projects_webhook_commit_comment', $this->payload);
				break;

			// Any time a Branch or Tag is created.
			case 'create':
				$event->trigger('e107projects_webhook_create', $this->payload);
				break;

			// Any time a Branch or Tag is deleted.
			case 'delete':
				$event->trigger('e107projects_webhook_delete', $this->payload);
				break;

			// Any time a Repository has a new deployment created from the API.
			case 'deployment':
				$event->trigger('e107projects_webhook_deployment', $this->payload);
				break;

			// Any time a deployment for a Repository has a status update from
			// the API.
			case 'deployment_status':
				$event->trigger('e107projects_webhook_deployment_status', $this->payload);
				break;

			// Any time a Repository is forked.
			case 'fork':
				$event->trigger('e107projects_webhook_fork', $this->payload);
				break;

			// Any time a Wiki page is updated.
			case 'gollum':
				$event->trigger('e107projects_webhook_gollum', $this->payload);
				break;

			// Any time a comment on an issue is created, edited, or deleted.
			// @see https://developer.github.com/v3/issues/comments/
			case 'issue_comment':
				$event->trigger('e107projects_webhook_issue_comment', $this->payload);
				break;

			// Any time an Issue is assigned, unassigned, labeled, unlabeled,
			// opened, edited, milestoned, demilestoned, closed, or reopened.
			case 'issues':
				$event->trigger('e107projects_webhook_issues', $this->payload);
				break;

			// Any time a Label is created, edited, or deleted.
			case 'label':
				$event->trigger('e107projects_webhook_label', $this->payload);
				break;

			// Any time a User is added as a collaborator to a Repository.
			case 'member':
				$event->trigger('e107projects_webhook_member', $this->payload);
				break;

			// Any time a User is added or removed from a team. Organization
			// hooks only.
			case 'membership':
				$event->trigger('e107projects_webhook_membership', $this->payload);
				break;

			// Any time a Milestone is created, closed, opened, edited, or
			// deleted.
			case 'milestone':
				$event->trigger('e107projects_webhook_milestone', $this->payload);
				break;

			// Any time a Pages site is built or results in a failed build.
			case 'page_build':
				$event->trigger('e107projects_webhook_page_build', $this->payload);
				break;

			// Any time a Repository changes from private to public.
			case 'public':
				$event->trigger('e107projects_webhook_public', $this->payload);
				break;

			// Any time a comment on a Pull Request's unified diff is created,
			// edited, or deleted (in the Files Changed tab).
			// @see https://developer.github.com/v3/pulls/comments/
			case 'pull_request_review_comment':
				$event->trigger('e107projects_webhook_pull_request_review_comment', $this->payload);
				break;

			// Any time a Pull Request Review is submitted.
			case 'pull_request_review':
				$event->trigger('e107projects_webhook_pull_request_review', $this->payload);
				break;

			// Any time a Pull Request is assigned, unassigned, labeled,
			// unlabeled, opened, edited, closed, reopened, or synchronized
			// (updated due to a new push in the branch that the pull request is
			// tracking).
			case 'pull_request':
				$event->trigger('e107projects_webhook_pull_request', $this->payload);
				break;

			// Any time a Repository is created, deleted, made public, or made
			// private.
			case 'repository':
				$event->trigger('e107projects_webhook_repository', $this->payload);
				break;

			// Any time a Release is published in a Repository.
			case 'release':
				$event->trigger('e107projects_webhook_release', $this->payload);
				break;

			// Any time a Repository has a status update from the API.
			case 'status':
				$event->trigger('e107projects_webhook_status', $this->payload);
				break;

			// Any time a team is added or modified on a Repository.
			case 'team_add':
				$event->trigger('e107projects_webhook_team_add', $this->payload);
				break;

			// Any time a User stars a Repository.
			case 'watch':
				$event->trigger('e107projects_webhook_watch', $this->payload);
				break;

			default:
				header('HTTP/1.0 404 Not Found');
				break;
		}

		exit;
	}

	/**
	 * Validate request.
	 *
	 * @return bool
	 */
	public function isValid()
	{
		if(!varset($_SERVER['HTTP_X_GITHUB_EVENT'], false))
		{
			// HTTP header 'X-GitHub-Event' is missing.
			$this->debugMsg("HTTP header 'X-GitHub-Event' is missing.");
			return false;
		}

		if(!varset($this->plugPrefs['github_secret'], false))
		{
			// Missing secret key.
			$this->debugMsg("Missing secret key.");
			return false;
		}

		if(!varset($_SERVER['HTTP_X_HUB_SIGNATURE'], false))
		{
			// HTTP header 'X-Hub-Signature' is missing.
			$this->debugMsg("HTTP header 'X-Hub-Signature' is missing.");
			return false;
		}
		elseif(!extension_loaded('hash'))
		{
			// Missing 'hash' extension to check the secret code validity.
			$this->debugMsg("Missing 'hash' extension to check the secret code validity.");
			return false;
		}

		list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');

		if(!in_array($algo, hash_algos(), true))
		{
			// Hash algorithm '$algo' is not supported.
			$this->debugMsg("Hash algorithm '" . $algo . "' is not supported.");
			return false;
		}

		$rawPost = file_get_contents('php://input');

		if($hash !== hash_hmac($algo, $rawPost, $this->plugPrefs['github_secret']))
		{
			// Hook secret does not match.
			$this->debugMsg("Hook secret does not match.");
			return false;
		}

		$contentType = varset($_SERVER['HTTP_CONTENT_TYPE']);
		if(empty($contentType))
		{
			$contentType = varset($_SERVER['CONTENT_TYPE']);
		}

		switch($contentType)
		{
			case 'application/json':
				$json = file_get_contents('php://input');
				break;

			case 'application/x-www-form-urlencoded':
				$json = $_POST['payload'];
				break;

			default:
				$this->debugMsg("Content type '" . $contentType . "' is not supported.");
				return false;
				break;
		}

		// Payload structure depends on triggered event
		// https://developer.github.com/v3/activity/events/types/
		$this->payload = json_decode($json, true);

		if(!is_array($this->payload))
		{
			$this->debugMsg("Invalid payload.");
			return false;
		}

		return true;
	}

	public function debugMsg($data)
	{
		if(E107PROJECTS_CALLBACK_DEBUG)
		{
			$log = e107::getLog();
			$log->add('WEBHOOK', (array) $data, E_LOG_INFORMATIVE, 'GITHUB');
		}
	}

}


new e107ProjectsCallback();