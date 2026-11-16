<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\RfidCard;
$cards = RfidCard::all();
foreach ($cards as $c) {
    echo "ID {$c->id} UID {$c->uid} NAME {$c->name} ACTIVE {$c->active}\n";
}
?>
