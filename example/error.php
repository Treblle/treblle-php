<?php

require_once __DIR__.'/bootstrap.php';

echo '{"a": 1}';

trigger_error('Some error');
