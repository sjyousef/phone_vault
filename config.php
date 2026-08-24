<?php
/* ============================================================
   PhoneVault – Global Config & Database Link
   ============================================================ */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$pdo = getPDO();
