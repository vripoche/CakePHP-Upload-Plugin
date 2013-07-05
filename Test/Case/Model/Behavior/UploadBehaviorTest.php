<?php
App::uses('Upload.Upload', 'Model/Behavior');
App::uses('Folder', 'Utility');

class TestUploadSimple extends CakeTestModel {
    public $useTable = 'uploads';
    public $actsAs = array(
        'Upload.Upload' => array('picture')
    );
}

class TestUploadComplete extends CakeTestModel {
    public $useTable = 'uploads';
    public $actsAs = array(
        'Upload.Upload' => array(
            'picture' => array(
                'dir' => 'files',
                'types' => array('jpg' => 'mime/jpeg', 'png' => 'mime/png', 'gif' => 'mime/gif'),
                'size' => 2,
                'thumbs' => array(
                    'small' => array(300),
                    'crop' => array(200,200)
                )
            )
        )
    );
}

class TestUploadFurther extends CakeTestModel {
    public $useTable = 'uploads';
    public $actsAs = array(
        'Upload.Upload' => array('picture', 'photo')
    );
}

class UploadBehaviorTest extends CakeTestCase {

    public $fixtures = array('plugin.upload.upload');
    public $TestUpload = null;
    public $MockUpload = null;
    public $data = array();
    public $currentTestMethod;

    function startTest($method) {
        $this->TestUploadSimple = ClassRegistry::init('TestUploadSimple');
        $this->TestUploadComplete = ClassRegistry::init('TestUploadComplete');
        $this->TestUploadFurther = ClassRegistry::init('TestUploadFurther');

        $this->currentTestMethod = $method;

        $this->data['test_insert'] = array(
            'picture' => array(
                'name'  => 'picture.jpg',
                'tmp_name'  => 'picture.jpg',
                'dir'   => '/tmp/php/file.tmp',
                'type'  => 'image/jpeg',
                'size'  => 8192,
                'error' => UPLOAD_ERR_OK,
            )
        );

        $this->data['test_insert_2'] = array(
            'picture' => array(
                'name'  => 'picture.jpg',
                'tmp_name'  => 'picture.jpg',
                'dir'   => '/tmp/php/file.tmp',
                'type'  => 'image/jpeg',
                'size'  => 8192,
                'error' => UPLOAD_ERR_OK,
            ),
            'photo' => array(
                'name'  => 'photo.jpg',
                'tmp_name'  => 'photo.jpg',
                'dir'   => '/tmp/php/file.tmp',
                'type'  => 'image/jpeg',
                'size'  => 8192,
                'error' => UPLOAD_ERR_OK,
            )
        );

        $this->data['test_update'] = array(
            'id' => 1,
            'picture' => array(
                'name'  => 'newpicture.jpg',
                'tmp_name'  => 'newpicture.jpg',
                'dir'   => '/tmp/php/file.tmp',
                'type'  => 'image/jpeg',
                'size'  => 8192,
                'error' => UPLOAD_ERR_OK,
            )
        );

        $this->data['test_update_other_field'] = array(
            'id' => 1,
            'other_field' => 'test',
            'picture' => array()
        );

        $this->data['test_remove'] = array(
            'picture' => array(
                'remove' => true,
            )
        );
    }

    public function mockUpload($methods = array()) {
        if (!is_array($methods)) {
            $methods = (array) $methods;
        }
        if (empty($methods)) {
            $methods = array('_checkMime', '_moveUploadedFile', '_checkSize');
        }

        $this->mockUploadBehavior = $this->getMock('UploadBehavior', $methods);

        $this->mockUploadBehavior->setup($this->TestUploadSimple, $this->TestUploadSimple->actsAs['Upload.Upload']);
        $this->TestUploadSimple->Behaviors->set('Upload', $this->mockUploadBehavior);

        $this->mockUploadBehavior->setup($this->TestUploadComplete, $this->TestUploadComplete->actsAs['Upload.Upload']);
        $this->TestUploadComplete->Behaviors->set('Upload', $this->mockUploadBehavior);

        $this->mockUploadBehavior->setup($this->TestUploadFurther, $this->TestUploadFurther->actsAs['Upload.Upload']);
        $this->TestUploadFurther->Behaviors->set('Upload', $this->mockUploadBehavior);
    }

    function endTest($method) {
        Classregistry::flush();
        unset($this->TestUploadSimple);
        unset($this->TestUploadComplete);
        unset($this->TestUploadFurther);
    }

    public function testSetup() {
        $this->mockUpload(array('_upload'));
    }

    public function testUploadSimple() {
        $this->mockUpload();

        $this->mockUploadBehavior->expects($this->once())->method('_checkMime')->will($this->returnValue('jpg'));
        $this->mockUploadBehavior->expects($this->once())->method('_moveUploadedFile')->will($this->returnValue(true));
        $this->mockUploadBehavior->expects($this->once())->method('_checkSize')->will($this->returnValue(true));

        $result = $this->TestUploadSimple->save($this->data['test_insert']);
        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result['TestUploadSimple']));
        $this->assertEquals(2, sizeof($result['TestUploadSimple']));

        $this->assertContains('file-', $result['TestUploadSimple']['picture']);

        $this->assertEqual($this->TestUploadSimple->id, 2);
    }

    public function testUploadComplete() {
        $this->mockUpload();

        $this->mockUploadBehavior->expects($this->once())->method('_checkMime')->will($this->returnValue('jpg'));
        $this->mockUploadBehavior->expects($this->once())->method('_moveUploadedFile')->will($this->returnValue(true));
        $this->mockUploadBehavior->expects($this->once())->method('_checkSize')->will($this->returnValue(true));

        $result = $this->TestUploadComplete->save($this->data['test_insert']);
        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result['TestUploadComplete']));
        $this->assertEquals(2, sizeof($result['TestUploadComplete']));

        $this->assertContains('file-', $result['TestUploadComplete']['picture']);

        $this->assertEqual($this->TestUploadComplete->id, 2);
    }

    public function testUploadFurther() {
        $this->mockUpload();

        $this->mockUploadBehavior->expects($this->any())->method('_checkMime')->will($this->returnValue('jpg'));
        $this->mockUploadBehavior->expects($this->any())->method('_moveUploadedFile')->will($this->returnValue(true));
        $this->mockUploadBehavior->expects($this->any())->method('_checkSize')->will($this->returnValue(true));

        $result = $this->TestUploadFurther->save($this->data['test_insert_2']);
        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result['TestUploadFurther']));
        $this->assertEquals(3, sizeof($result['TestUploadFurther']));

        $this->assertContains('file-', $result['TestUploadFurther']['picture']);
        $this->assertContains('file-', $result['TestUploadFurther']['photo']);

        $this->assertEqual($this->TestUploadFurther->id, 2);
    }
}
