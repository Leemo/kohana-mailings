<?php defined('SYSPATH') OR die('No direct script access.');

return array
(
	'default' => array
	(
		/**
		 * Provider name
		 */
		'provider' => 'Unisender',

		/**
		 * Default list name
		 */
		'default_list' => 'Default',

		/**
		 * Instance parameters
		 */
		'parameters' => array
		(
			/**
			* API response language
			*
			* @see http://www.unisender.com/ru/help/api/
			*/
			'lang' => 'en',

			/**
			* API key
			*
			* @see http://www.unisender.com/ru/help/api/
			*/
			'api_key' => '',

			/**
			 * Track mailing read
			 */
			'track_read' => FALSE,

			/**
			 * Track mailing links
			 */
			'track_links' => FALSE
		)
	)
);
