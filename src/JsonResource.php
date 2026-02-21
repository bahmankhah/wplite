<?php

namespace WPLite;

/**
 * JsonResource — abstract API response transformer.
 *
 * Role: Transforms raw data arrays/objects into structured API response
 *       formats, similar to Laravel's API Resources.
 *
 * Responsibilities:
 *   - Wrap a single data item and define its toArray() shape.
 *   - Create collections of transformed resources.
 *   - Encode to JSON.
 *
 * How to use:
 *   - Extend this class and implement toArray():
 *     class UserResource extends JsonResource {
 *         public function toArray() {
 *             return ['id' => $this->data['id'], 'name' => $this->data['name']];
 *         }
 *     }
 *   - Single: UserResource::make($user)->toArray();
 *   - Collection: UserResource::collection($users);
 *
 * Avoid:
 *   - Do not put business logic in resources; they are for presentation only.
 *
 * @see \WPLite\Model  Data source for resources.
 */
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
