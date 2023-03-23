<?php

declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';

echo '{"a": 1}';

trigger_error('Some error');
