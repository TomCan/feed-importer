<?php

namespace TomCan\FeedImporter;

class FeedDownloader
{
    private $bufferSize = 128 * 1024;
    private FeedProcessor $feedProcessor;
    private FeedDefinition $feed;
    private $inflateContext;
    private bool $mustYield = false;
    private array $yieldableItems = [];

    public function __construct(FeedDefinition $feed, FeedProcessor $feedProcessor)
    {
        $this->feed = $feed;
        $this->feedProcessor = $feedProcessor;

        if ('gzip' == $feed->getCompression()) {
            $this->inflateContext = inflate_init(ZLIB_ENCODING_GZIP);
        } else if ('deflate' == $feed->getCompression()) {
            $this->inflateContext = inflate_init(ZLIB_ENCODING_DEFLATE);
        } else if ('raw' == $feed->getCompression()) {
            $this->inflateContext = inflate_init(ZLIB_ENCODING_RAW);
        }
    }

    public function download() {
        // calling generate without actually looping over it will not actually call it
        foreach ($this->generate() as $item) {}
    }

    public function generate() {
        if (!is_callable($this->feedProcessor->getCallback())) {
            // no callback given, use internal callback in combination with yield
            $this->mustYield = true;
            $this->feedProcessor->setCallback([$this, 'itemCallback']);
        }

        switch ($this->feed->getTransport()) {
            case 'path':
                // direct path to file
                $fp = fopen($this->feed->getUrl(), "r");
                while (!feof($fp)) {
                    $data = fread($fp, $this->bufferSize);
                    if ('none' !== $this->feed->getCompression()) {
                        $result = inflate_add($this->inflateContext, $data, ZLIB_SYNC_FLUSH);
                        $this->feedProcessor->data($result);
                    } else {
                        $this->feedProcessor->data($data);
                    }

                    if ($this->mustYield) {
                        while (count($this->yieldableItems) > 0) {
                            yield array_shift($this->yieldableItems);
                        }
                    }
                }
                fclose($fp);

                break;

            case 'http':
            case 'ftp':
            case 'ftp+ssl':
                // curl download
                $ch = curl_init($this->feed->getUrl());
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$this, 'curlWriteCallback']);
                curl_setopt($ch, CURLOPT_BUFFERSIZE, $this->bufferSize); // Set buffer size

                if ($this->feed->getTransportOptions()) {
                    if ($this->feed->getTransportOptions()['auth']) {
                        curl_setopt($ch, CURLOPT_USERPWD, $this->feed->getTransportOptions()['auth']);
                    }
                    if ('ftp+ssl' == $this->feed->getTransport()) {
                        curl_setopt($ch, CURLOPT_USE_SSL, CURLFTPSSL_ALL);
                    }
                    if ($this->feed->getTransportOptions()['authssl']) {
                        curl_setopt($ch, CURLOPT_FTPSSLAUTH, CURLFTPAUTH_SSL);
                    }
                }

                if ($this->mustYield) {
                    // wrap in curl_multi
                    $cmh = curl_multi_init();
                    curl_multi_add_handle($cmh, $ch);

                    $stillRunning = 0;
                    do {
                        curl_multi_exec($cmh,$stillRunning);
                        while (count($this->yieldableItems) > 0) {
                            yield array_shift($this->yieldableItems);
                        }
                    } while($stillRunning > 0);
                    // make sure to also yield last elements
                    while (count($this->yieldableItems) > 0) {
                        yield array_shift($this->yieldableItems);
                    }

                    if (curl_multi_errno($cmh)) {
                        throw new \RuntimeException(curl_multi_strerror(curl_multi_errno($cmh)));
                    }

                    curl_multi_remove_handle($cmh, $ch);
                    curl_multi_close($cmh);
                } else {
                    // just execute curl call
                    curl_exec($ch);
                    if (curl_errno($ch)) {
                        throw new \RuntimeException(curl_error($ch));
                    }
                    curl_close($ch);
                }

                break;

            case 'data':
                // url actually contains the data, just pass to processor
                if ('none' !== $this->feed->getCompression()) {
                    $result = inflate_add($this->inflateContext, $this->feed->getUrl(), ZLIB_SYNC_FLUSH);
                    $this->feedProcessor->data($result);
                } else {
                    $this->feedProcessor->data($this->feed->getUrl());
                }

                if ($this->mustYield) {
                    while (count($this->yieldableItems) > 0) {
                        yield array_shift($this->yieldableItems);
                    }
                }

                break;
        }
    }

    private function curlWriteCallback($ch, $data)
    {
        // Write the compressed data to the decompressor
        if ('none' !== $this->feed->getCompression()) {
            $result = inflate_add($this->inflateContext, $data, ZLIB_SYNC_FLUSH);
            $this->feedProcessor->data($result);
        } else {
            $this->feedProcessor->data($data);
        }

        return strlen($data);
    }

    public function itemCallback(array $item) {
        $this->yieldableItems[] = $item;
    }
}