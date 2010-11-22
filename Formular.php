<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Formular class to generate formulars from DCAs.
 *
 * PHP version 5
 * @copyright  4ward.media 2010
 * @author     Christoph Wiechert <christoph.wiechert@4wardmedia.de>
 * @package	   Library
 * @license    LGPL
 * @version	   0.1
 * @filesource
 * 
 * @TODO add submitOnChange
 */


class Formular extends Controller 
{
	
	/******* Formular confiuration ******/
	 
	/**
	 * String to identify this formular
	 * @var str
	 */
	protected $formId = null;
	
	/**
	 * Template
	 * @var str
	 */
	protected $formTemplate = 'form';
	
	/**
	 * Additional CSS-Classes
	 * @var str
	 */
	protected $class = '';
	
	/**
	 * Action-attrbute for <form> tag
	 * @var str
	 */
	protected $action = null;
	
	/**
	 * Method-attribute for <form> tag
	 * @var str
	 */
	protected $method = 'post';
	
	/**
	 * The widgets get generated in this format
	 * @var str
	 */
	protected $generateFormat = '%parse';
	
	/**
	 * Attributes for parse()-Methods
	 * i.e. "tableless"
	 * @var array
	 */
	protected $attributes = array();
	/******* Formular confiuration END ******/
	
	/**
	 * The DCA-Definition
	 * @var array
	 */
	protected $arrDCA = array();
	
	/**
	 * The initialized widget-objects
	 * @var array
	 */
	protected $arrWidgets = array();
	
	/**
	 * Helper variable to set the initialized-flag
	 * @var boolean
	 */
	protected $initialized = false;	
	
	/**
	 * Does the formular has file-fields?
	 * @var bool
	 */
	protected $hasUpload = false;
	
	/**
	 * The validation-result
	 * @var bool
	 */
	protected $hasErrors = false;

	
	/**
	 * Constructor
	 * @param str $formId
	 * @param array $dca
	 */
	public function __construct($formId)
	{
		parent::__construct();
		$this->import('Input');
		
		$this->formId = $formId;
		$this->action = $this->Environment->request;
		
		// Try to load global formular config
		if(isset($GLOBALS['TL_FORM']) && is_array($GLOBALS['TL_FORM']))
		{
			foreach($GLOBALS['TL_FORM'] as $param => $val) $this->setConfig($param,$val);	
		}
		
	}
	
	/**
	 * Set the DCA definition
	 * @param array $dca
	 */
	public function setDCA($dca)
	{
		$this->arrDCA = $dca;
		$this->initialized = false;
	}
	
	/**
	 * Get the DCA
	 * @return array
	 */
	public function getDCA()
	{
		return $this->arrDCA;
	}
	
	
	/**
	 * Generate the formular and return it 
	 * @param bool $returnArray When true return array(hiddenFields,normalFields)
	 * @return mixed
	 * @TODO closer look on definition-standardisation
	 */
	public function generate($returnArray=false)
	{
		// Init form if its not already done		
		if(!$this->initialized) $this->initialize();
		
		$hasUpload = false;
		$row = 0;
		$max_row = count($this->arrDCA);
		$arrTinyMceConfigs = array();
		$arrDatepicker = array();
		
		// Iterate the DCA
		$hidden = $html = '';
		foreach ($this->arrWidgets as $field => $objWidget)
		{
			$objWidget->rowClass = 'row_'.$row . (($row == 0) ? ' row_first' : (($row == ($max_row - 1)) ? ' row_last' : '')) . ((($row % 2) == 0) ? ' even' : ' odd');
			
			// Increase the row count if its a password field
			if ($this->arrDCA[$field]['inputType'] == 'password')
			{
				++$row;
				++$max_row;
				$objWidget->rowClassConfirm = 'row_'.$row . (($row == ($max_row - 1)) ? ' row_last' : '') . ((($row % 2) == 0) ? ' even' : ' odd');
			}

			// Add classes 
			if (strlen($this->arrDCA[$field]['eval']['class'])){
				$objWidget->rowClass .= ' '.$this->arrDCA[$field]['eval']['class'];
			}
			
			
			/** Some DCA-definition standardisation for specific widgets **/
			
			// Submit buttons needs a slabel
			if ($this->arrDCA[$field]['inputType'] == 'submit')
			{
				$objWidget->label = '';
				if(isset($this->arrDCA[$field]['name'])) $objWidget->name = $this->arrDCA[$field]['name'];
				$objWidget->slabel = $this->arrDCA[$field]['label'];
			}
						
			// Headline, Explanation and HTML needs text and html Attributes
			if ($this->arrDCA[$field]['inputType'] == 'headline')		$objWidget->text = $this->arrDCA[$field]['label'];
			if ($this->arrDCA[$field]['inputType'] == 'explanation')	$objWidget->text = $this->arrDCA[$field]['value'];
			if ($this->arrDCA[$field]['inputType'] == 'html')			$objWidget->html = $this->arrDCA[$field]['value'];
			
			// Cleardefault-extension for textfields and textareas 
			if(in_array('cleardefault', $this->Config->getActiveModules()))
			{
				// only if setting in DCA-eval is not already set  
				if(!isset($this->arrDCA[$field]['eval']['cleardefault']) 
					&& ( $this->arrDCA[$field]['inputType'] == 'text' || $this->arrDCA[$field]['inputType'] == 'textarea'))
				{
					$objWidget->cleardefault = true;
				}
			}
			
			// TinyMCE-Config
			if(isset($this->arrDCA[$field]['eval']['rte']) && strlen($this->arrDCA[$field]['eval']['rte']))
				$arrTinyMceConfigs[$this->arrDCA[$field]['eval']['rte']][] = $field;
			
			// Add datepicker 
			if (isset($this->arrDCA[$field]['eval']['datepicker']) && strlen($this->arrDCA[$field]['eval']['datepicker']))
				$arrDatepicker[] = sprintf($this->arrDCA[$field]['eval']['datepicker'], 'ctrl_' . $field);
			
			// HOOK: run load form field callback
			if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && is_array($GLOBALS['TL_HOOKS']['loadFormField']))
			{
				foreach ($GLOBALS['TL_HOOKS']['loadFormField'] as $callback)
				{
					$this->import($callback[0]);
					$objWidget = $this->$callback[0]->$callback[1]($objWidget, $this->formId, $this->arrData);
				}
			}

			// notice if this formular has an upload field
			if ($objWidget instanceof uploadable)	$this->hasUpload = true;

			// Adjust rowcount
			if ($objWidget instanceof FormHidden)
				$max_row--;
			else
				$row++;
			
			// Generate/parse the field
			// @TODO perhaps theres a better way to do the replacement according $this->generateFormat
			$tmp = $this->generateFormat;
			if(strpos($this->generateFormat,'%parse') !== false)
				$tmp = str_replace('%parse',$objWidget->parse($this->attributes),$tmp);
			if(strpos($this->generateFormat,'%label') !== false)
				$tmp = str_replace('%label',$objWidget->generateLabel(),$tmp);
			if(strpos($this->generateFormat,'%field') !== false)
				$tmp = str_replace('%field',$objWidget->generate(),$tmp);
			if(strpos($this->generateFormat,'%error') !== false)
				$tmp = str_replace('%error',$objWidget->getErrorAsHTML(),$tmp);
			if(strpos($this->generateFormat,'%errorPlain') !== false)
				$tmp = str_replace('%errorPlain',$objWidget->getErrorAsString(),$tmp);

			if($objWidget instanceof FormHidden)
				$hidden .= $tmp;
			else
				$html .= $tmp;
				
			continue;
			
		}

		// Insert-TinyMCE config if theres one
		// @TODO: find a better way to do this
		if(count($arrTinyMceConfigs)>0)
		{
            $this->base = $this->Environment->base;
            $this->brNewLine = $GLOBALS['TL_CONFIG']['pNewLine'] ? false : true;
            $this->uploadPath = $GLOBALS['TL_CONFIG']['uploadPath'];
            // Fallback to English if the user language is not supported
            $this->language = (file_exists(TL_ROOT . '/plugins/tinyMCE/langs/' . $GLOBALS['TL_LANGUAGE'] . '.js')) ? $GLOBALS['TL_LANGUAGE'] : 'en';
			
			foreach($arrTinyMceConfigs as $cfg => $rteFields)
			{
				$this->rteFields = implode(',',$rteFields);
				ob_start();
				include(TL_ROOT.'/system/config/'.$cfg.'.php');
				$html .= ob_get_clean();		
			}
		}
		
		// Insert Datepicker javascript
		if(count($arrDatepicker)>0)
		{
			$GLOBALS['TL_JAVASCRIPT']['calendar'] = 'plugins/calendar/js/calendar.js.gz';
			$GLOBALS['TL_CSS']['calendar'] = 'plugins/calendar/css/calendar.css';
			$html .= '
<script type="text/javascript">
<!--//--><![CDATA[//><!--
window.addEvent(\'domready\', function() { ' . implode("\n",$arrDatepicker) . ' });
//--><!]]>
</script>';
		}
				
		
		if ($returnArray)
			return array($hidden,$html);
		else 
			return $hidden.$html;
	}
	
	/**
	 * Validate the formular and return true if the there are no errors
	 * @return bool
	 */
	public function validate()
	{
		// Init form if its not already done		
		if(!$this->initialized) $this->initialize();
		
		$this->hasErrors = false;
		foreach($this->arrWidgets as $objWidget)
		{
			// Validate input
			$objWidget->validate();
	
			// HOOK: validate form field callback
			if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && is_array($GLOBALS['TL_HOOKS']['validateFormField']))
			{
				foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback)
				{
					$this->import($callback[0]);
					$objWidget = $this->$callback[0]->$callback[1]($objWidget, $formId, $this->arrData);
				}
			}
	
			if ($objWidget->hasErrors()){
				$this->hasErrors = true;
			}
		}

		return !$this->hasErrors;
	}
	
	/**
	 * Returns true if there are any validation errors
	 * @return bool
	 */
	public function hasErrors()
	{
		return $this->hasErrors;
	}
	
	/**
	 * Returns true if the formular has been submitted
	 * Consider that $_POST-Valuas gets passed to the Widgets only on validat()
	 * @return bool
	 */
	public function isSubmitted()
	{
		return ($this->Input->post('FORM_SUBMIT') == $this->formId);
	}
	
	/**
	 * Populate widgets with this data
	 * @param array $arrData
	 */
	public function setData($arrData)
	{
		// Init form if its not already done		
		if(!$this->initialized) $this->initialize();
		
		foreach($arrData as $key => $val)
		{
			if(isset($this->arrWidgets[$key])) $this->$key = $val;
		}
	}

	/**
	 * Return all data as array
	 * @return array
	 */
	public function getData()
	{
		// Init form if its not already done		
		if(!$this->initialized) $this->initialize();
		
		$return = array();
		foreach($this->arrWidgets as $key => $objWidget)
		{
			$return[$key] = $objWidget->value;
		}
		return $return;		
	}
	
	/**
	 * Returns all errors as array
	 * @return array
	 */
	public function getErrors()
	{
		// Init form if its not already done		
		if(!$this->initialized) $this->initialize();
		
		$return = array();
		foreach($this->arrWidgets as $key => $objWidget)
		{
			$return[$key] = $objWidget->getErrors();
		}
		return $return;
	}
	
	/**
	 * Returns all errors as html-string
	 * @param str Separator
	 * @return str
	 */
	public function getErrorsAsHTML($separator = '')
	{
		// Init form if its not already done		
		if(!$this->initialized) $this->initialize();
		
		$return = '';
		foreach($this->arrWidgets as $objWidget)
		{
			$return .= $objWidget->getErrorAsHTML().$separator;
		}
		
		return (strlen($separator)>0) ? substr($return,0,-strlen($separator)) : $return;
	}
	
	/**
	 * Initialize the widgets from the DCA
	 */
	public function initialize()
	{
		// Initialize Widgets
		foreach ($this->arrDCA as $field => $arrData)
		{
			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];
			
			// TEST IF BE_FFL also work
			if(!strlen($strClass)) $strClass = $GLOBALS['BE_FFL'][$arrData['inputType']];
			
			if(!strlen($strClass)) trigger_error("InputType {$arrData['inputType']} not found in TL_FFL-Array.",E_USER_NOTICE);
			// Continue if the class is not defined
			if (!$this->classFileExists($strClass))
			{
				trigger_error("Class {$strClass} not found while initializing widgets.",E_USER_NOTICE);
				continue;
			} 
			
			$arrData['decodeEntities'] = true;
			$arrData['eval']['required'] = $arrData['eval']['mandatory'];
			$this->arrWidgets[$field] = new $strClass($this->prepareForWidget($arrData, $field, $arrData['default']));
		}
		
		$this->initialized = true;
	}
	
	/**
	 * Set a value 
	 * @param str $key the fields key
	 * @param mixed $val 
	 */
	public function __set($key,$val)
	{
		if(isset($this->arrDCA[$key]))
		{ // Access Widget-Value
			
			// Init form if its not already done
			if(!$this->initialized) $this->initialize();
			
			if(!isset($this->arrWidgets[$key])) throw new Exception("Field \"$key\" not found or initialization failed.");
			$this->arrWidgets[$key]->value = $val;
		} 
		else
		{ // Access class-attribute
			$this->$key = $val;
		}
	}
	
	/**
	 * Get a value from a widget
	 * @param str $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if(isset($this->arrDCA[$key]))
		{ // Access Widget-Value
			
			// Init form if its not already done
			if(!$this->initialized) $this->initialize();
			
			if(!isset($this->arrWidgets[$key])) throw new Exception("Field \"$key\" not found or initialization failed.");
			return $this->arrWidgets[$key]->value;
		}
		else 
		{ // Access class-attribute
			return $this->$key;
		}	
	}
	
	/**
	 * Parses the template and return the result as string
	 * @param array $arrAttribs Attributes for the formTemplate 
	 * @return string
	 */
	public function parse($arrAttribs = array())
	{
		$this->Template = new FrontendTemplate($this->formTemplate);
		$this->Template->setData($arrAttribs);
		
		list($hidden,$fields) = $this->generate(true);
		$this->Template->fields = $fields;
		$this->Template->hidden = $hidden;

		$this->Template->formSubmit = $this->formId;
		$this->Template->hasError = $this->hasErrors;
		$this->Template->attributes = (strlen($this->class)) ? ' class="'.$this->class.'"' : '';
		$this->Template->enctype = $this->hasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
		$this->Template->formId = $this->formId;
		$this->Template->action = ampersand($this->action, true);
		$this->Template->method = $this->method;

		return $this->Template->parse();
	}
	
	/**
	 * Sets a formular config parameter
	 * @param str $key
	 * @param mixed $val
	 */
	public function setConfig($param,$val)
	{
		$this->$param = $val;
	}
	
	/**
	 * Get a formular config parameter
	 * @param str $key
	 */
	public function getConfig($param)
	{
		return $this->$param;	
	}
	
	/**
	 * Add an custom widget-object to the formular
	 * @param str $key
	 * @param Widget $widget
	 * @param int $position Insert the widget on this position
	 */
	public function addWidget($key,Widget $widget, $position=false) {
		if($position !== false)
			array_insert($this->arrWidgets,$position,array($key=>$widget));
		else
			$this->arrWidgets[$key] = $widget;
	}
	
	/**
	 * Returns a reference to a specific Widget-Object
	 * @param Widget $key
	 */
	public function getWidget($key)
	{
		// Init form if its not already done		
		if(!$this->initialized) $this->initialize();
		
		return $this->arrWidgets[$key];
	}
}