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
class JESpiderModelQueue extends JModelLegacy
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
	public function initialize($force = false)
	{
		if ($force || $this->isEmpty())
		{
			$db		= $this->getDbo();
			$query	= $db->getQuery(true);
			
			$db->setQuery('TRUNCATE TABLE #__jes_queues');
			$db->query();
			
			$query->select('sc.*, ss.params site_params')
				->from('#__jes_crawlers sc')
				->innerJoin('#__jes_sites ss ON sc.site_id = ss.id')
				->where('sc.published = 1 AND ss.published = 1')
				->order('ss.ordering DESC, sc.ordering DESC');
			
			$db->setQuery($query);
			$crawlers = $db->loadObjectList();
			
			foreach ($crawlers as $crawler)
			{
				$params = new JRegistry($crawler->site_params);
				$params->loadString($crawler->params);
			
				$params->set('url', $crawler->url);
				$params->set('crawler_id', $crawler->id);
				$params->set('site_id', $crawler->site_id);
			
				$this->add($params);
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
	public function add($params)
	{
		if (!$params->get('crawling_data', false) && $params->get('depth') <= 0)
		{
			return false;
		}
		
		$table	= $this->getTable('', 'JESpiderTable');
		$url	= $params->get('url');
		$md5url	= md5($url);
		
		// Add to queue if not exists
		$table->load(array('md5url' => $md5url));
		if (!$table->id)
		{
			$created				= new JDate();
			$table->url				= $url;
			$table->md5url			= $md5url;
			$table->site_id			= $params->get('site_id');
			$table->crawler_id		= $params->get('crawler_id');
			$table->crawling_data	= $params->get('crawling_data', false);
			$table->status			= 0;
			$table->params			= $params->toString();
			$table->created			= $created->toSql();
			$table->store();
			
			return true;
		}
		else 
		{
			return false;
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
	public function getSize()
	{
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		$query->select('COUNT(*)')
			->from('#__jes_queues') 
			->where('status = 0');
		
		$db->setQuery($query);
		
		return $db->loadResult();
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
	public function isEmpty()
	{
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		$query->select('COUNT(*)')
			->from('#__jes_queues')
			->where('status < 2');
		
		$db->setQuery($query);
		
		return ($db->loadResult() == 0);
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
	public function pop()
	{
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		$query->select('*')
			->from('#__jes_queues')
			->where('status = 0')
			->order('created ASC');
		
		$db->setQuery($query, 0, 1);
		$row = $db->loadObject();
		
		if ($row)
		{
			JLoader::import("models.crawler", JPATH_COMPONENT);
			$this->id		= $row->id;
			$params			= new JRegistry($row->params);
			$crawler		= new JESpiderModelCrawler($params->get('crawler_id'));
			$crawler->url	= $params->get('url');
			$crawler->queue	= $this;
			$crawler->loadParams($row->params);
				
			// Mark processing in queue
			$query = $db->getQuery(true);
			$query->update('#__jes_queues')
				->set('status = 1')
				->where('id = '.$row->id);
			$db->setQuery($query);
			$db->query();
			
			return $crawler;
		}
		else 
		{
			return null;
		}
	}
}