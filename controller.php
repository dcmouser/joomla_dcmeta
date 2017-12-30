<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_dcadmin
 */

// No direct access to this file
defined('_JEXEC') or die;



class DcMetaController extends JControllerLegacy
{

	// fall back on just displaying this view
	protected $default_view = 'default';

	public function display($cachable = false, $urlparams = array()) {


		// Access check: is this user allowed to access the backend of this component?
		if (!JFactory::getUser()->authorise('core.manage', 'com_dcmeta')) {
			throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
		}


		if (!$this->setupcontroller())
			return false;

		if ($this->is_validform_available()) {
			echo '<div class="dcmetainfo"/>';
			$rethtml  = $this->processFormSubmission();
			echo $rethtml;
			echo '</div>';
		}


		parent::display($cachable, $urlparams);
	}







	//---------------------------------------------------------------------------
	protected function setupcontroller() {
		$this->setupcontroller_addToolBar();
		if (!$this->setupcontroller_displayErrors())
			return false;
		return true;
	}


	protected function setupcontroller_displayErrors() {
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		return true;
	}
	

	protected function setupcontroller_addToolBar() {
		$title = 'DC Meta Info';
		JToolBarHelper::title($title, 'DcMeta');
		JToolBarHelper::preferences('com_dcmeta');
		JToolbarHelper::help( 'COM_DCMETA_HELP_VIEW_TYPE1', true );
		return true;
	}

	//---------------------------------------------------------------------------



	//---------------------------------------------------------------------------
	protected function is_validform_available() {
		if (!JSession::checkToken())
			return false;
		return true;
	}
	//---------------------------------------------------------------------------



//---------------------------------------------------------------------------
	protected function processFormSubmission() {
		
		require_once(__DIR__ . '/helpers/dcmetaviewer.php');
		$this->dcmetaviewer = new DcMetaViewer();

		$rethtml = '';		
		$rethtml .= $this->dcmetaviewer->processFormSubmission($_POST);
		
		return $rethtml;
	}
//---------------------------------------------------------------------------



}
