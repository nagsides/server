<?php
/**
 * @package plugins.reach
 * @subpackage Admin
 */
class Form_DictionariesSubForm extends ConfigureSubForm
{
	private $ignore = array('relatedObjects', 'type', 'gs');
	private $prefix = "Dictionary_";

	private $type;

	public function __construct($type)
	{
		$this->type = $type;
		parent::__construct();
	}

	public function init()
	{
		$this->setAttrib('id', 'frmDictionariesSubForm');
		$this->setMethod('post');
		$this->addDecorator('ViewScript', array(
			'viewScript' => 'dictionary-sub-form.phtml',
		));

		$obj = new $this->type();
		$this->addObjectProperties($obj, $this->ignore, $this->prefix);

	}

	public function isValid($data)
	{
		if (!$data['VendorProfileDictionaries'] || !empty(json_decode($data['VendorProfileDictionaries'], true)))
			return true;
		else
			return false;
	}
}