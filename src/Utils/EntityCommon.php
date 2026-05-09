<?php

namespace App\Utils;

class EntityCommon
{

    public function mapFromArray(array $array)
    {
        foreach ($array as $key => $value) {
            // Try to use setter first
            $action = 'set' . ucfirst($key);
            if (is_callable([$this, $action])) {
                $this->$action($value);
            }
            // Otherwise, try to set property directly if it exists and is public
            elseif (property_exists($this, $key)) {
                $this->$key = $value;
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
