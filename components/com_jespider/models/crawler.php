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
class JESpiderModelCrawler extends JModelLegacy
{
	/**
	 * @var JRegistry
	 */
	protected $params;
	
	/**
	 * @var JESpiderModelSite
	 */
	protected $site;
	
	/**
	 * @var JESpiderModelQueue
	 */
	public $queue;
	
	/**
	 * @var String
	 */
	protected $content;
	
	/**
	 * @var String
	 */
	public $url;
	
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
	public function __construct($crawlerId, $params = null)
	{
		if (!$params)
		{
			$table = $this->getTable('', 'JESpiderTable');
			$table->load($crawlerId);
			
			$this->params	= new JRegistry($table->params);
			$this->site		= JESpiderModelSites::getSite($table->site_id);
			$this->url		= $table->url;
			$this->params->set('site_id', $table->site_id);
			$this->params->set('crawler_id', $table->id);
		}
		else
		{
			$this->params	= $params;
			$this->site		= JESpiderModelSite::getSite($params->get('site_id'));
			$this->url		= $params->get('url');
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
	public function loadParams($strParams)
	{
		$this->params->loadString($strParams);
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
	public function getContent($url = '')
	{
		if (!empty($url))
		{
			$this->url = $url;
		}
		
		if (empty($this->content))
		{
			$this->content = $this->site->getContent($this->url);
		}
		
		return $this->content;
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
	public function crawl()
	{
		$this->url = 'http://extensions.joomla.org/extensions/photos-a-images/galleries/photo-display/21000';
		$this->params->set('crawling_data', true);
		$content = $this->getContent();
		//echo $content; die();
		if (empty($content))
		{
			return false;
		}
		
		if ($this->isCrawlingData())
		{
			$parsed = $this->parseData($content);
		}
		else
		{
			$parsed = ($this->parsePages($content)) || ($this->parsePageLinks($content));
		}
		
		if ($parsed)
		{
			$this->queue->remove();
		}
		die();
		return $parsed;
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
	public function parseData($content)
	{
		$content = $this->clean($content, 'data');
		$pattern = $this->params->get('regex.data');
		//echo $pattern;die();
		//echo $content;die();
		$pattern = '<span class="breadcrumbs pathway">(?P<breadcrumbs>.*?)</span>.*?<h2>\s*<span property="v:itemreviewed">\s*<a[^>]*>(?P<title>.*?)</a>\s*(?:<img[^>]* alt="(?P<feature>Featured)"[^>]*>)?\s*(?:<img[^>]* alt="(?P<popular>Popular)"[^>]*>)?.*?</span>
(?:<img[^>]* alt="Component" title="(?P<component>.*?)"[^>]*>)?(?:<img[^>]* alt="Module" title="(?P<module>.*?)"[^>]*>)?(?:<img[^>]* alt="Plugin" title="(?P<plugin>.*?)"[^>]*>)?(?:<img[^>]* alt="Language" title="(?P<language>.*?)"[^>]*>)?(?:<img[^>]* alt="Extension Specific Addon" title="(?P<specific>.*?)"[^>]*>)?.*?</h2>
.*?<div class="caption">Version</div><div class="data">(?P<version>.*?)(?: <i>\(last update on (?P<last_update>.*?)\)</i>)?.*?<div class="rating"><span property="v:average" style="color:green; font-size: 20px;">(?P<rating>.*?)</span>.*?from <span property="v:count">(?P<rating_user>.*?)</span> users\.</div></div><div class="caption">Compatibility</div><div class="data">(?:<span><img[^>]* alt="(?P<compat_15>Joomla\! 1\.5 Native)"[^>]*></span>)?(?:<span><img[^>]* alt="(?P<compat_25>Joomla\! 2\.5 Series)"[^>]*></span>)?(?:<span><img[^>]* alt="(?P<compat_30>Joomla\! 3\.0 Ready)"[^>]*></span>)?.*?</div><div class="caption ccol2">Votes</div><div class="data dcol2">.*?
.*?<span id="fav-count">(?P<favorite>.*?)</span>.*?<div class="caption_type">(?P<license>.*?)</div>.*?<div class="caption ccol2">Views</div><div class="data dcol2">(?P<view>.*?)</div><div class="caption">Date Added</div><div class="data">(?P<date_added>.*?)</div>.*?
.*?<div class="fields_dev">.*?<a[^>]*>(?P<developer>.*?)</a>.*?<a[^>]* href="(?P<website>[^"]*").*?
.*?<div class="fields_buttons">\s*(?:<a[^>]* href="(?P<download_link>[^"]*)"[^>]*><img[^>]* alt="Download URL"[^>]*></a>)?(?:<a[^>]* href="(?P<demo_link>[^"]*)"[^>]*><img[^>]* alt="Demo URL"[^>]*></a>)?(?:<a[^>]* href="(?P<support_link>[^"]*)"[^>]*><img[^>]* alt="Support Forum URL"[^>]*></a>)?(?:<a[^>]* href="(?P<document_link>[^"]*)"[^>]*><img[^>]* alt="Documentation URL"[^>]*></a>)?';
		
		if (preg_match("#{$pattern}#is", $content, $matches))
		{
			$keys	= array_keys($matches);
			foreach ($keys as $key)
			{
				if (!is_numeric($key))
				{
					$this->params->set("data.{$key}", $matches[$key]);
				}
			}
			$this->params->set('url', $this->url);
				
			JPluginHelper::importPlugin('jspider');
			$dispatcher	= JEventDispatcher::getInstance();
			$dispatcher->trigger('onAfterParseData', array(&$this->params));
				
			//return true;
		}
		else
		{
			//return false;
		}
		print_r($matches);die();
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
	public function parsePages($content)
	{
		$content = $this->clean($content, 'items');
		$pattern = $this->params->get('regex.items');
		//$pattern = '<div class="listing-summary">\s*<h3><a href="(?P<link>.*?)"[^>]>.*?</a> </h3>(?:<a href="\1"><img[^>]* src="(?P<avatar>[^"]*)")?';
		//echo $content;die();

		if (preg_match_all("#{$pattern}#is", $content, $matches))
		{
			$count = count($matches[0]);
			$keys = array_keys($matches);
			for ($i = 0; $i < $count; $i++)
			{
				$params = clone $this->params;
				foreach ($keys as $key)
				{
					if (!is_numeric($key))
					{
						$params->set("data.{$key}", $matches[$key][$i]);
					}
				}

				$url = $params->get('data.link');
				if (!empty($url))
				{
					$params->set('url', $url);
					$params->set('depth', $params->get('depth') - 1);
					$params->set('crawling_data', true);
					$this->queue->add($params);
				}
			}
			//return true;
		}
		else
		{
			//return false;
		}
		//print_r($matches);die();
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
	public function parsePageLinks($content)
	{
		if (preg_match('#<span class="breadcrumbs pathway">(.*?)</span>#is', $content, $matches))
		{
			$breadcrumbs = $matches[1];
			$breadcrumbs = preg_replace('#^\s*<a[^>]*>.*?</a>\s*<img[^>]*>\s*#is', '', $breadcrumbs);
			$breadcrumbs = preg_replace('#\s*<img[^>]*>\s*$#is', '', $breadcrumbs);
			$breadcrumbs = preg_replace('#\s*<img[^>]*>\s*#is', '|', $breadcrumbs);
			$breadcrumbs = strip_tags($breadcrumbs);
			$breadcrumbs = htmlspecialchars_decode($breadcrumbs);
		}
		
		$description = '';
		if (preg_match('#<div id="cat-desc">(.*?)</div>#is', $content, $matches))
		{
			$description = $matches[1];
			$description = strip_tags($description);
			$description = trim($description);
		}
		
		if (!empty($breadcrumbs))
		{
			$this->params->set('data.'.$breadcrumbs, $description);
		}
		
		$content = $this->clean($content, 'pages');
		$pattern = $this->params->get('regex.pages');
		
		if (preg_match_all("#{$pattern}#is", $content, $matches))
		{
			foreach ($matches[1] as $url)
			{
				if (!empty($url))
				{
					$params = clone $this->params;
					$params->set('url', $url);
					$params->set('depth', $params->get('depth') - 1);
					$this->queue->add($params);
				}
			}
				
			//return true;
		}
		else
		{
			//return false;
		}
		print_r($matches);die();
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
	protected function clean($content, $key)
	{
		$patterns = $this->params->get("regex.clean.{$key}", '');
		$patterns = str_replace('|', '<jespider-separate>', $patterns);
		$patterns = str_replace('\<jespider-separate>', '\|', $patterns);
		
		if (!empty($patterns))
		{
			$patterns = explode('<jespider-separate>', $patterns);
			foreach ($patterns as $pattern)
			{
				if (!empty($pattern))
				{
					$content = preg_replace("#{$pattern}#is", '\1', $content);
				}
			}
		}
		
		return $content;
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
	protected function isCrawlingData()
	{
		return $this->params->get('crawling_data', false);
	}
}