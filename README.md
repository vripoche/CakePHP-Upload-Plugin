CakePHP-Upload-Plugin
=====================

For CakePHP 2.x, used to automatically upload files and control their integrity (size and file types)

Setup
-----

You must install Fileinfo PHP module before: http://pecl.php.net/package/Fileinfo

You need to clone the project into a "Plugin" directory in app/Plugin.
Then, add this "CakePlugin::load" in the app bootstrap:

> CakePlugin::load('Upload');

Configure The Behavior in Models
--------------------------------

>    public $actsAs = array(
>        'Upload.Upload' => array(
>            'photo',
>            'picture' => array(
>                'prefix' => 'file',
>                'dir' => 'files',
>                'types' => array('jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'),
>                'size' => 100,
	             'retina' => false,
>                'thumbs' => array(
>                    'small' => array(300),
>                    'crop' => array(200,200)
>                )
>            )
>        )
>    );

Add the upload fields in the Behavior declaration, you can add custom options or not, here the fields are "photo" and "picture".

- prefix: the prefix of the uploaded file that will be for example: file-123abc456.jpg
- dir: the webroot directory where the file will be uploaded, "files" by default,
- types: the file types authorized to download, "jpg", "png" andd "gif" by default. The key is the extension and the value the mime type,
- size: the maximum file size authorized,
- retina: the uploaded file will be used for a retina display,
- thumbs: the thumbs for Web images formats. The key will the the prefix (ie: small-123abc456.jpg), and the value(s) the width or the sizes.

Use the UploadForm Helper in Views
----------------------------------

Instead od using Form Helper It necessary to use its brother, UploadForm, first add the Helper declaration in Controller:

>   public $helpers = array('Upload.FormUpload');

Secondly, use it in add View:

>   echo $this->FormUpload->create('Item');
>   echo $this->FormUpload->fileInput('photo');
>   echo $this->FormUpload->fileInput('picture');
>   echo $this->FormUpload->end(__('Submit'));

Finally, use it in edit View, you can specify "isEdition" with true if you want to see the image or a link to the file in the form:

>   echo $this->FormUpload->create('Item');
>   echo $this->FormUpload->fileInput('photo', array('isEdition' => true));
>   echo $this->FormUpload->fileInput('picture', array('isEdition' => true));
>   echo $this->FormUpload->end(__('Submit'));

If you need to get the thumb file name, it is possible to use "thumbName":

> echo $this->FormUpload->thumnName($filename, 'small');

The first argument is the file name and the second if the thumb prefix.
