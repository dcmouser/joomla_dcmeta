<?php

// class to assist in browsing log files from an online php script


class DcMetaViewer {
	public $edit_mode = true;
	//
	public $baseUrl = '';
	public $articles = array();
	public $menus = array();
	public $article_columns = array(
		'title'=>'Title/Edit',
		'path'=>'View Path',
		'metadesc'=>'Meta Description',
		'metakey'=>'Meta Keywords',
		'article_page_title'=>'Browser Page Title',
		'robots'=>'Robots',
		'author'=>'Meta Author',
		'rights'=>'Rights',
		'xreference'=>'Ext. Ref.',
		//'metatitle'=>'Meta Title',	// note this field is only added by osmeta i think, and not in core joomla
		);
	public $menu_columns = array(
		'title'=>'Title/Edit',
		'path'=>'View Path',
		'menutype'=>'Menu Type',
		'menulink'=>'Internal Link',
		'meta_description'=>'Meta Description',
		'meta_keywords'=>'Meta Keywords',
		'page_title'=>'Browser Page Title',
		'robots'=>'Robots',
		'secure'=>'Secure',
		'notes'=>'Notes',
		);



public function set_baseUrl($baseUrl) {
	$this->baseUrl = $baseUrl;
	}
public function getBaseUrl() {
	return $this->baseUrl;
	}


public function setParams($params) {
	// set with joomla params
	$this->hidemenus = intval($params->get('hide_menus',1));
}


public function get_articles() {
	return $this->articles;
}

public function get_menus() {
	return $this->menus;
}

public function get_hidemenus() {
	return $this->hidemenus;
}

public function get_article_columns() {
	return $this->article_columns;
}

public function get_menu_columns() {
	return $this->menu_columns;
}

public function get_edit_mode() {
	return $this->edit_mode;
}





public function loadArticleData() {
	$db = JFactory::getDBO();

	if (version_compare(JVERSION, '3.2.', '<')) $tags = false;
	else $tags = true;

	// AND wheres
	$where = array();
	$where[] = "a.state = '1'";
//	$where[] = "a.metadesc = ''";
//	$where[] = "a.metakey = ''";

	$db->setQuery("SELECT a.id, a.title, a.metadesc, a.metakey, a.metadata, a.attribs, a.created, a.state ".($tags ? ", tm.content_item_id" : "''")." AS tags FROM #__content AS a".($tags ? " LEFT JOIN #__contentitem_tag_map AS tm ON tm.content_item_id = a.id" : "")." WHERE a.state = '1'" . " AND (".implode(' AND ', $where).") ORDER BY a.title ASC");

	$this->articles = $db->loadObjectList();

	// build links to articles
	$this->menuitems = $this->getRawMenuItems();
	$path = '';
	foreach ($this->articles as &$article) {
		$article->url_view = $this->calcMenuRouteUrl($article->id, $path);
		$article->path = $path;
		$decodedmetadata = class_exists('JParameter') ? new JParameter($article->metadata) : new JRegistry($article->metadata);
		$article->robots = $decodedmetadata->get('robots', '');
		$article->author = $decodedmetadata->get('author', '');
		$article->rights = $decodedmetadata->get('rights', '');
		$article->xreference = $decodedmetadata->get('xreference', '');
		$article->metatitle = $decodedmetadata->get('metatitle', '');
		$decodedattribs = class_exists('JParameter') ? new JParameter($article->attribs) : new JRegistry($article->attribs);
		$article->article_page_title = $decodedattribs->get('article_page_title', '');
	}
}





public function loadMenuData() {
	$db = JFactory::getDBO();

	if (version_compare(JVERSION, '3.2.', '<')) $tags = false;
	else $tags = true;
	
	$tags = false;

	// AND wheres
	$where = array();
	$where[] = 'm.menutype != "main"';
	$where[] = 'm.menutype != ""';
	// block menu links to articles since these should be blank?
	// $where[] = 'm.link NOT LIKE "%com_content&view=article%"';

	$db->setQuery("SELECT m.id, m.title, m.params, m.menutype, m.type, m.path, m.link FROM #__menu AS m WHERE m.published = '1'"." AND (".implode(' AND ', $where).") ORDER BY m.menutype ASC, m.type ASC, m.title ASC");

	$this->menus = $db->loadObjectList();

	// unpack meta data
	foreach ($this->menus as &$menu) {
		$decodedparams = class_exists('JParameter') ? new JParameter($menu->params) : new JRegistry($menu->params);
		$menu->meta_description = $decodedparams->get('menu-meta_description', '');
		$menu->meta_keywords = $decodedparams->get('menu-meta_keywords', '');
		$menu->page_title = $decodedparams->get('page_title', '');
		$menu->robots = $decodedparams->get('robots', '');
		$menu->secure = $decodedparams->get('secure', '');
	}
}



	public function calcMenuRouteUrl($articleid, &$path) {
		$menulink = $this->getBestMenuRouteToArticle($articleid);
		if (!empty($menulink)) {
			$path = $menulink;
			return JURI::root() . $menulink;
		}
		
		// couldnt find menu link, try to use default uril
		require_once JPATH_SITE . '/components/com_content/helpers/route.php';
		$path = ContentHelperRoute::getArticleRoute($articleid);
	   $baseurl = JURI::base();
	   $url2 = str_replace('/administrator', '', $baseurl);
	   $url_view = $url2 . $path;
		return $url_view;
	}

	public function getBestMenuRouteToArticle($articleid) {
		// now walk through and find all refering to OUR article
		foreach ($this->menuitems as $menuitem) {
			if ($menuitem->component!='com_content') {
				continue;
				}
			if ($menuitem->query['view']!='article') {
				continue;
				}
			if ($menuitem->query['id']!=$articleid) {
				continue;
				}
			// found it!
			return $menuitem->route;
			}

		return '';
	}


public function getRawMenuItems() {
	// get all menu items
	$menu = JMenu::getInstance('site');
	$attributes=array();
	$values=array();
	$menuitems = $menu->getItems($attributes, $values, false);
	return $menuitems;
}
	









public function processFormSubmission($post) {
	$rethtml = '<p>Processing form submission..</p>';

	//$rethtml .= '<p>RAW POST DATA:</p>';
	//$rethtml .= '<pre>' . htmlentities(print_r($_POST,true)) . '</pre>';

	// articles
	$this->loadArticleData();
	$articles = $this->get_articles();
	// walk articles and find changes
	$changes = 0;
	foreach ($articles as $article) {
		$changed = false;
		$attribchanged = false;
		$varprefix = 'a' . $article->id;
		//
		// we only handle these 2 variables currently (not the json encoded stuff)
		$varname = $varprefix . '_metakey';
		if (isset($post[$varname]) && $post[$varname]!=$article->metakey) {
			$article->metakey = $post[$varname];
			$changed = true;
		}
		//
		$varname = $varprefix . '_metadesc';
		if (isset($post[$varname]) && $post[$varname]!=$article->metadesc) {
			$article->metadesc = $post[$varname];
			$changed = true;
		}
		//
		$varname = $varprefix . '_article_page_title';
		if (isset($post[$varname]) && $post[$varname]!=$article->article_page_title) {
			$article->article_page_title = $post[$varname];
			$changed = true;
			$attribchanged = true;
		}
		//
		if ($changed)	{
				// only we want to save changes to article now
				$object = new stdClass();
				$object->id = $article->id;
				$object->metakey = $article->metakey;
				$object->metadesc = $article->metadesc;
				if ($attribchanged) {
					// more tricky attrib change
					// this is trickier, since its a json encoded parameter thing
					$decodedattribs = class_exists('JParameter') ? new JParameter($article->attribs) : new JRegistry($article->attribs);
					$decodedattribs->set('article_page_title',$article->article_page_title);
					//
					$object->attribs = $decodedattribs->toString();
				}
				//
				$result = JFactory::getDbo()->updateObject('#__content', $object, 'id');
				if ($result == 1) {
					$resultstr = 'Success';
				} else {
					$resultstr = '<strong>FAILED (' . htmlentities(print_r($result,true)) . ')</strong>';
				}
				$rethtml .= '<p>Saved changes to article #' . $article->id . ': ' . $resultstr . '.</p>';
				$changes+=1;
				//$rethtml .= 'stopping early for safety during testing.';
				//break;
			}
	}
	$rethtml .= '<p>Articles updated: <strong>' . $changes . '</strong>.</p>';



	// articles
	$this->loadMenuData();
	$menus = $this->get_menus();
	// walk articles and find changes
	$changes = 0;
	foreach ($menus as $menu) {
		$changed = false;
		$varprefix = 'm' . $menu->id;
		//
		// we only handle these 2 variables currently (not the json encoded stuff)
		$varname = $varprefix . '_meta_description';
		if (isset($post[$varname]) && $post[$varname]!=$menu->meta_description) {
			$menu->meta_description = $post[$varname];
			$changed = true;
		}
		//
		$varname = $varprefix . '_meta_keywords';
		if (isset($post[$varname]) && $post[$varname]!=$menu->meta_keywords) {
			$menu->meta_keywords = $post[$varname];
			$changed = true;
		}
		//
		$varname = $varprefix . '_page_title';
		if (isset($post[$varname]) && $post[$varname]!=$menu->page_title) {
			$menu->page_title = $post[$varname];
			$changed = true;
		}
		//
		if ($changed)	{
				// only we want to save changes to menu now
				$object = new stdClass();
				$object->id = $menu->id;

				// this is trickier, since its a json encoded parameter thing
				$decodedparams = class_exists('JParameter') ? new JParameter($menu->params) : new JRegistry($menu->params);
				//
				$decodedparams->set('menu-meta_description',$menu->meta_description);
				$decodedparams->set('menu-meta_keywords',$menu->meta_keywords);
				$decodedparams->set('page_title',$menu->page_title);
				//
				$object->params = $decodedparams->toString();
		
				$result = JFactory::getDbo()->updateObject('#__menu', $object, 'id');
				$rethtml .= '<p>Saved changes to menu #' . $menu->id . ': ' . htmlentities(print_r($result,true)) . '</p>';
				$changes+=1;
				//$rethtml .= 'stopping early for safety during testing.';
				//break;
			}
	}
	$rethtml .= '<p>Menus updated: <strong>' . $changes . '</strong>.</p>';



	$rethtml .= '<p>DONE.</p>';
	
	return $rethtml;
}









}
