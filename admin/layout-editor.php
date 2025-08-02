<?php
// admin/layout-editor.php - Omdirigerer til den nye band-editor.php

// Hent eventuelle URL-parametre
$page = isset($_GET['page']) ? $_GET['page'] : 'forside';
$queryString = $_SERVER['QUERY_STRING'];

// Omdiriger til band-editor.php med samme parametre
header('Location: band-editor.php' . ($queryString ? '?' . $queryString : '?page=' . urlencode($page)));
exit;
?>
