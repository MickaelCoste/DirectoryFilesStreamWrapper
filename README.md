# DirectoryFilesStreamWrapper

Allow to read all files of a directory with a single resource object.

## Install
```
composer require mcoste/directory-files-stream-wrapper
```


## Initialization
Before being able to use this stream wrapper, you have to register it. To do so, you have 2 ways.

### The easy way
You can use the PHP [stream_wrapper_register()](https://www.php.net/manual/en/function.stream-wrapper-register.php) function:
```
stream_wrapper_register('protocol', DirectoryFilesStreamWrapper::class);
```
Replace 'protocol' by whatever valid string you want. See the [RFC 2396 (section 3.1: Scheme Component)](http://www.ietf.org/rfc/rfc2396.txt) for more details. You can simply use DirectoryFilesStreamWrapper::DEFAULT_PROTOCOL_NAME if you want.

### The very easy way
An easier way is to call the static method DirectoryFilesStreamWrapper::register(). And that's it, you're good to go.

This method takes an optional parameter to specify the protocol name you want. By default, it uses DirectoryFilesStreamWrapper::DEFAULT_PROTOCOL_NAME.


## Usage
With the registration completed, all you have to do is open a directory like so :
```
$resource = fopen('protocol://path/to/my/directory', 'r');
```
'protocol' must be replaced by the protocol name you have chosen ('directory-files' if you used DirectoryFilesStream::register() without parameters)

You can now use the resource as any other. For exemple :
```
$line = fgets($resource);
```

The files to be read are sorted by their names using the natural order, as defined by the PHP function [natsort()](https://www.php.net/manual/en/function.natsort.php).

## Limitations
### Read only
This stream wrapper does not allow any other mode than 'r'. You can pass whatever mode you want to fopen(), **'r' will always be used.**
This limitation is needed because 'r' is the only mode that makes sense in the context of this wrapper.