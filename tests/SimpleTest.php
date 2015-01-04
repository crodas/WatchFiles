<?php

use WAtchFiles\Watch;

class SimpleTest extends \phpunit_framework_testcase
{
    public function testFile()
    {
        $watch = new Watch(TMP . '/test-file.php');
        $watch->watchFile(__DIR__ . '/features/somefolder/foobar.txt');
        $watch->watch();
        $this->assertFalse($watch->hasChanged());
        touch(__DIR__ . '/features/somefolder/foobar.txt', time());
        clearstatcache();
        $this->assertNotEquals($watch->hasChanged(), false);
    }

    public function testFileRubbishInpt()
    {
        $watch = new Watch(TMP . '/test-file.php');
        $watch->watchFile(__DIR__ . '/features/somefolder/xxx.txt');
        $this->assertTrue($watch->hasChanged());
    }

    public function testGlobsArray()
    {
        $watch = new Watch(TMP . '/test-array-arrayx.php');
        $watch->watchGlobs(array(__DIR__ . '/featur*'));
        $watch->watch();
        $this->assertFalse($watch->hasChanged());
        touch(__DIR__ . '/features/' . uniqid(true) . '.txt', time());
        sleep(1);
        clearstatcache();
        $this->assertNotEquals($watch->hasChanged(), false);
    }


    public function testGlobArray()
    {
        $watch = new Watch(TMP . '/test-array-array.php');
        $watch->watchGlob(array(__DIR__ . '/featur*'));
        $watch->watch();
        $this->assertFalse($watch->hasChanged());
        touch(__DIR__ . '/features/' . uniqid(true) . '.txt', time());
        sleep(1);
        clearstatcache();
        $this->assertNotEquals($watch->hasChanged(), false);
    }

    public function testGlob()
    {
        $watch = new Watch(TMP . '/test-glob.php');
        $watch->watchGlob(__DIR__ . '/featur*');
        $watch->watch();
        $this->assertFalse($watch->hasChanged());
        touch(__DIR__ . '/features/' . uniqid(true) . '.txt', time());
        sleep(1);
        clearstatcache();
        $this->assertNotEquals($watch->hasChanged(), false);
    }

    public function testDir()
    {
        $watch = new Watch(TMP . '/test-dirdir.php');
        $watch->watchDir(TMP);
        $watch->watch();
        sleep(2);

        $this->assertFalse($watch->hasChanged());

        file_put_contents(TMP . "/" . uniqid(true) . '.txt', uniqid(true));
        sleep(2);
        clearstatcache();

        $this->assertNotEquals($watch->hasChanged(), false);

        $watch->rebuild();
        sleep(2);
        clearstatcache();

        $this->assertFalse($watch->hasChanged());
    }

    public function testGetFiles()
    {
        $watch = new Watch(TMP . '/test-file.php');
        $this->assertTrue(is_array($watch->getFiles()));
        $this->assertFalse(empty($watch->getFiles()));
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testGetDirsException()
    {
        $watch = new Watch(TMP . '/test-filexx.php');
        $watch->getDirs();
    }


    /**
     *  @expectedException RuntimeException
     */
    public function testGetFilesException()
    {
        $watch = new Watch(TMP . '/test-filexx.php');
        $watch->getFiles();
    }

    public function testRebuild()
    {
        $watch = new Watch(TMP . '/test-file.php');
        $watch->rebuild();
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testRebuildException()
    {
        $watch = new Watch(TMP . '/test-filexx.php');
        $watch->rebuild();
    }
}
