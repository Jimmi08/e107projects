<?php

/**
 * @file
 * Shortcodes for "e107projects" plugin.
 */

if(!defined('e107_INIT'))
{
	exit;
}

// [PLUGINS]/e107projects/languages/[LANGUAGE]/[LANGUAGE]_front.php
e107::lan('e107projects', false, true);


/**
 * Class e107projects_shortcodes.
 */
class e107projects_shortcodes extends e_shortcode
{

	/**
	 * Constructor.
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Contents for first column in summary menu.
	 */
	public function sc_summary_menu_col_1()
	{
		$count = (int) $this->var['col_1'];
		$formatted = number_format($count);
		return '<strong>' . $formatted . '</strong><br/>' . LAN_E107PROJECTS_FRONT_01;
	}

	/**
	 * Contents for second column in summary menu.
	 */
	public function sc_summary_menu_col_2()
	{
		$count = (int) $this->var['col_2'];
		$formatted = number_format($count);
		return '<strong>' . $formatted . '</strong><br/>' . LAN_E107PROJECTS_FRONT_03;
	}

	/**
	 * Contents for third column in summary menu.
	 */
	public function sc_summary_menu_col_3()
	{
		$count = (int) $this->var['col_3'];
		$formatted = number_format($count);
		return '<strong>' . $formatted . '</strong><br/>' . LAN_E107PROJECTS_FRONT_02;
	}

	/**
	 * Notification - avatar.
	 */
	public function sc_notification_avatar()
	{
		$url = varset($this->var['avatar_url'], '');

		if(!empty($url))
		{
			$width = varset($this->var['avatar_width'], 50);
			$height = varset($this->var['avatar_height'], 50);

			return '<img src="' . $url . '" width="' . $width . '" height="' . $height . '" alt=""/>';
		}
	}

	/**
	 * Notification - message.
	 */
	public function sc_notification_message()
	{
		$message = varset($this->var['message'], '');

		if(!empty($message))
		{
			return '<p>' . $message . '</p>';
		}
	}

	/**
	 * Notification - link.
	 */
	public function sc_notification_link()
	{
		$link = varset($this->var['link'], '');

		if(!empty($link))
		{
			return $link;
		}
	}

	/**
	 * Submit page - empty text.
	 */
	public function sc_submit_project_empty_text()
	{
		return LAN_E107PROJECTS_FRONT_09;
	}

	/**
	 * Submit page - help text.
	 */
	public function sc_submit_project_help_text()
	{
		return LAN_E107PROJECTS_FRONT_12;
	}

	/**
	 * Submit page - project name label.
	 */
	public function sc_submit_project_name_label()
	{
		return LAN_E107PROJECTS_FRONT_06;
	}

	/**
	 * Submit page - project name.
	 */
	public function sc_submit_project_name()
	{
		$repository = $this->var['repository'];
		$submitted = (bool) $this->var['submitted'];
		$status = (int) $this->var['status'];

		if($submitted && $status == 1)
		{
			$url = e107::url('e107projects', 'project', array(
				'user'       => $repository['owner']['login'],
				'repository' => $repository['name'],
			), array('full' => true));

			return '<a href="' . $url . '" target="_self">' . $repository['full_name'] . '</a>';
		}

		return varset($repository['full_name'], '');
	}

	/**
	 * Submit page - project description.
	 */
	public function sc_submit_project_description()
	{
		$repository = $this->var['repository'];
		return varset($repository['description'], '');
	}

	/**
	 * Submit page - project action label.
	 */
	public function sc_submit_project_action_label()
	{
		return LAN_E107PROJECTS_FRONT_07;
	}

	/**
	 * Submit page - project action label.
	 */
	public function sc_submit_project_action()
	{
		$repository = $this->var['repository'];
		$submitted = (bool) $this->var['submitted'];
		$status = (int) $this->var['status'];

		$html = '';

		if(!$submitted)
		{
			$form = e107::getForm();
			$tp = e107::getParser();

			$btnType = 'button';
			$btnName = 'submit';
			$btnVals = LAN_E107PROJECTS_FRONT_08;
			$btnAttr = $form->get_attributes(array(
				'class'          => 'btn btn-primary e-ajax has-spinner project-submission-button',
				'data-event'     => 'click',
				'data-ajax-type' => 'POST',
			), $btnName, $btnVals);

			$html .= $form->open('submit-repository-' . $repository['id']);
			$html .= $form->hidden('repository', $repository['id']);
			$html .= '<button type="' . $btnType . '" name="' . $btnName . '"' . $btnAttr . '>';
			$html .= '<span class="spinner">' . $tp->toGlyph('fa-refresh', array('spin' => 1)) . '</span>';
			$html .= $btnVals;
			$html .= '</button>';

			$html .= $form->close();
			return $html;
		}

		// Pending.
		if($status == 0)
		{
			$html = '<p class="text-success">' . LAN_E107PROJECTS_FRONT_11 . '</p>';
			return $html;
		}

		// Rejected.
		if($status == 2)
		{
			$html = '<p class="text-danger">' . LAN_E107PROJECTS_FRONT_14 . '</p>';
			return $html;
		}

		// Approved.
		$html = '<p class="text-success">' . LAN_E107PROJECTS_FRONT_13 . '</p>';
		return $html;
	}

}