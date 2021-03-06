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
		$db = JFactory::getDbo();
		$table = new JTableJed($db);
		$table->load(array('md5url' => md5($params->get('url'))));
		
		$lastUpdate				= new JDate($params->get('data.last_update'));
		$dateAdded				= new JDate($params->get('data.date_added'));
		$today					= new JDate();
		
		$table->url				= $params->get('url');
		$table->md5url			= md5($table->url);
		$table->title			= $params->get('data.title', '');
		$table->alias			= JApplication::stringURLSafe($table->title);
		$table->fulltext		= $params->get('data.description', '');
		$table->introtext		= $this->getIntrotext($table->fulltext);
		$table->catid			= $this->getCategory($params);
		$table->avatar			= $this->getAvatar($params);
		$table->gallery			= $this->getGallery($params);
		
		$table->featured		= ($params->get('data.feature', '')		== '') ? false : true;
		$table->popular			= ($params->get('data.popular', '')		== '') ? false : true;
		$table->component		= ($params->get('data.component', '')	== '') ? false : true;
		$table->module			= ($params->get('data.module', '')		== '') ? false : true;
		$table->plugin			= ($params->get('data.plugin', '')		== '') ? false : true;
		$table->language		= ($params->get('data.language', '')	== '') ? false : true;
		$table->specific		= ($params->get('data.specific', '')	== '') ? false : true;
		$table->compat_15		= ($params->get('data.compat_15', '')	== '') ? false : true;
		$table->compat_25 		= ($params->get('data.compat_25', '')	== '') ? false : true;
		$table->compat_30 		= ($params->get('data.compat_30', '')	== '') ? false : true;
		
		$table->version			= $params->get('data.version', '');
		$table->date_added		= $dateAdded->toSql();
		$table->last_update		= $lastUpdate->toSql();
		$table->rating			= $params->get('data.rating', 0);
		$table->rating_count	= $params->get('data.rating_user', 0);
		$table->rating_sum		= round($table->rating * $table->rating_count);
		$table->favorite		= $params->get('data.favorite', 0);
		$table->license			= $params->get('data.license', '');
		$table->view			= $params->get('data.view', 0);
		
		$table->developer		= $params->get('data.developer', '');
		$table->website			= $this->getRedirectUrl($params->get('data.website'));
		$table->download_url	= $params->get('data.download_link');
		$table->demo_url		= $params->get('data.demo_link');
		$table->support_url		= $params->get('data.support_link');
		$table->document_url	= $params->get('data.document_link');
		
		$time = $today->toUnix() - $dateAdded->toUnix();
		$days = ($time / (60 * 60 * 24)) + 1;
		$table->ordering = floor($table->view / $days);
		
		if (!$table->id)
		{
			$table->state = 1;
		}
		
		//print_r($params);
		//print_r($table);die();
		
		if ($table->store())
		{
			$params->set('success', true);
		}
	}
	
	protected function getIntrotext($fulltext)
	{
		$fulltext = trim(strip_tags($fulltext));
		if (preg_match('#(^.{250,300}?)\s#is', $fulltext, $matches))
		{
			$introtext = $matches[1];
		}
		else
		{
			$introtext = $fulltext;
		}
		
		if (strlen($introtext) < strlen($fulltext))
		{
			$introtext = $introtext . '...';
		}
		
		return $introtext;
	}
	
	protected function getRedirectUrl($url)
	{
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL,            $url);
		curl_setopt($ch, CURLOPT_HEADER,         true);
		curl_setopt($ch, CURLOPT_NOBODY,         true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT,        10);
		
		$r = curl_exec($ch);
		$lasturl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

		return $lasturl;
	}
	
	protected function getAvatar($params)
	{
		if (preg_match('#.*?/listings/m/([^"]+)"#is', $params->get('data.gallery', ''), $matches))
		{
			$avatar = $matches[1];
		}
		else
		{
			$avatar = '';
		}
		
		return $avatar;
	}
	
	protected function getGallery($params)
	{
		if (preg_match_all('#href="[^"]+/listings/m/([^"]+)"#is', $params->get('data.gallery', ''), $matches))
		{
			$gallery = implode('|', $matches[1]);
		}
		else
		{
			$gallery = '';
		}
		
		return $gallery;
	}
	
	protected function getCategory($params)
	{
		$breadcrumbs = $params->get('data.breadcrumbs');
		$breadcrumbs = preg_replace('#^\s*<a[^>]*>.*?</a>\s*<img[^>]*>\s*#is', '', $breadcrumbs);
		$breadcrumbs = preg_replace('#\s*<img[^>]*>\s*$#is', '', $breadcrumbs);
		$breadcrumbs = preg_replace('#\s*<img[^>]*>\s*#is', '|', $breadcrumbs);
		$breadcrumbs = strip_tags($breadcrumbs);
		$breadcrumbs = htmlspecialchars_decode($breadcrumbs);
		$breadcrumbs = explode('|', $breadcrumbs);
		
		$db			= JFactory::getDbo();
		$parentId	= 1;
		$depth		= $params->get('depth');
		
		foreach ($breadcrumbs as $title)
		{
			$depth++;
			$alias = JApplication::stringURLSafe($title);
			$table = new JTableCategory($db);
			$table->load(array('alias' => $alias, 'extension' => 'com_jed'));
			if (empty($table->id))
			{
				$table->title			= $title;
				$table->alias			= $alias;
				$table->description		= $this->params->get("data.{$depth}", '');
				$table->extension		= 'com_jed';
				$table->published		= 1;
				$table->access			= 1;
				$table->created_user_id	= 42;
				$table->language		= '*';
				
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
			else if ($this->params->get("data.{$depth}", '') != '')
			{
				$table->description = $this->params->get("data.{$depth}", '');
				$table->store();
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