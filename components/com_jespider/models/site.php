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
class JESpiderModelSite extends JModelLegacy
{
	protected static $cache = array();
	
	protected $params;
	
	protected $cHandle;
	
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
	public function __construct($siteId)
	{
		$table = $this->getTable('', 'JESpiderTable');
		$table->load($siteId);
		
		$this->params = new JRegistry($table->params);
		$this->params->set('site_id', $table->id);
		
		$this->cHandle = curl_init();
		
		$cookiePath = JPATH_COMPONENT_SITE.'/cookies/';
		if (!JFolder::exists($cookiePath))
		{
			JFolder::create($cookiePath);
		}
		$cookieJar = JPATH_COMPONENT_SITE.'/cookies/'.$siteId.'.txt';
		
		curl_setopt($this->cHandle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->cHandle, CURLOPT_COOKIEJAR, $cookieJar);
		curl_setopt($this->cHandle, CURLOPT_COOKIEFILE, $cookieJar);
		curl_setopt($this->cHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->cHandle, CURLOPT_FOLLOWLOCATION, true);
		
// 		curl_setopt($this->curlHandle, CURLOPT_PROXYPORT, 80);
// 		curl_setopt($this->curlHandle, CURLOPT_PROXYTYPE, 'HTTP');
// 		curl_setopt($this->curlHandle, CURLOPT_PROXY, '222.255.237.138');
// 		curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
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
	public function getContent($url)
	{
		curl_setopt($this->cHandle, CURLOPT_POSTFIELDS, array());
		curl_setopt($this->cHandle, CURLOPT_URL, $url);
		$content = curl_exec($this->cHandle);
		$content = $this->qualifyUrl($content, $url);
		
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
	protected function qualifyUrl($content, $url)
	{
		if (preg_match('#<base[^>]* href="([^"]*)"#is', $content, $match))
		{
			$baseUrl = $match[1];
		}
		else
		{
			$baseUrl = $url;
		}
		
		$component = parse_url($baseUrl);
		if (empty($component['scheme']))
		{	
			$component['scheme'] = 'http';
		}
		
		$this->baseUrl	= $component['scheme'].'://'.$component['host'].$component['path'];
		$this->host		= $component['scheme'].'://'.$component['host'];
		
		$content = preg_replace_callback('#(<\w+[^>]* (?:href|src)\s*=\s*)(["\'])(.*?)\2#is', array($this, 'relToAbs'), $content);
		$content = preg_replace("#(?!\r)\n#is", "\r\n", $content);
		
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
	protected function relToAbs($matches)
	{
		if (substr($matches[3], 0, 1) == '/')
		{
			$matches[3] = $this->host.$matches[3];
		}
		else if (!preg_match('#(http://|www\.|https://|ftp://|\w+://)#is', $matches[3]))
		{
			$matches[3] = $this->baseUrl.$matches[3];
		}
		
		return $matches[1].$matches[2].$matches[3].$matches[2];
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
	public static function getSite($siteId)
	{
		if (isset(self::$cache[$siteId]))
		{
			return self::$cache[$siteId];
		}
		
		$site = new JESpiderModelSites($siteId);
		self::$cache[$siteId] = $site;
		
		return self::$cache[$siteId];
	}
}