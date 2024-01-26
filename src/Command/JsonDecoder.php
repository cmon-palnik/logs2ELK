<?php

namespace Logs2ELK\Command;

class JsonDecoder
{
    public static function decode(string $json, bool $associative = true): null|array|object
    {
        $decodedData = json_decode($json, $associative);

        if (empty($decodedData) && json_last_error() !== JSON_ERROR_NONE) {
            $jsonString = static::fixJsonString($json);
            $decodedData = json_decode($jsonString, $associative);
        }

        return $decodedData;
    }

    private static function fixJsonString($jsonString) {
        return str_replace([' "', '" '], "'", $jsonString);
    }
}
