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
}