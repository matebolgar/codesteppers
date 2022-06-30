<?php

namespace CodeSteppers\Generated\Route;

use CodeSteppers\Generated\ValidationError;

class Error
{
    public static function validateQueryParams(array $query, array $fields)
    {
        $errors = array_filter($fields, function ($fieldName) use ($query) {
            return !isset($query[$fieldName]) || !is_numeric($query[$fieldName]);
        });

        if (count($errors) === 0) {
            return;
        }

        $err = new ValidationError();
        $fields= array_values(array_map(function ($fieldName) {
            return [
                "reason" => "$fieldName is required",
                "message" => "Required",
                "locationType" => "query",
                "location" => "global",
            ];
        }, $errors));

        $err->addErrors($fields);

        throw $err;
    }
}

