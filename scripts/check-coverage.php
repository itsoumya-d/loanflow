<?php

declare(strict_types=1);

$arguments = array_slice($argv, 1);

if (count($arguments) < 1 || count($arguments) > 3) {
    fwrite(STDERR, "Usage: php scripts/check-coverage.php <clover.xml> [min-statements] [min-methods]\n");
    exit(2);
}

$path = $arguments[0];
$minStatements = isset($arguments[1]) ? (float) $arguments[1] : 80.0;
$minMethods = isset($arguments[2]) ? (float) $arguments[2] : 60.0;

if (! is_file($path)) {
    fwrite(STDERR, "Coverage file not found: {$path}\n");
    exit(2);
}

$xml = simplexml_load_file($path);

if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Could not read Clover metrics from: {$path}\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];
$methods = (int) $metrics['methods'];
$coveredMethods = (int) $metrics['coveredmethods'];

$statementPercent = $statements > 0 ? ($coveredStatements / $statements) * 100 : 100.0;
$methodPercent = $methods > 0 ? ($coveredMethods / $methods) * 100 : 100.0;

printf(
    "Coverage: statements %d/%d (%.2f%%), methods %d/%d (%.2f%%)\n",
    $coveredStatements,
    $statements,
    $statementPercent,
    $coveredMethods,
    $methods,
    $methodPercent,
);
printf("Floor: statements >= %.2f%%, methods >= %.2f%%\n", $minStatements, $minMethods);

$failures = [];

if ($statementPercent < $minStatements) {
    $failures[] = sprintf(
        'statements %.2f%% below %.2f%%',
        $statementPercent,
        $minStatements,
    );
}

if ($methodPercent < $minMethods) {
    $failures[] = sprintf(
        'methods %.2f%% below %.2f%%',
        $methodPercent,
        $minMethods,
    );
}

if ($failures !== []) {
    fwrite(STDERR, 'FAIL: '.implode('; ', $failures)."\n");
    exit(1);
}

echo "PASS\n";
exit(0);
