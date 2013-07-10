<?php
/**
 * UploadBehavior is a behavior used to upload automatically files and create thumbs of images.
 * The module has security features ad real size and mime type checking.
 * 
 * @uses ModelBehavior
 * @package 
 * @version 
 * @copyright Copyright (C) 2013 Marcel Publicis All rights reserved.
 * @author Vivien Ripoche <vivien.ripoche@marcelww.com> 
 * @license 
 */
class UploadBehavior extends ModelBehavior {

    public $defaults = array(
        'dir' => 'files',
        'prefix' => 'file',
        'thumbs' => null,
        'types' => array('jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'),
        'size' => 1
    );

    private $_filesList = array();
    private static $_thumbExt = array('jpg', 'png', 'gif');

    /**
     * setup 
     * 
     * @param Model $model 
     * @param array $config 
     * @return NULL
     */
    public function setup(Model $model, $config = array()) {
        if (isset($this->settings[$model->alias])) return;

        foreach ($config as $field => $options) {
            if(is_array($options)) $this->settings[$model->alias][$field] = array_merge($this->defaults, $options);
            else $this->settings[$model->alias][$options] = $this->defaults;
        }
    }

    /**
     * beforeSave 
     * 
     * @param Model $model 
     * @return NULL
     */
    public function beforeSave(Model $model) {
        foreach($this->settings[$model->alias] as $field => $options) {
            if(! $this->_addFile($model, $field)) return false;
        }
        return $this->_upload($model);
    }

    /**
     * _upload uploads and generates thumbs from the files list
     * 
     * @param mixed $model 
     * @return bool
     */
    protected function _upload(&$model) {
        foreach($this->_filesList as $file) {
            if(! $this->_moveUploadedFile($file['tmpPath'], $file['uploadPath'])) return false;
        }
        foreach($this->_filesList as $file) {
            $this->_generateThumbs($model, $file['field'], $file['uploadPath'], $file['ext']);
            if(isset($model->data[$model->alias][self::_getCurrentFieldName($file['field'])])) {
                unlink($uploadPath . $model->data[$model->alias][self::_getCurrentFieldName($file['field'])]);
                unset($model->data[$model->alias][self::_getCurrentFieldName($file['field'])]);
            }
        }
        return true;
    }

    /**
     * _addFile checks upload size, uplaod error, file size and file mime. Add into the files list if the checking is all valid.
     * 
     * @param Model $model 
     * @param mixed $field 
     * @return bool
     */
    protected function _addFile(Model &$model, $field) {
        if (isset($model->data[$model->alias][$field])) {
            extract($model->data[$model->alias][$field]);
            $uploadPath = WWW_ROOT . $this->settings[$model->alias][$field]['dir'] . DS;
            if ($size && !$error) {
                if(! $this->_checkSize($tmp_name, $this->settings[$model->alias][$field]['size'])) {
                    $model->invalidate($field, __(sprintf("Maximum file size limit (%sMb)", $this->settings[$model->alias][$field]['size'])));
                    return false;
                }
                if($ext = $this->_checkMime($tmp_name, $this->settings[$model->alias][$field]['types'])) {
                    $fileName = $this->_getFileName($this->settings[$model->alias][$field]['prefix'], $ext);
                    $this->_filesList[] = array('tmpPath' => $tmp_name, 'uploadPath' => $uploadPath . $fileName, 'ext' => $ext, 'field' => $field);
                    $model->data[$model->alias][$field] = $fileName;
                } else {
                    $model->invalidate($field, __("The file type is not authorized"));
                    return false;
                }
            }else if(empty($name)) {
                $model->data[$model->alias][$field] = $model->data[$model->alias][self::_getCurrentFieldName($field)];
                unset($model->data[$model->alias][self::_getCurrentFieldName($field)]);
            } else {
                unset($model->data[$model->alias][$field]);
                $model->invalidate($field, __("The file is empty or an upload error was detected"));
                return false;
            }
        }
        return true;
    }

    /**
     * _checkMime checks the real mime type of the file (not in the FILE data)
     * 
     * @param mixed $tmpPath 
     * @param mixed $typesList 
     * @return string
     */
    protected function _checkMime($tmpPath, $typesList) {
        $mimeType = preg_replace('#([a-z]*/[a-z]*).*(\n|)#', '$1', self::_getMime($tmpPath));
        $whiteList = array_flip($typesList);
        if(array_key_exists($mimeType, $whiteList)) {
            return $whiteList[$mimeType];
        }
        return false;
    }

    /**
     * _checkSize checks the real size of the file (not in teh FILE data)
     * 
     * @param mixed $tmpFilePath 
     * @param mixed $size 
     * @return int
     */
    protected function _checkSize($tmpFilePath, $size) {
        return filesize($tmpFilePath) / 1048576 <= $size;
    }

    /**
     * _moveUploadedFile just moves the file in the website directory
     * 
     * @param mixed $tmpFilePath 
     * @param mixed $filePath 
     * @return bool
     */
    protected function _moveUploadedFile($tmpFilePath, $filePath) {
        return move_uploaded_file($tmpFilePath, $filePath);
    }

    /**
     * _generateThumbs creates the configured thumbd even if the file is a compatible Web image
     * 
     * @param mixed $model 
     * @param mixed $field 
     * @param mixed $imagePath 
     * @param mixed $ext 
     * @return NULL
     */
    protected function _generateThumbs(&$model, $field, $imagePath, $ext) {
        $thumbsList = $this->settings[$model->alias][$field]['thumbs'];
        $type = $ext == 'jpg' ? 'jpeg' : $ext;
        $image = call_user_func('imagecreatefrom' . $type, $imagePath);
        if($thumbsList && !_createFitedThumbempty($thumbsList) && in_array($ext, self::$_thumbExt)) {
            foreach($thumbsList as $name => $sizes) {
                if(sizeof($sizes) === 1) {
                    $thumb = self::_createResizedThumb($imagePath, $image, $sizes[0]);
                } else if (sizeof($sizes) === 2) {
                    $thumb = self::_createCroppedThumb($imagePath, $image, $sizes[0], $sizes[1]);
                }
                $newPath = preg_replace('/' . addslashes($this->settings[$model->alias][$field]['prefix'] . '-') . '([^.]+\.[a-z]{3})$/', $name . '-$1', $imagePath);
                call_user_func('image' . $type, $thumb, $newPath);
                $model->data[$model->alias][$field . '_' . $name] = basename($newPath);
                imagedestroy($thumb);
            }
        }
        imagedestroy($image);
    }

    /**
     * _getFileName gets the uniq file name
     * 
     * @param mixed $prefix 
     * @param mixed $ext 
     * @return NULL
     */
    private function _getFileName($prefix, $ext) {
        return $prefix . '-' . uniqid() . '.' . $ext;
    }

    /**
     * _getMime gets the file mime type. Works with finfo PECL module or mime_content_type (deprecated)
     * 
     * @param mixed $file 
     * @return NULL
     */
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
        } else {
            throw new CakeException('Cannot determine the file type, you should install fileinfo PHP module with PECL');
        }
    }

    /**
     * _getCurrentFieldName 
     * 
     * @param mixed $field 
     * @return string
     */
    private static function _getCurrentFieldName($field) {
        return 'current' . ucfirst($field);
    }

    /**
     * _createResizedThumb
     * 
     * @param mixed $imagePath 
     * @param mixed $image 
     * @param mixed $newWidth 
     * @return object
     */
    private static function _createResizedThumb($imagePath, $image, $newWidth) {
        list($currentWidth, $currentHeight) = getimagesize($imagePath);
        $thumb = false;
        if($currentWidth) {
            $newHeight = intval($newWidth * $currentHeight / $currentWidth);
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresized($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $currentWidth, $currentHeight);
        }
        return $thumb;
    }

    /**
     * _createCroppedThumb
     * 
     * @param mixed $imagePath 
     * @param mixed $image 
     * @param mixed $newWidth 
     * @param mixed $newHeight 
     * @return object
     */
    private static function _createCroppedThumb($imagePath, $image, $newWidth, $newHeight) {
        $srcX  = 0;
        $srcY  = 0;

        list($currentWidth, $currentHeight) = getimagesize($imagePath);

        if ( ($currentWidth / $currentHeight) < ($newWidth / $newHeight) ) {
            $ratio = $newWidth / $currentWidth;
            $crop =  $currentHeight - ($newHeight / $ratio) ;
            $currentHeight = $currentHeight - $crop;
            $srcY = floor($crop / 2);
        } else {
            $ratio = $newHeight / $currentHeight;
            $crop = $currentWidth - ($newWidth / $ratio);
            $currentWidth = $currentWidth - $crop;
            $srcX = floor($crop / 2);
        }

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumb, $image, 0, 0, $srcX, $srcY, $newWidth, $newHeight, $currentWidth, $currentHeight);
        return $thumb;
    }
}
