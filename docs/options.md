# Configuration options for FeedDefinition

Configuring the FeedDefinition is done by passing a configuration array to the constructor of the object. Any keys
matching with known options will be applied to the FeedDefinition. Unrecognized keys are silently ignored.

## url
The url to read from. You will always need this. The `transport` option will define how this url is treated.

## transport
The `transport` options defines how the actual data is retrieved. Currently, the following values are supported:

`path`: The `url` is treated as a filepath accessible through the local computer. The file is opened using the PHP
`fopen` function in read mode 'r'. Any supported file or streamhandler can be used.

`http`: The `url` is treated as an actual http/https URL and a `GET` request is performed to retrieve the file using 
`curl`. This is the default value.

`ftp`: This behaves exactly the same as `http` as `url` will be passed to `curl`. 

`ftp+ssl`: This will negotiate SSL as part of the FTP protocol. This will set `CURLOPT_USE_SSL` to `CURLFTPSSL_ALL`. 
Not to be confused with ftps, which will setup a secure connection first and then starts the FTP protocol over that 
connection. If you want to use ftps on the entire connection, you can use `ftps://` in your `url` instead.

`data`: The `url` isn't actually a url, but instead just the raw data you want to process. Can be usefull for testing
small sets of data without actually having to download the data.

## transportOptions
Depending on the `transport` you might need to define additional options specific for that transport. The 
`transportOptions` option is an `array` where each option is a key => value pair. Following options are available:

`auth`: HTTP or FTP authentication in `username:password` form. This will be used by `curl` using the `CURLOPT_USERPWD`

`authssl`: If set to `true`, SSL authentication will be enabled by setting `CURLOPT_FTPSSLAUTH` to `CURLFTPAUTH_SSL`.

## compression
If this is set to a supported value, the raw data is treated as compressed and will be decompressed on the fly using the
zlib `inflate_init` and `inflate_add` functions. Supported values are:

`none`: No compression will be used. `inflate_init` will not be called.

`gzip`: `inflate_init` will be called with `ZLIB_ENCODING_GZIP`.

`deflate`: `inflate_init` will be called with `ZLIB_ENCODING_DEFLATE`.

`raw`: `inflate_init` will be called with `ZLIB_ENCODING_RAW`.

For more information regarding these values, see the ` inflate_init` PHP documentation. Spoiler alert: regular `zip`
files are not supported.

## type
Unused, initialized to `csv`. 

## typeOptions
Depending on the type (in this case always `csv`), you might need to define additional options specific for that type. 
The `typeOptions` option is an `array` where each option is a key => value pair. Following options are available:

`delimiter`, `enclosure` and `escape` refer to the corresponding parameters of the `fgetcsv` that is used to parse a
line into an array.

`firstRowHeaders`: When set to true, the `FeedProcessorCsv` will treat the first row of the file as header names.
Depending on your field definition, this first row is used to automatically map the requested fields to the corresponding
field in the csv file.

## fields
The `fields` option contains an array with the field mapping of the output array that is passed to the callback function.
You can specify the fieldname of the output array as the key of the array, and the source column of the csv as the value.

If the `fields` option is an empty array and `firstRowHeaders` option is set to true, then all values of the first row
will be converted to field definitions by index.

`name,score,reason` will result in a fields array of `['name' => 0, 'score' => 1, 'reason' => 2]`.

If the `fields` array is not empty, there are 3 ways of defining the fields:

### By index
Key value pairs where key is the name of the field in the output array, and value is the numeric index of 
field in the input array.

Definition:  
`['name' => 0, 'score' => 1, 'reason' => 2]`

Result:  
`Tom,10,I did that` => `['name' => 'Tom', 'score' => 10, 'reason' => 'I did that']`

### By source name
The array just contains the field names. Field names will be searched for in the first line of data. This
requires `firstRowHeaders` to be true. 

Definition:  
`['score','name']`

Result:  
```
name,score,reason
Tom,10,I did that
```
 => `['score' => 10, 'name' => 'Tom']`

### By target and source name
The array contains key => value pairs where the key is used as the name of the field in the output array, and the value
is the field name of the field in the data. Field names will be searched for in the first line of data. This
requires `firstRowHeaders` to be true.

Definition:  
`['naam' => 'name', 'reden' => 'reason']`

Result:
```
name,score,reason
Tom,10,I did that
```
=> `['naam' => 'Tom', 'reden' => 'I did that']`
