<?php

function formatReadableDate($date)
{
    // Accepts string (Y-m-d, datetime, etc.)
    return date('F j, Y', strtotime($date));
}

function formatReadableTime($time)
{
    // Accepts string (H:i:s, datetime, etc.)
    return date('h:i A', strtotime($time));
}

function formatLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}