<?php

namespace App\Utils;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;

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
