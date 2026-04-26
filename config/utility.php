<?php

function formatReadableDate($date) {
    // Accepts string (Y-m-d, datetime, etc.)
    return date('F j, Y', strtotime($date));
}