<?php

declare(strict_types=1);

namespace JsonResponseTest\APITest;

use Coduo\PHPMatcher\Backtrace;
use Coduo\PHPMatcher\Value\SingleLineString;
use Coduo\ToString\StringConverter;

final class JSONBacktrace implements Backtrace {

    /**
     * @var mixed[]
     */
    private $trace;

    /**
     * Check for failures flag
     */
    private $checkFail = false;

    public function __construct() {
         $this->trace = [];
    }

    public function __toString() : string {
        return \implode( "\n", $this->trace );
    }

    public function matcherCanMatch( string $name, $value, bool $result ) : void {
        if ( $result ) {
            $this->checkFail = true;
        }
    }

    public function matcherEntrance( string $name, $value, $pattern ) : void {
    }

    public function matcherSucceed( string $name, $value, $pattern ) : void {
        $this->checkFail = false;
    }

    public function matcherFailed( string $name, $value, $pattern, string $error ) : void {

        if ( $this->checkFail && ! is_array( $pattern ) && is_string( $pattern ) && strlen( $pattern ) < 50 && is_string( $value ) && strlen( $value ) < 50 ) {

            $this->trace[] = \sprintf(
                '#%d Matcher %s failed to match value "%s" with "%s" pattern',
                $this->entriesCount(),
                $name,
                new SingleLineString( (string) new StringConverter( $value ) ),
                new SingleLineString( (string) new StringConverter( $pattern ) )
            );

            $this->trace[] = \sprintf(
                '#%d Matcher %s error: %s',
                $this->entriesCount(),
                $name,
                new SingleLineString( $error )
            );
        }
    }

    public function expanderEntrance( string $name, $value ) : void {
    }

    public function expanderSucceed( string $name, $value ) : void {
    }

    public function expanderFailed( string $name, $value, string $error ) : void {
        // $this->trace[] = \sprintf(
        //     '#%d Expander %s failed to match value "%s"',
        //     $this->entriesCount(),
        //     $name,
        //     new SingleLineString((string) new StringConverter($value))
        // );

        // $this->trace[] = \sprintf(
        //     '#%d Expander %s error: %s',
        //     $this->entriesCount(),
        //     $name,
        //     new SingleLineString($error)
        // );
    }

    public function isEmpty() : bool {
        return \count( $this->trace ) === 0;
    }

    public function raw() : array {
        return $this->trace;
    }

    private function entriesCount() : int {
        return \count( $this->trace ) + 1;
    }
}
