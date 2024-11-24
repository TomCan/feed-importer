<?php

namespace TomCan\FeedImporter;

interface FeedProcessor
{
    public function data($data);
    public function getCallback(): ?callable;
    public function setCallback(?callable $callback);
}