<?php
/**
 * Short description (optional unless the file contains more than two classes or functions), followed by a blank line)
 * Long description (optional, followed by a blank line).
 * @category (optional and rarely used)
 * @package (generally optional but required when files contain only procedural code)
 * @subpackage (optional)
 * @author Richard Stallman <richard@joomexts.com>
 * @copyright Copyright � 2012 JoomExts Inc. All rights re-served. 
 * @license GNU General Public License version 2 or later
 * @deprecated (optional)
 * @link (optional)
 * @see (optional)
 * @since (generally optional but required when files contain only procedural code)
 */

defined('_JEXEC') or die;

/**
 * Short description (required, unless the file contains more than two classes or functions), followed by a blank line).
 * Long description (optional, followed by a blank line).
 * @category (optional and rarely used)
 * @package (required)
 * @subpackage (optional)
 * @author Richard Stallman <richard@joomexts.com>
 * @copyright (optional unless different from the file Docblock)
 * @license (optional unless different from the file Docblock)
 * @deprecated (optional)
 * @link (optional)
 * @see (optional)
 * @since (required, being the version of the software the class was introduced)
 */
class JESpiderControllerCronjob extends JControllerLegacy
{
	/**
	 * Short description (required, followed by a blank line)
	 * Long description (optional, followed by a blank line)
	 * @package
	 * @subpackage
	 *
	 * @param (required if there are method or function arguments, the last @param tag is followed by a blank line)
	 *
	 * @return (required, followed by a blank line)
	 *
	 * @since
	 */
	public function crawl()
	{
		JLoader::import('models.site', JPATH_COMPONENT);

		$limit		= $this->input->getInt('limit', 0);
		$force		= $this->input->getBool('force', false);
		$crawled	= 0;

		/* @var $queue JESpiderModelQueue */
		$queue = $this->getModel('queue');
		$queue->initialize($force);

		while (($limit == 0 || $crawled < $limit ) && $queue->getSize() > 0)
		{
			$crawler = $queue->pop();
			if ($crawler)
			{
				if ($crawler->crawl())
				{
					$crawled++;
				}
			}
		}
	}
	
	/**
	 * Short description (required, followed by a blank line)
	 * Long description (optional, followed by a blank line)
	 * @package
	 * @subpackage
	 * 
	 * @param (required if there are method or function arguments, the last @param tag is followed by a blank line)
	 * 
	 * @return (required, followed by a blank line)
	 * 
	 * @since
	 */
	public function generate()
	{
		$params = new JRegistry();
		
		$params->set('regex.pages', '<a[^>]*? href="(.*?)"');
		$params->set('regex.items', '<div class="listing-summary">\s*<h3><a href="(?P<link>.*?)"[^>]>.*?</a> </h3>(?:<a href="\1"><img[^>]* src="(?P<avatar>[^"]*)")?');
		$params->set('regex.data', '<span class="breadcrumbs pathway">(?P<breadcrumbs>.*?)</span>.*?<h2>\s*<span property="v:itemreviewed">\s*<a[^>]*>(?P<title>.*?)</a>\s*(?:<img[^>]* alt="(?P<feature>Featured)"[^>]*>)?\s*(?:<img[^>]* alt="(?P<popular>Popular)"[^>]*>)?.*?</span>
(?:<img[^>]* alt="Component" title="(?P<component>.*?)"[^>]*>)?(?:<img[^>]* alt="Module" title="(?P<module>.*?)"[^>]*>)?(?:<img[^>]* alt="Plugin" title="(?P<plugin>.*?)"[^>]*>)?(?:<img[^>]* alt="Language" title="(?P<language>.*?)"[^>]*>)?(?:<img[^>]* alt="Extension Specific Addon" title="(?P<specific>.*?)"[^>]*>)?.*?</h2>
.*?<div class="caption">Version</div><div class="data">(?P<version>.*?)(?: <i>\(last update on (?P<last_update>.*?)\)</i>)?.*?<div class="rating"><span property="v:average" style="color:green; font-size: 20px;">(?P<rating>.*?)</span>.*?from <span property="v:count">(?P<rating_user>.*?)</span> users\.</div></div><div class="caption">Compatibility</div><div class="data">(?:<span><img[^>]* alt="(?P<compat_15>Joomla\! 1\.5 Native)"[^>]*></span>)?(?:<span><img[^>]* alt="(?P<compat_25>Joomla\! 2\.5 Series)"[^>]*></span>)?(?:<span><img[^>]* alt="(?P<compat_30>Joomla\! 3\.0 Ready)"[^>]*></span>)?.*?</div><div class="caption ccol2">Votes</div><div class="data dcol2">.*?
.*?<span id="fav-count">(?P<favorite>.*?)</span>.*?<div class="caption_type">(?P<license>.*?)</div>.*?<div class="caption ccol2">Views</div><div class="data dcol2">(?P<view>.*?)</div><div class="caption">Date Added</div><div class="data">(?P<date_added>.*?)</div>.*?
.*?<div class="fields_dev">.*?<a[^>]*>(?P<developer>.*?)</a>.*?<a[^>]* href="(?P<website>[^"]*").*?
.*?<div class="fields_buttons">\s*(?:<a[^>]* href="(?P<download_link>[^"]*)"[^>]*><img[^>]* alt="Download URL"[^>]*></a>)?(?:<a[^>]* href="(?P<demo_link>[^"]*)"[^>]*><img[^>]* alt="Demo URL"[^>]*></a>)?(?:<a[^>]* href="(?P<support_link>[^"]*)"[^>]*><img[^>]* alt="Support Forum URL"[^>]*></a>)?(?:<a[^>]* href="(?P<document_link>[^"]*)"[^>]*><img[^>]* alt="Documentation URL"[^>]*></a>)?.*?
.*?<span class="listing-desc">(?P<description>.*?)</span></div><p class="report">.*?<div class="reviews">(?:\s*<div class="title">Images</div>\s*<div class="gallery">(?P<gallery>.*?)</div>\n)?');
		
		$params->set('regex.clean.pages', '.*?<div id="index">|<div id="sidebar".*|.*?<div id="subcats">|<div class="hometop".*|.*?<div class="pages-links">|<div class="listing-summary">.*');
		
		$params->set('depth', 3);
		$params->set('crawler_type', 'jed');

		echo $params->toString();
	}
}