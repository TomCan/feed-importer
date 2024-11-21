<?php

namespace TomCan\FeedImporter;

use JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable;

class FeedProcessorCsv implements FeedProcessor
{
    private string $buffer;
    private int $rowCount = 0;
    private FeedDefinition $feedDefinition;
    private $callback;

    public function __construct(FeedDefinition $feedDefinition, callable $callback)
    {
        $this->buffer = '';
        $this->feedDefinition = $feedDefinition;
        $this->callback = $callback;
        $this->rowCount = 0;
    }

    public function data($data)
    {
        $this->buffer .= $data;

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $this->buffer);
        rewind($handle);

        $pos = 0;
        while (strrpos(substr($this->buffer, $pos), "\n") !== false) {
            $row = fgetcsv($handle, null, $this->feedDefinition->getTypeOptions()['delimiter'], $this->feedDefinition->getTypeOptions()['enclosure'], $this->feedDefinition->getTypeOptions()['escape']);
            $pos = ftell($handle);

            if (0 == $this->rowCount && isset($this->feedDefinition->getTypeOptions()['firstRowHeaders']) && $this->feedDefinition->getTypeOptions()['firstRowHeaders']) {
                // first row contains headers
                $flds = [];
                if (0 == count($this->feedDefinition->getFields())) {
                    // no fields defined, use and return all csv fields
                    foreach ($row as $index => $field) {
                        $flds[$field] = $index;
                    }
                } else {
                    // find index of desired fields
                    $fdFields = $this->feedDefinition->getFields();
                    foreach ($fdFields as $index => $field) {
                        if (gettype($index) == 'integer') {
                            // Just field names. Find key of fieldname in row.
                            // eg. ['id', 'name'] aka [0 => 'id', 1 => 'name']
                            if ($key = array_search($field, $row)) {
                                $flds[$field] = $key;
                            }
                        } else if (gettype($field) == 'integer') {
                            // target => index. Keep key and field.
                            // eg. ['id' => 2, 'name' => 5]
                            $flds[$index] = $field;
                        } else {
                            // target => source fields. Find field in row and assign that to index
                            // ['id' => 'some_feed_id_field', 'name' => 'some_feed_name_field']
                            if ($key = array_search($field, $row)) {
                                $flds[$index] = $key;
                            }
                        }
                    }

                    if (count($fdFields) != count($flds)) {
                        // missing fields
                        throw new \RuntimeException('Failed to get fields from first row.');
                    }
                }
                $this->feedDefinition->setFields($flds);
                $this->rowCount++;
            } else {
                // either not first row, or first row does not contain field headers
                $fields = [];
                foreach ($this->feedDefinition->getFields() as $fieldKey => $fieldPos) {
                    if (isset($row[$fieldPos])) {
                        $fields[$fieldKey] = $row[$fieldPos];
                    }
                }

                // only call callback if there's at least one field present
                if (count($fields)) {
                    $this->rowCount++;
                    call_user_func($this->callback, $fields);
                }
            }
        }
        fclose($handle);

        if ($pos !== 0) {
            $this->buffer = substr($this->buffer, $pos);
        }
    }
}