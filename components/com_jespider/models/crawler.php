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
	protected $url;
	
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
		$content = $this->getContent();
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
				
			return true;
		}
		else
		{
			return false;
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
	public function parsePages($content)
	{
		$content = $this->clean($content, 'items');
		$pattern = $this->params->get('regex.items');

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
			return true;
		}
		else
		{
			return false;
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
				
			return true;
		}
		else
		{
			return false;
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
	protected function clean($content, $key)
	{
		$cleanPattern = $this->params->get("regex.clean.{$key}", '');
		$cleanPattern = str_replace('|', '<jespider-separate>', $cleanPattern);
		$cleanPattern = str_replace('\<jespider-separate>', '\|', $cleanPattern);
		
		if (!empty($cleanPattern))
		{
			$cleanPattern = explode('<jespider-separate>', $cleanPattern);
			foreach ($cleanPattern as $pattern)
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