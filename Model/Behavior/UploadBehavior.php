<?php
class UploadBehavior extends ModelBehavior {

    public $defaults = array(
        'dir' => 'files',
        'prefix' => 'file-',
        'thumbs' => null,
        'types' => array('jpg' => 'mime/jpeg'),
        'size' => 1
    );
    private $_fileList = array();

    public function setup(Model $model, $config = array()) {
        if (isset($this->settings[$model->alias])) return;

        foreach ($config as $field => $options) {
            if(is_array($options)) $this->settings[$model->alias][$field] = array_merge($this->defaults, $options);
            else $this->settings[$model->alias][$options] = $this->defaults;
        }
    }

    public function beforeSave(Model $model) {
        foreach($this->settings[$model->alias] as $field => $options) {
            if(! $this->_addFile($model, $field)) return false;
        }
        return $this->_upload();
    }

    protected function _upload() {
        foreach($this->_fileList as $file) {
            $this->_moveUploadedFile($file[0], $file[1]);
        }
        return true;
    }

    protected function _addFile(Model &$model, $field) {
        $uploadPath = WWW_ROOT .  DS . $this->settings[$model->alias][$field]['dir'] . DS ;
        if (isset($model->data[$model->alias][$field])) {
            extract($model->data[$model->alias][$field]);
            if ($size && !$error) {
                if(! $this->_checkSize($model->data[$model->alias][$field]['tmp_name'])) {
                    $model->validationErrors[$field] = __(sprintf("Maximum file size limit (%sMb)", $this->settings[$model->alias]['size']));
                    return false;
                }
                if($ext = $this->_checkMime($model, $field)) {
                    $fileName = $this->_getFileName($ext);
                    $this->_fileList[] = array($model->data[$model->alias][$field]['tmp_name'], $uploadPath . $fileName);
                    $model->data[$model->alias][$field] = $fileName;
                    if(isset($model->data[$model->alias][self::_getCurrentFieldName($field)])) {
                        unlink($uploadPath . $model->data[$model->alias][self::_getCurrentFieldName($field)]);
                        unset($model->data[$model->alias][self::_getCurrentFieldName($field)]);
                    }
                } else {
                    $model->validationErrors[$field] = __("The file type is not authorized");
                    return false;
                }
            }else if(isset($model->data[$model->alias][self::_getCurrentFieldName($field)])) {
                $model->data[$model->alias][$field] = $model->data[$model->alias][self::_getCurrentFieldName($field)];
                unset($model->data[$model->alias][self::_getCurrentFieldName($field)]);
            } else {
                unset($model->data[$model->alias][$field]);
            }
        }
        return true;
    }

    protected function _checkMime($model, $field) {
        $mimeType = self::_getMime($model->data[$model->alias][$field]['tmp_name']);
        $whiteList = array_flip($this->settings[$model->alias][$field]['types']);
        if(array_key_exists($mimeType, $whiteList)) {
            return $whiteList[$mimeType];
        }
        return false;
    }

    protected function _moveUploadedFile($tmpFilePath, $filePath) {
        return move_uploaded_file($tmpFilePath, $filePath);
    }

    protected function _checkSize($tmpFilePath) {
        return filesize($tmpFilePath) / 1048576 <= $this->settings[$model->alias]['size'];
    }

    private function _getFileName($ext) {
        return 'file-' . uniqid() . '.' . $ext;
    }

    private static function _getMime($file) {
        if (function_exists("finfo_file")) {
            if($finfo = finfo_open(FILEINFO_MIME_TYPE)) {
                $mime = finfo_file($finfo, $file);
                finfo_close($finfo);
            } else {
                $finfo = new finfo(FILEINFO_MIME, "/usr/share/misc/magic");
                $mime = $finfo->file($file);
            }
            return $mime;
        } else if (function_exists("mime_content_type")) {
            return mime_content_type($file);
        } else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
            $file = escapeshellarg($file);
            $mime = shell_exec("file -bi " . $file);
            return $mime;
        } else {
            throw new CakeException('Cannot determine the file type, you should install fileinfo PHP module with PECL');
        }
    }

    private static function _getCurrentFieldName($field) {
        return 'current' . ucfirst($field);
    }
}
