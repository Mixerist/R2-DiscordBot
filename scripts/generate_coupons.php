<?php

use Classes\Database;

$pdo = Database::getInstance();

/*
 * Не создавайте одновременно очень много купонов чтобы не положить базу.
 */
$count = 100; /* Количество купонов */
$denomination = 200; /* Номинал купона */

for ($i = 0; $i < $count; $i++) {
    $pdo->query("INSERT INTO [FNLBilling].[dbo].[coupons] (coupon, denomination) VALUES (REPLACE(NEWID(), '-', ''), $denomination)");
}

echo "$count codes successfully generated.";
