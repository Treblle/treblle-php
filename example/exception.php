<?php

require_once __DIR__.'/bootstrap.php';

echo \Safe\json_encode(['error' => [1, 2, 3]]);
throw new \RuntimeException('xxx');
