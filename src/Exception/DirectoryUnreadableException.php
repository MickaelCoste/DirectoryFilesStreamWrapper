<?php

namespace MCoste\StreamWrapper\Exception;

use Exception;

class DirectoryUnreadableException extends Exception
{

    protected $_message_ = 'The directory "%s" cannot be read. Either it doesn\'t exists or you don\'t have the permission.';
    
    public function __construct(string $directory)
    {
        $message = sprintf($this->_message_, $directory);
        parent::__construct($message);
    }

}