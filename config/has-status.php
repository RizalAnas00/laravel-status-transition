<?php
// config/has-status.php

return [
    /*
     * Status default if the model doesn't have or set any status
     */
    'default_statuses' => ['active', 'inactive'],

    /*
     * If true, the status history will be recorded
     */
    'record_history' => true,
];