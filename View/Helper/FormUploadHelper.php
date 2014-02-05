<?php
App::uses('FormHelper', 'View/Helper');
App::uses('HtmlHelper', 'View/Helper');

class FormUploadHelper extends FormHelper {
    public $helpers = array('Html');
    const RETINA_SUFFIX = '@2x.';
    private static $_imageTypes = array('jpg', 'png', 'gif');
    public function create($model = null, $options = array()) {
        $options['type'] = 'file';
        return parent::create($model, $options);
    }
    public function fileInput($fieldName, $options = array()) {
        $output = null;
        $options = $this->_initInputField($fieldName, $options);
        $options['type'] = 'file';
        $options['dir'] = isset($options['dir']) ? $options['dir'] : 'files';

        $currentFieldName = 'current' . ucfirst($fieldName);

        $output .= $this->input($fieldName, $options);
        if(isset($options['isEdition']) && $options['isEdition']) {
        	if(!empty($options['changeName']))
        		$fileName = $options['changeName'];
        	else
           		$fileName = is_array($options['value']) ? $this->data[$this->defaultModel][$currentFieldName] : $options['value'];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if(in_array($ext, self::$_imageTypes)) {
                $output .= $this->Html->image('/' . $options['dir'] . '/' . $fileName, array('alt' => '', 'width' => 200));
            } else {
            	if(!empty($fileName)) $output .= $this->Html->link('/' . $options['dir'] . '/' . $fileName);
            }
            $output .= $this->input($currentFieldName, array( 'type' => 'hidden', 'value' => $fileName, 'name' => 'data[' . $this->defaultModel .'][' . $currentFieldName . ']', 'id' => $this->defaultModel . ucfirst($currentFieldName)));
        }
        return $output;
    }
    public function thumbName($filename, $prefix) {
        return preg_replace('/^.*\-/', $prefix . '-', $filename);
    }
}
