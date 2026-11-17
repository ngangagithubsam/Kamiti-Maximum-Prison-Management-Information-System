<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();

jsonOK(['csrf_token' => csrfToken()]);
