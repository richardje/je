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

class JESpierTableSite extends JTable
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
	function __construct(&$db)
	{
		parent::__construct('#__spd_site', 'id', $db);
	}
}