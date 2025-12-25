<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\RoleService();
$groups = $service->getPermissionsGrouped();

foreach ($groups as $key => $group) {
    echo "Group: " . $group['label'] . "\n";
    foreach ($group['permissions'] as $perm) {
        if ($perm === 'lihat_semua_stock_opname') {
            echo "  -> FOUND 'lihat_semua_stock_opname' HERE!\n";
        }
    }
}
