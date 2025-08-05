<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: image/png");

// Serve image
readfile("user.png");
?>
