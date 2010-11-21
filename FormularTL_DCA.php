<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');
/**
 * Formular class to generate formulars from $GLOBALS['TL_DCA'].
 *
 * PHP version 5
 * @copyright  4ward.media 2010
 * @author     Christoph Wiechert <christoph.wiechert@4wardmedia.de>
 * @package	   Library
 * @license    LGPL
 * @version	   0.1
 * @filesource
 * 
 */

class FormularTL_DCA extends Formular {

	/**
	 * Table name
	 * @var str
	 */
	protected $table = '';
	
	/**
	 * Constructor
	 * @param str $table Table name
	 * @param mixed $palette Palette-name to use for rendering or an array with fieldnames
	 */
	public function __construct($table,$palette='default'){
		parent::__construct($table);
		$this->import('Database');
		
		$this->table = $table;
		
		$this->loadDataContainer($this->table);
		$this->loadLanguageFile($this->table);
		
		$dca = array();
		
		if(is_array($palette))
			$fields = $palette;
		else
			$fields = explode(",",str_replace(';',',',$GLOBALS['TL_DCA'][$this->table]['palettes'][$palette]));
		
		// fill DCA with the fields from the palette
		foreach($fields as $field)
		{
			// Continue on Legends
			if(substr($field,0,1) == '{') continue;
			
			// Continue if field is not in DCA-field-list
			if(!isset($GLOBALS['TL_DCA'][$this->table]['fields'][$field])) continue;
			
			$dca[$field] = $GLOBALS['TL_DCA'][$this->table]['fields'][$field];
		}
		
		// add submit button
		// @todo auslagern oder wwi - so ists uncool
		$dca['submit'] = array
		(
			'label' 	=> 'speichern',
			'name'		=> '',
			'inputType' => 'submit'
		);
		
		$this->setDCA($dca);
	}
	
	/**
	 * Load a record form the database
	 * and populate the widgets with the data
	 * @param int $id
	 */
	public function load($id) {
		$erg = $this->Database->prepare('SELECT * FROM '.$this->table.' WHERE id=?')->execute($id);
		if($erg->numRows != 1) return false;
		else $this->setData($erg->row());
		return true;
	}
	
	/**
	 * Save data in the database
	 * insert or update the record according to $id parameter
	 * @param int $id Primary key of the record to update
	 */
	public function save($id = false){
		$data = $this->getData();
		
		// Kick out the added submit-field
		unset($data['submit']);
		
		if($id)
		{ // Update record
			return $this->Database->prepare('UPDATE '.$this->table.' %s WHERE id=?')->set($data)->execute($id);
		}
		else 
		{ // Insert record
			return $this->Database->prepare('INSERT INTO '.$this->table.' %s')->set($data)->execute();
		}
	}
	
}