<?php

namespace WPLite;

abstract class JsonResource
{
    protected $data;

    public function __construct($data)
    {
        $this->data = is_array($data) ? $data : (array) $data;
    }

    public static function make($data)
    {
        return new static($data);
    }

    public static function collection($items)
    {
        return array_map(function ($item) {
            return new static($item);
        }, $items);
    }

    abstract public function toArray();

    public function json()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
