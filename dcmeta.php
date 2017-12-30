<?php

// No direct access to this file
defined('_JEXEC') or die;

// Get an instance of the controller prefixed by DcAdmin
$controller = JControllerLegacy::getInstance('DcMeta');

// Perform the Request task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();

?>
