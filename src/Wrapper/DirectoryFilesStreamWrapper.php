<?php

namespace MCoste\StreamWrapper\Wrapper;

use MCoste\StreamWrapper\Exception\DirectoryUnreadableException;

use ArrayIterator;
use ArrayObject;

/**
 * A stream wrapper allowing to read from multiple files with a single resource object.
 * 
 * Takes a directory to open and uses all the files inside, except hidden files,
 * directories and unreadable files. The files are sorted by their names using
 * a "natural order" as defined by natsort() {@link https://www.php.net/manual/en/function.natsort.php}
 * 
 * @author Mickaël Coste <mickael.coste@viacesi.fr>
 */
class DirectoryFilesStreamWrapper implements IReadableStreamWrapper
{

    /**
     * The protocol name registered if none is specified when using {@see DirectoryFilesStreamWrapper::register()}
     */
    const DEFAULT_PROTOCOL_NAME = 'directory-files';

    /**
     * @var int The directory path
     */
    protected $directory;

    /**
     * @var ArrayIterator The files list iterator
     */
    protected $files;

    /**
     * @var resource The currently opened file stream
     */
    protected $resource;

    /**
     * Register this stream wrapper for PHP to be able to use.
     * 
     * @param string $protocol The protocol name to register
     * 
     * @return string|false The protocol name on success, false on failure
     */
    public static function register(string $protocol = self::DEFAULT_PROTOCOL_NAME)
    {
        return stream_wrapper_register($protocol, self::class)
            ? $protocol
            : false;
    }

    /**
     * {@inheritDoc}
     */
    public function stream_open(string $url, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->directory = $this->getDirectoryPath($url);
        if(!$this->isReadableDirectory($this->directory)) {
            throw new DirectoryUnreadableException($this->directory);
        }

        $this->files = $this->readDirectory($this->directory);
        $this->openCurrentFile();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function stream_close(): void
    {
        $this->closeCurrentFile();
    }

    /**
     * {@inheritDoc}
     */
    public function stream_read(int $count): string
    {
        $content = "";
        $toRead = $count;

        while($toRead > 0 && !$this->stream_eof()) {
            $read = fread($this->resource, $toRead);
            $toRead -= strlen($read);
            $content .= $read;
            if($toRead > 0) { $this->goToNextFile(); }
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function stream_tell(): int
    {
        $position = 0;

        $it = clone $this->files;
        $currentKey = $it->key();
        $it->rewind();

        while($it->valid()) {
            $position += $it->key() !== $currentKey
                ? filesize($it->current())
                : ftell($this->resource);
            if($it->key() === $currentKey) { break; }
        }

        return $position;
    }

    /**
     * {@inheritDoc}
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        switch($whence) {
            case SEEK_SET: return $this->setStreamPosition($offset);
            case SEEK_CUR: return $this->setStreamPosition($this->stream_tell() + $offset);
            case SEEK_END: return $this->setStreamPosition($this->getStreamSize() + $offset);
            default: return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function stream_eof(): bool
    {
        return !$this->files->valid() || feof($this->resource);
    }

    /**
     * Extract the directory path from the URL.
     * 
     * @param string $url The URL to extract the path from
     * 
     * @return string The path of the directory
     */
    protected function getDirectoryPath(string $url): string
    {
        $pos = strpos($url, '://');
        return false !== $pos
            ? substr($url, $pos + 3)
            : $url;
    }

    /**
     * Check if the path given is readable directory.
     * 
     * @param string $path The path of the file to check
     * 
     * @return bool True if the path is a readable directory, false otherwise
     */
    protected function isReadableDirectory(string $path): bool
    {
        return is_dir($path) && is_readable($path);
    }

    /**
     * Get an iterator for the list of files in the given directory. Hidden files,
     * directories and unreadable files are excluded from the list. The files are
     * sorted using natsort() PHP function.
     * 
     * @param string $directory The path (absolute or relative) or the directory
     * 
     * @return ArrayIterator The iterator for the files
     */
    protected function readDirectory(string $directory): ArrayIterator
    {
        $files = new ArrayObject();

        $d = opendir($directory);
        while(false !== ($e = readdir($d))) {
            $path = $directory.DIRECTORY_SEPARATOR.$e;
            if(substr($e, 0, 1) === '.' || !is_file($path) || !is_readable($path)) { continue; }
            $files[] = $path;
        }

        $files->natsort();
        return $files->getIterator();
    }

    /**
     * Open the next file if possible.
     */
    protected function goToNextFile()
    {
        $this->closeCurrentFile();
        $this->files->next();
        $this->openCurrentFile();
    }

    /**
     * Open the current file if possible.
     */
    protected function openCurrentFile()
    {
        if($this->files->valid()) {
            $this->resource = fopen($this->files->current(), 'r');
        }
    }

    /**
     * Close the current file if opened.
     */
    protected function closeCurrentFile()
    {
        if(is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    /**
     * Get the total size in bytes of the stream.
     * 
     * @return int The size of the stream
     */
    protected function getStreamSize(): int
    {
        $size = 0;
        $it = clone $this->files;
        $it->rewind();

        foreach($it as $file) { $size += filesize($file); }

        return $size;
    }

    /**
     * Seek to a specific position in the stream. Change the current file
     * and the position in it accordingly.
     * 
     * @param int $position The new position in the stream
     * 
     * @return bool True on success, false on error
     */
    protected function setStreamPosition(int $position): bool
    {
        $p = 0;
        $it = clone $this->files;
        $it->rewind();

        foreach($it as $file) {
            $size = filesize($file);
            if($p + $size >= $position) {
                $this->closeCurrentFile();
                $this->files = $it;
                $this->openCurrentFile();
                fseek($this->resource, $position - $p);
                return true;
            }
            $p += $size;
        }

        return false;
    }

}