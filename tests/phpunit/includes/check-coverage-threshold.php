<?php
/**
 * Coverage threshold checker.
 *
 * @package authorship
 */

declare( strict_types=1 );

if ( $argc < 3 ) {
	fwrite( STDERR, "Usage: php tests/phpunit/includes/check-coverage-threshold.php <clover-xml-path> <minimum-percent>\n" );
	exit( 2 );
}

$clover_file = $argv[1];
$minimum_percent = floatval( $argv[2] );

if ( ! is_file( $clover_file ) ) {
	fwrite( STDERR, sprintf( "Coverage file not found: %s\n", $clover_file ) );
	exit( 1 );
}

$coverage = simplexml_load_file( $clover_file );

if ( false === $coverage || ! isset( $coverage->project->metrics ) ) {
	fwrite( STDERR, sprintf( "Could not read coverage metrics from: %s\n", $clover_file ) );
	exit( 1 );
}

$metrics = $coverage->project->metrics;
$covered_statements = intval( (string) $metrics['coveredstatements'] );
$total_statements = intval( (string) $metrics['statements'] );

if ( $total_statements <= 0 ) {
	fwrite( STDERR, "Coverage file does not contain any statements.\n" );
	exit( 1 );
}

$statement_coverage = ( $covered_statements / $total_statements ) * 100;

// phpcs:disable HM.Security.EscapeOutput.OutputNotEscaped -- CLI output is plain text, not HTML.
printf(
	"Statement coverage: %.2f%% (%d/%d), threshold: %.2f%%\n",
	$statement_coverage,
	$covered_statements,
	$total_statements,
	$minimum_percent
);
// phpcs:enable HM.Security.EscapeOutput.OutputNotEscaped

if ( $statement_coverage < $minimum_percent ) {
	fwrite(
		STDERR,
		sprintf(
			"Coverage gate failed: %.2f%% is below %.2f%%.\n",
			$statement_coverage,
			$minimum_percent
		)
	);
	exit( 1 );
}

fwrite( STDOUT, "Coverage gate passed.\n" );
