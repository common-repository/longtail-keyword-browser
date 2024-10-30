<?php
/*
 Plugin Name: Longtail Keyword Browser
 Plugin URI: http://www.SummitMediaConcepts.com/
 Description: Enter a seed keyword and this tool will let you drill down a tree of longtail keyword suggestions
 Author: Gary Cornelisse
 Author URI: http://www.SummitMediaConcepts.com/
 Tags: longtail, long tail, suggestions, keywords, wonder wheel
 Version: 1.0
*/

include_once(dirname(__FILE__).'/app/Longtail_Keyword_Browser.php');

if (class_exists('Longtail_Keyword_Browser'))
{
	$longtail_browser = new Longtail_Keyword_Browser();
}