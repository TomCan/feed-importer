<?php

namespace TomCan\FeedImporter;

class FeedDownloader
{
    private $bufferSize = 128 * 1024;
    private FeedProcessor $feedProcessor;
    private FeedDefinition $feed;
    private $inflateContext;

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

                curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new \RuntimeException(curl_error($ch));
                }
                curl_close($ch);

                break;

            case 'data':
                // url actually contains the data, just pass to processor
                if ('none' !== $this->feed->getCompression()) {
                    $result = inflate_add($this->inflateContext, $this->feed->getUrl(), ZLIB_SYNC_FLUSH);
                    $this->feedProcessor->data($result);
                } else {
                    $this->feedProcessor->data($this->feed->getUrl());
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
}