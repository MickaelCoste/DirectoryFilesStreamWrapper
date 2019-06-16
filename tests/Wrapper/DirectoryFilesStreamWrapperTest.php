<?php

use PHPUnit\Framework\TestCase;

use MCoste\StreamWrapper\Wrapper\DirectoryFilesStreamWrapper;
use MCoste\StreamWrapper\Exception\DirectoryUnreadableException;

class DirectoryFilesStreamWrapperTest extends TestCase
{

    public function testBasicRegister()
    {
        $this->assertEquals(
            DirectoryFilesStreamWrapper::DEFAULT_PROTOCOL_NAME,
            DirectoryFilesStreamWrapper::register(),
            'The DirectoryFilesStreamWrapper::register() with no parameters failed to register the protocol'
        );
    }

    public function testRegisterWithCustomProtocolName()
    {
        $protocol = 'custom';

        $this->assertEquals(
            $protocol,
            DirectoryFilesStreamWrapper::register($protocol),
            'The DirectoryFilesStreamWrapper::register() with a valid parameter failed to register the protocol'
        );
    }

    public function testRegisterWithBadProtocolName()
    {
        $protocol = 'invalid_protocol_name';

        $this->assertEquals(
            false,
            @DirectoryFilesStreamWrapper::register($protocol),
            'The DirectoryFilesStreamWrapper::register() with an invalid paramaters didn\'t failed as expected'
        );
    }

    public function testOpenExistingDirectoryByAbsolutePath()
    {
        $protocol = 'absolute';
        $directory = __DIR__;

        DirectoryFilesStreamWrapper::register($protocol);
        $r = fopen("$protocol://$directory", 'r');
        $this->assertTrue(
            is_resource($r),
            'The stream wrapper failed to open the directory designed by its absolute path'
        );
        fclose($r);
    }

    public function testOpenExistingDirectoryByRelativePath()
    {
        $protocol = 'relative';
        $directory = '.';

        DirectoryFilesStreamWrapper::register($protocol);
        $r = fopen("$protocol://$directory", 'r');
        $this->assertTrue(
            is_resource($r),
            'The stream wrapper failed to open the directory designed by a relative path'
        );
        fclose($r);
    }

    public function testOpenNotExistingDirectory()
    {
        $protocol = 'not-existing';
        $directory = uniqid('directory:');

        $this->expectException(DirectoryUnreadableException::class);

        DirectoryFilesStreamWrapper::register($protocol);
        $r = fopen("$protocol://$directory", 'r');
        fclose($r);
    }

    public function testReadManyFiles()
    {
        $protocol = 'read-many';
        $directory = tempnam(sys_get_temp_dir(), 'TEST');

        if(file_exists($directory)) { unlink($directory); }
        mkdir($directory);
        
        $files = [
            'This is file 1 content.' => 'file1.txt',
            'This is file 2 content.' => 'file2.txt',
            'This is file 3 content.' => 'file3.txt'
        ];

        natsort($files);
        $readExpected = '';
        foreach($files as $content => $file) {
            file_put_contents($directory.DIRECTORY_SEPARATOR.$file, $content);
            $readExpected .= $content;
        }

        DirectoryFilesStreamWrapper::register($protocol);
        $r = fopen("$protocol://$directory", 'r');
        $this->assertEquals(
            $readExpected,
            fgets($r),
            'The read string and the expected one do not match. Reading error !'
        );
        fclose($r);

        foreach($files as $file) {
            unlink($directory.DIRECTORY_SEPARATOR.$file);
        }
        rmdir($directory);
    }

    public function testReadNaturalOrderedFiles()
    {
        $protocol = 'read-ordered';
        $directory = tempnam(sys_get_temp_dir(), 'TEST');

        if(file_exists($directory)) { unlink($directory); }
        mkdir($directory);
        
        $files = [
            'This is file 2 content.' => 'file2.txt',
            'This is file 1 content.' => 'file1.txt',
            'This is file 3 content.' => 'file3.txt',
            'This is file 21 content.' => 'file21.txt',
            'This is file ABC content.' => 'fileABC.txt',
            'This is file a content.' => 'filea.txt',
            'This is file 012 content.' => 'file012.txt',
            'This is file 145879 content.' => 'file145879.txt',
            'This is file 3bis content.' => 'file3bis.txt',
        ];

        natsort($files);
        $readExpected = '';
        foreach($files as $content => $file) {
            file_put_contents($directory.DIRECTORY_SEPARATOR.$file, $content);
            $readExpected .= $content;
        }

        DirectoryFilesStreamWrapper::register($protocol);
        $r = fopen("$protocol://$directory", 'r');
        $this->assertEquals(
            $readExpected,
            fgets($r),
            'The read string and the expected one do not match. Reading error !'
        );
        fclose($r);

        foreach($files as $file) {
            unlink($directory.DIRECTORY_SEPARATOR.$file);
        }
        rmdir($directory);
    }

}