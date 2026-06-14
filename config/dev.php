<?php

return [
    'local_auth' => filter_var(env('LOCAL_DEV_AUTH', false), FILTER_VALIDATE_BOOL),
];
