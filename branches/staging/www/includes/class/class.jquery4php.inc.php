<?php

/**
* JQuery4PHP class
*
* Class for developping JQuery scripts
* @package  sefi
*/
class Jquery4php
{
	public static function load()
	{
		require_once(
			DIR_LIB_ABSOLUTE .
			'/jQuery4PHP/'.
			'YepSua/Labs/RIA/jQuery4PHP/YsJQueryAutoloader.php'
		);

		YsJQueryAutoloader::register();		
	}
}