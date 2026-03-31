<?php
$files = [
    'admin/index.php',
    'admin/login.php',
    'auth/login.php',
    'auth/signup.php',
    'contact.php',
    'dashboard/index.php'
];

$tokenHtml = '<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">';

foreach ($files as $f) {
    if (!file_exists($f)) {
        echo "File not found: $f\n";
        continue;
    }
    
    $c = file_get_contents($f);
    
    // Make sure we haven't already injected it
    if (strpos($c, 'name="csrf_token"') === false) {
        $c = str_replace('</form>', $tokenHtml . "\n" . '</form>', $c);
        file_put_contents($f, $c);
        echo "Injected into $f\n";
    } else {
        echo "Already present in $f\n";
    }
}
