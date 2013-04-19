<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Kohana_Mailings {

	/**
	 * Default list name
	 *
	 * @var string
	 */
	protected $_default_list;

	/**
	 * Current instance name
	 *
	 * @var string
	 */
	protected $_instance_name;

	/**
	 * Creates a Mailings class instance
	 *
	 * @param   string   $instance
	 * @return  Mailings
	 */
	public static function instance($instance = 'default')
	{
		$config = Kohana::$config
			->load('mailings.'.$instance);

		// Set class name
		$provider = 'Mailings_Provider_'.$config['provider'];

		return new $provider($instance, $config);
	}

	/**
	 * Inserts inline styles in the html
	 *
	 * @param  string $html
	 * @param  string $css
	 * @return string
	 */
	public static function inline_style($html, $css)
	{
		if ( ! class_exists('CSSQuery', FALSE))
		{
			require Kohana::find_file('vendor', 'inline-style/CSSQuery');
		}

		if ( !class_exists('InlineStyle', FALSE))
		{
			require Kohana::find_file('vendor', 'inline-style/InlineStyle');
		}

		$remove_doctype = ! stristr($html, 'DOCTYPE');

		$html = iconv(mb_detect_encoding($html), 'UTF-8//TRANSLIT', $html);

		$htmldoc = new InlineStyle($html);
		$htmldoc->applyStylesheet($css);

		$html = $htmldoc->getHTML();

		// Remove DOCTYPE, if it was not in the HTML source
		if ($remove_doctype)
		{
			$html = str_replace(array('<html>', '</html>', '<body>', '</body>'), '', $html);
			$html = preg_replace('/^<!DOCTYPE.+?>/', '', $html);
		}

		// Then remove unused cass parameters
		$html = preg_replace('/ class=".*?"/', '', $html);
		$html = preg_replace('/class=".*?"/', '', $html);

		return InlineCSS::parse($html, $css);
	}

} // End Kohana_Mailings