<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \Illuminate\Support\Facades\Mail::raw('test', function($msg) {
        $msg->to('diarrass1507@gmail.com')->subject('test');
    });
    echo 'SUCCESS_SEND_EMAIL';
} catch(\Exception $e) {
    echo 'ERROR_SEND_EMAIL: ' . $e->getMessage();
}
