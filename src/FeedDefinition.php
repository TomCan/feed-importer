<?php

namespace TomCan\FeedImporter;

class FeedDefinition
{
    private $id;
    private string $name;
    private string $source;
    private string $url;
    private string $transport = 'http';
    private array $transportOptions = [];
    private string $type = 'csv';
    private array $typeOptions = [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'firstRowHeaders' => true,
        ];
    private array $fields = [];
    private string $compression = 'none';

    public function __construct(?array $data = null)
    {
        if ($data) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function setTransport(string $transport): void
    {
        $this->transport = $transport;
    }

    public function getTransportOptions(): array
    {
        return $this->transportOptions;
    }

    public function setTransportOptions(array $transportOptions): void
    {
        $this->transportOptions = $transportOptions;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getTypeOptions(): array
    {
        return $this->typeOptions;
    }

    public function setTypeOptions(array $typeOptions): void
    {
        $this->typeOptions = $typeOptions;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function getCompression(): string
    {
        return $this->compression;
    }

    public function setCompression(string $compression): void
    {
        $this->compression = $compression;
    }
}