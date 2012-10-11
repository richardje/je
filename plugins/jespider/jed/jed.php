<?php
/**
 * @version		$Id: pagebreak.php 21811 2011-07-11 06:50:16Z infograf768 $
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class plgJESpiderJed extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onAfterParseData($params)
	{
		print_r($params);die('test');
		$db = JFactory::getDbo();
		$table = new JTableJed($db);
		$table->load(array('md5url' => md5($params->get('url'))));
		
		$lastUpdate = new JDate($params->get('data.last_update'));
		$dateAdded = new JDate($params->get('data.date_added'));
		
		$table->url = $params->get('url');
		$table->md5url = md5($table->url);
		$table->catid = $this->getCategory($params->get('data.breadcrumbs'));
		$table->title = $params->get('data.title');
		$table->feature = ($params->get('data.feature') == '') ? false : true;
		$table->popular = ($params->get('data.popular') == '') ? false : true;
		$table->component = ($params->get('data.component') == '') ? false : true;
		$table->module = ($params->get('data.module') == '') ? false : true;
		$table->plugin = ($params->get('data.plugin') == '') ? false : true;
		$table->language = ($params->get('data.language') == '') ? false : true;
		$table->specific = ($params->get('data.specific') == '') ? false : true;
		$table->version = $params->get('data.version');
		$table->last_update = $lastUpdate->toSql();
		$table->rating = $params->get('data.rating');
		$table->rating_user = $params->get('data.rating_user');
		$table->compat_15 = ($params->get('data.compat_15') == '') ? false : true;
		$table->compat_25 = ($params->get('data.compat_25') == '') ? false : true;
		$table->favorite = $params->get('data.favorite');
		$table->license = $params->get('data.license');
		$table->view = $params->get('data.view');
		$table->date_added = $dateAdded->toSql();
		
		if ($table->store())
		{
			$params->set('success', true);
		}
	}
	
	protected function getCategory($breadcrumbs)
	{
		$breadcrumbs = preg_replace('#^\s*<a[^>]*>.*?</a>\s*<img[^>]*>\s*#is', '', $breadcrumbs);
		$breadcrumbs = preg_replace('#\s*<img[^>]*>\s*$#is', '', $breadcrumbs);
		$breadcrumbs = preg_replace('#\s*<img[^>]*>\s*#is', '|', $breadcrumbs);
		$breadcrumbs = strip_tags($breadcrumbs);
		$breadcrumbs = htmlspecialchars_decode($breadcrumbs);
		$breadcrumbs = explode('|', $breadcrumbs);
		
		$db = JFactory::getDbo();
		$parentId = 1;
		
		foreach ($breadcrumbs as $title)
		{
			$alias = JApplication::stringURLSafe($title);
			$table = new JTableCategory($db);
			$table->load(array('alias' => $alias, 'extension' => 'com_jed'));
			if (empty($table->id))
			{
				$table->title = $title;
				$table->alias = $alias;
				$table->extension = 'com_jed';
				//$table->parent_id = $parentId;
				$table->published = 1;
				$table->access = 1;
				//$table->level = 1;
				$table->created_user_id = 42;
				$table->language = '*';
				
				$table->setLocation($parentId, 'last-child');
				
				// Store the data.
				if (!$table->store())
				{
					$this->setError($table->getError());
					return false;
				}
				
				// Rebuild the path for the category:
				if (!$table->rebuildPath($table->id))
				{
					$this->setError($table->getError());
					return false;
				}
				
				// Rebuild the paths of the category's children:
				if (!$table->rebuild($table->id, $table->lft, $table->level, $table->path))
				{
					$this->setError($table->getError());
					return false;
				}
			}
			$parentId = $table->id;
		}
		
		return $parentId;
	}
}

class JTableJed extends JTable 
{
	public function __construct($db)
	{
		parent::__construct('#__jed_items', 'id', $db);
	}
}