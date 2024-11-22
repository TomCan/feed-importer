# Tom's Feed Importer

Because everybody loves importing csv files...

## Why?

Importing data from csv feeds can be hard. Often you'll end up with a custom implementation, closely coupling the 
downloading, reading, processing of the data. Each of them comes with their own difficulties and complexity. Is it on 
disk, http or a SSL authenticated ftp? Do you even have enough memory or diskspace available to download and extract 
that 2GB large gzipped, 12GB unzipped products.csv.gz?  

The result is often a mess of control code mixed in with your business logic. And then you need to do the same but 
differently for another feed, and you end up duplicating half of your code and rewriting the rest. I have been there
too! And I was feed up with it (pun intended).  

## What

What if I told you that you now can just define the feed, write your logic to process a single record and be done?  

Tom's Feed Importer abstracts all the feed handling parts away and just gives you the records one by one, so that you
can process them in small, sizeable parts.

```
    // define the feed
    $feed = new FeedDefinition(['url' => 'https://raw.githubusercontent.com/TomCan/feed-importer/refs/heads/main/samples/toms-favorite-names.csv']);

    // write your callback
    $callback = function (array $row) {
        echo 'Got a record: '.str_replace(PHP_EOL, ' ', print_r($row, true)).PHP_EOL;
    };

    // instantiate processor
    $processor = new FeedProcessorCsv($feed, $callback);
    // instantiate downloader
    $downloader = new FeedDownloader($feed, $processor);
    // profit
    $downloader->download();
```

It mostly comes down to defining your feed parameters with a `FeedDefinition`. See the [`options`](docs/options.md) page in the docs for a 
reference of the available options.