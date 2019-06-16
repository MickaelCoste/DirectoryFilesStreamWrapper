<?php

namespace MCoste\StreamWrapper\Wrapper;

use MCoste\StreamWrapper\Exception\DirectoryUnreadableException;

/**
 * Interface to implement when creating any custom stream wrapper.
 * 
 * @author Mickaël Coste <mickae.coste@viacesi.fr>
 */
interface IStreamWrapper
{

    /**
     * Opens the stream.
     * 
     * @param string        $url            The URL to open as passed to fopen
     * @param string        $mode           The opening mode as passed to fopen. Only 'r' is supported
     * @param int           $options        The opening options as passed to fopen. NOT SUPPORTED
     * @param string|null   $opened_path    Set to the resource opened path when complete. NOT SUPPORTED
     * 
     * @return bool True on success, false on failure.
     * 
     * @throws DirectoryUnreadableException if the directory does not exists or is unreadable
     */
    public function stream_open(string $url, string $mode, int $options, ?string &$opened_path): bool;

    /**
     * Closes the stream.
     */
    public function stream_close(): void;

    /**
     * Retrieves the current position of the stream.
     * 
     * @return int The current position
     */
    public function stream_tell(): int;

    /**
     * Seeks to specific location in the stream.
     * 
     * @param int $offset The stream offset to seek to
     * @param int $whence How to use the offset value. See fseek() documentation
     * 
     * @return bool True if the cursor has been moved, false otherwise
     */
    public function stream_seek(int $position, int $whence = SEEK_SET): bool;

    /**
     * Checks if the end of the stream has been reached.
     * 
     * @return bool True of reached the end of the stream, false otherwise
     */
    public function stream_eof(): bool;

}