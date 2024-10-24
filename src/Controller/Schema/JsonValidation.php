<?php

namespace App\Controller\Schema;

use Exception;
use JsonSchema\Validator;

class JsonValidation
{

    public function isValid(array $jsonArray, string $jsonSchema)
    {
        $schema = json_decode($jsonSchema, true, 512, JSON_THROW_ON_ERROR);

        $validator = new Validator();
        $validator->validate($jsonArray, $schema);

        $error = $validator->getErrors();
        if (!empty($error)) {
            throw new Exception("JsonSchema Error: [" . $error[0]['pointer'] . '] ' . $error[0]['message']);
        }
        return $validator->isValid();
    }
}
