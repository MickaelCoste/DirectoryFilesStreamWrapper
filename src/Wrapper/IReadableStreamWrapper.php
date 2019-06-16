<?php

namespace MCoste\StreamWrapper\Wrapper;

/**
 * Interface to implement when creating any custom read capable
 * stream wrapper.
 * 
 * @author Mickaël Coste <mickael.coste@viacesi.fr>
 */
interface IReadableStreamWrapper extends IStreamWrapper
{

    /**
     * Reads from the stream.
     * 
     * The use of fread() will call this method. Be aware that PHP
     * uses a cache of 8192 bytes. Thus, the $count parameter will
     * always be set to 8192.
     * 
     * @param int $count The maximum number of bytes to read from the stream
     * 
     * @return string The data read from the stream
     */
    public function stream_read(int $count): string;

}