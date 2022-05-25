<?php

namespace App\Utils;

class EntityCommon
{

    public function mapFromArray(array $array)
    {
        foreach ($array as $key => $value) {
            $action = 'set' . ucfirst($key);
            if (is_callable([$this, $action])) {
                $this->$action($value);
            }
        }
    }

    public function toArray()
    {
        $data = [];
        foreach ($this as $key => $value) {
            $data[$key] = $value;
        }
        return $data;
    }
}
