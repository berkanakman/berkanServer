<?php

namespace Berkan\Tests;

use Berkan\Filesystem;

class FilesystemTest extends TestCase
{
    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
    }

    public function test_mkdir_creates_directory(): void
    {
        $path = $this->tempDir . '/test-dir';

        $this->files->mkdir($path);

        $this->assertTrue(is_dir($path));
    }

    public function test_mkdir_does_not_fail_if_directory_exists(): void
    {
        $path = $this->tempDir . '/test-dir';
        mkdir($path, 0755, true);

        $this->files->mkdir($path);

        $this->assertTrue(is_dir($path));
    }

    public function test_ensure_dir_exists(): void
    {
        $path = $this->tempDir . '/deep/nested/dir';

        $this->files->ensureDirExists($path);

        $this->assertTrue(is_dir($path));
    }

    public function test_put_and_get(): void
    {
        $path = $this->tempDir . '/test-file.txt';

        $this->files->put($path, 'hello world');

        $this->assertEquals('hello world', $this->files->get($path));
    }

    public function test_exists(): void
    {
        $path = $this->tempDir . '/exists.txt';

        $this->assertFalse($this->files->exists($path));

        file_put_contents($path, 'content');

        $this->assertTrue($this->files->exists($path));
    }

    public function test_is_dir(): void
    {
        $this->assertTrue($this->files->isDir($this->tempDir));
        $this->assertFalse($this->files->isDir($this->tempDir . '/nonexistent'));
    }

    public function test_append(): void
    {
        $path = $this->tempDir . '/append.txt';
        file_put_contents($path, 'line1');

        $this->files->append($path, "\nline2");

        $this->assertEquals("line1\nline2", file_get_contents($path));
    }

    public function test_copy(): void
    {
        $from = $this->tempDir . '/source.txt';
        $to = $this->tempDir . '/dest.txt';

        file_put_contents($from, 'copied content');
        $this->files->copy($from, $to);

        $this->assertTrue(file_exists($to));
        $this->assertEquals('copied content', file_get_contents($to));
    }

    public function test_unlink(): void
    {
        $path = $this->tempDir . '/delete-me.txt';
        file_put_contents($path, 'content');

        $this->assertTrue(file_exists($path));

        $this->files->unlink($path);

        $this->assertFalse(file_exists($path));
    }

    public function test_unlink_nonexistent_does_not_fail(): void
    {
        $this->files->unlink($this->tempDir . '/nonexistent.txt');

        $this->assertTrue(true); // No exception thrown
    }

    public function test_touch(): void
    {
        $path = $this->tempDir . '/touched.txt';

        $result = $this->files->touch($path);

        $this->assertTrue(file_exists($path));
        $this->assertEquals($path, $result);
    }

    public function test_scandir(): void
    {
        mkdir($this->tempDir . '/scan');
        file_put_contents($this->tempDir . '/scan/a.txt', 'a');
        file_put_contents($this->tempDir . '/scan/b.txt', 'b');
        mkdir($this->tempDir . '/scan/sub');

        $result = $this->files->scandir($this->tempDir . '/scan');

        $this->assertContains('a.txt', $result);
        $this->assertContains('b.txt', $result);
        $this->assertContains('sub', $result);
        $this->assertNotContains('.', $result);
        $this->assertNotContains('..', $result);
    }

    public function test_remove_directory(): void
    {
        $dir = $this->tempDir . '/remove-me';
        mkdir($dir);
        file_put_contents($dir . '/file.txt', 'content');
        mkdir($dir . '/sub');
        file_put_contents($dir . '/sub/nested.txt', 'nested');

        $this->files->remove($dir);

        $this->assertFalse(is_dir($dir));
    }

    public function test_symlink_and_is_link(): void
    {
        $target = $this->tempDir . '/link-target';
        $link = $this->tempDir . '/symlink';
        mkdir($target);

        $this->files->symlink($target, $link);

        $this->assertTrue($this->files->isLink($link));
        $this->assertEquals($target, $this->files->readLink($link));
    }

    public function test_symlink_replaces_existing(): void
    {
        $target1 = $this->tempDir . '/target1';
        $target2 = $this->tempDir . '/target2';
        $link = $this->tempDir . '/symlink';

        mkdir($target1);
        mkdir($target2);

        $this->files->symlink($target1, $link);
        $this->files->symlink($target2, $link);

        $this->assertEquals($target2, $this->files->readLink($link));
    }

    public function test_is_empty(): void
    {
        $path = $this->tempDir . '/empty.txt';
        file_put_contents($path, '');

        $this->assertTrue($this->files->isEmpty($path));

        file_put_contents($path, 'not empty');

        $this->assertFalse($this->files->isEmpty($path));
    }

    public function test_files_recursive(): void
    {
        $dir = $this->tempDir . '/recursive';
        mkdir($dir . '/sub', 0755, true);
        file_put_contents($dir . '/a.txt', 'a');
        file_put_contents($dir . '/sub/b.txt', 'b');

        $result = $this->files->files($dir);

        $this->assertCount(2, $result);
    }

    public function test_files_returns_empty_for_nonexistent(): void
    {
        $result = $this->files->files($this->tempDir . '/nonexistent');

        $this->assertEquals([], $result);
    }

    public function test_remove_line(): void
    {
        $path = $this->tempDir . '/lines.txt';
        file_put_contents($path, "line1\nremove-this\nline3");

        $this->files->removeLine($path, '/remove-this/');

        $contents = file_get_contents($path);
        $this->assertStringNotContainsString('remove-this', $contents);
        $this->assertStringContainsString('line1', $contents);
        $this->assertStringContainsString('line3', $contents);
    }

    public function test_copy_directory(): void
    {
        $from = $this->tempDir . '/copy-from';
        $to = $this->tempDir . '/copy-to';
        mkdir($from . '/sub', 0755, true);
        file_put_contents($from . '/file1.txt', 'content1');
        file_put_contents($from . '/sub/file2.txt', 'content2');

        $this->files->copyDirectory($from, $to);

        $this->assertTrue(file_exists($to . '/file1.txt'));
        $this->assertTrue(file_exists($to . '/sub/file2.txt'));
        $this->assertEquals('content1', file_get_contents($to . '/file1.txt'));
        $this->assertEquals('content2', file_get_contents($to . '/sub/file2.txt'));
    }

    public function test_commented_out_php_ini_value(): void
    {
        $path = $this->tempDir . '/php.ini';
        file_put_contents($path, ";memory_limit = 128M\nupload_max_filesize = 2M\n");

        $this->assertEquals('128M', $this->files->commentedOutPhpIniValue($path, 'memory_limit'));
        $this->assertEquals('2M', $this->files->commentedOutPhpIniValue($path, 'upload_max_filesize'));
        $this->assertNull($this->files->commentedOutPhpIniValue($path, 'nonexistent'));
    }

    public function test_commented_out_php_ini_value_nonexistent_file(): void
    {
        $this->assertNull($this->files->commentedOutPhpIniValue('/nonexistent', 'key'));
    }
}
