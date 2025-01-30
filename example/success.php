<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

echo \Safe\json_encode(['data' => [1, 2, 3]]);
