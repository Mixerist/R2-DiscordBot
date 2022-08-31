<?php

$config = require_once 'C:\OpenServer\domains\discordbot\config.php';

$pdo = new PDO("sqlsrv:Server={$config['server_ip']};Database={$config['database']}", $config['username'], $config['password']);

/*
 * Не создавайте одновременно очень много купонов чтобы не положить базу.
 */
$count = 100; /* Количество купонов */
$denomination = 200; /* Номинал купона */

for ($i = 0; $i < $count; $i++) {
    $pdo->query("INSERT INTO [FNLBilling].[dbo].[coupons] (coupon, denomination) VALUES (REPLACE(NEWID(), '-', ''), $denomination)");
}

echo "$count codes successfully generated.";
