<?php
declare(strict_types=1);

/** Ancienne URL ; page budget intégrée (module Youssef). */
$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : '';
header('Location: budget-user.php' . $qs, true, 302);
exit;
