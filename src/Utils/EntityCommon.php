<?php

namespace App\Utils;

class EntityCommon
{

    public function mapToArray(array $array)
    {
        foreach ($array as $key => $value) {
            if (isset($this->$key)) {
                $this->$key = $value;
            }
        }
    }
}
