<?php

return [
    'enabled' => filter_var(env('ERROR_NOTIFICATION_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('ERROR_NOTIFICATION_EMAILS', ''))))),
];
