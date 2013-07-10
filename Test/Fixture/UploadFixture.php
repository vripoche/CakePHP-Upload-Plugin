<?php
class UploadFixture extends CakeTestFixture {
	var $name = 'Upload';

	var $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'picture' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'picture_small' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'picture_crop' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'other_field' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => 1,
			'picture' => 'picture.png'
		)
	);
}
?>
