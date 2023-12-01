<?php

use Classes\Database;

require_once __DIR__ . '/../vendor/autoload.php';

$pdo = Database::getInstance();

$count = 100; /* Количество купонов */
$denomination = 200; /* Номинал купона */

for ($i = 0; $i < $count; $i++) {
    $pdo->query("INSERT INTO [FNLBilling].[dbo].[coupons] (coupon, denomination) VALUES (REPLACE(NEWID(), '-', ''), $denomination)");
}

echo "$count codes with denomination $denomination successfully generated!";
