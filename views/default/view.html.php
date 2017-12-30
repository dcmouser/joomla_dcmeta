<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_dcadmin
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die;

class DcMetaViewDefault extends JViewLegacy
{
	function display($tpl = null)
	{
		// add css
		$document = JFactory::getDocument();
		$csspath = JURI::root(true).'/administrator/components/com_dcmeta/assets';
		$document->addStyleSheet($csspath . '/dcmeta.css');

		JHtml::_('jquery.framework', true, true);
		//JHtml::_('bootstrap.framework');
		$document->addScript($csspath . '/dcmeta.js');

		// create log viewer helper class
		require_once(__DIR__ . '/../../helpers/dcmetaviewer.php');
		$this->dcmetaviewer = new DcMetaViewer();
		// init with options
		$this->dcmetaviewer->set_baseUrl('index.php?option=com_dcmeta');
		// params
		jimport('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_dcmeta');
		$this->dcmetaviewer->setParams($params);
		
		// load article data
		$this->dcmetaviewer->loadArticleData();
		$this->dcmetaviewer->loadMenuData();
		
		// Display the template
		parent::display($tpl);
	}
}
