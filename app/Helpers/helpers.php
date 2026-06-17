<?php

if (! function_exists('getDocumentUrl')) {
    function getDocumentUrl($path)
    {
        return $path ? asset('storage/' . $path) : null;
    }
}
