<?php

return [
    /*
     * Status default if the model doesn't have or set any status
     */
    'default_statuses' => ['active', 'inactive'],

    /*
     * If true, the status history will be recorded
     */
    'record_history' => true,

    /*
     * If true, the package will dispatch events on status transition
     */
    'dispatch_events' => false,
];