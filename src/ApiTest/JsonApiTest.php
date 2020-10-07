<?php
declare(strict_types=1);

/**
 * API JSON test.
 *
 * @package   API JSON
 * @author    Stoil Dobreff
 * @copyright Copyright © 2020
 */

/**
 * Matcher patterns:
 *
Available patterns

    @string@
    @integer@
    @number@
    @double@
    @boolean@
    @array@
    @...@ - unbounded array
    @null@
    @*@ || @wildcard@
    expr(expression) - optional, requires symfony/expression-language: ^2.3|^3.0|^4.0|^5.0 to be present
    @uuid@
    @json@
    @string@||@integer@ - string OR integer

Available pattern expanders

    startsWith($stringBeginning, $ignoreCase = false)
    endsWith($stringEnding, $ignoreCase = false)
    contains($string, $ignoreCase = false)
    notContains($string, $ignoreCase = false)
    isDateTime()
    isEmail()
    isUrl()
    isIp()
    isEmpty()
    isNotEmpty()
    lowerThan($boundry)
    greaterThan($boundry)
    inArray($value)
    hasProperty($propertyName) - example "@json@.hasProperty(\"property_name\")"
    oneOf(...$expanders) - example "@string@.oneOf(contains('foo'), contains('bar'), contains('baz'))"
    matchRegex($regex) - example "@string@.matchRegex('/^lorem.+/')"
    optional() - work's only with ArrayMatcher, JsonMatcher and XmlMatcher
    count() - work's only with ArrayMatcher - example "@array@.count(5)"
    repeat($pattern, $isStrict = true) - example '@array@.repeat({"name": "foe"})' or "@array@.repeat('@string@')"
    match($pattern) - example {"image":"@json@.match({\"url\":\"@string@.isUrl()\"})"}

Good JSON example

Actual response
{
    "users":[
      {
        "firstName": "Norbert",
        "lastName": "Orzechowicz",
        "created": "2014-01-01",
        "roles":["ROLE_USER", "ROLE_DEVELOPER"],
        "attributes": {
          "isAdmin": false,
          "dateOfBirth": null,
          "hasEmailVerified": true
        },
        "avatar": {
          "url": "http://avatar-image.com/avatar.png"
        }
      },
      {
        "firstName": "Michał",
        "lastName": "Dąbrowski",
        "created": "2014-01-01",
        "roles":["ROLE_USER", "ROLE_DEVELOPER", "ROLE_ADMIN"],
        "attributes": {
          "isAdmin": true,
          "dateOfBirth": null,
          "hasEmailVerified": true
        },
        "avatar": null
      }
    ]
}
Pattern to test against
{
    "users":[
      {
        "firstName": "@string@",
        "lastName": "@string@",
        "created": "@string@.isDateTime()",
        "roles": [
            "ROLE_USER",
            "@...@"
        ],
        "attributes": {
          "isAdmin": @boolean@,
          "@*@": "@*@"
        },
        "avatar": "@json@.match({\"url\":\"@string@.isUrl()\"})"
      }
      ,
      @...@
    ]
}
 */

namespace JsonResponseTest\APITest;

use GuzzleHttp\Client;
use Coduo\PHPMatcher\PHPMatcher;
use Webmozart\Assert\Assert;
use JsonResponseTest\APITest\JSONBacktrace;

class JsonApiTest extends \PHPUnit\Framework\TestCase {

    private $client;
    private $responseDir;
    private $matcher;
    private $baseUrl;

    /**
      * Setter for the expected responses directory
      *
      * @param string $responseDir
      *
      * @return void
      */
    public function setResponseDir( string $responseDir ): void {

        $this->responseDir = $responseDir;

    }

    /**
     * Sets the base URL
     *
     * @param string $url
     *
     * @return void
     */
    public function setBaseUrl( string $url ): void {
        $this->baseUrl = $url;
    }

    /**
     * Makes the actual request and returns the response
     *
     * @param string $url
     * @param string|string $method
     * @param array|array $params
     * @param array|array $headers
     *
     * @return ResponseInterface
     */
    public function request(
        string $url,
        string $method = 'GET',
        array $params = [],
        array $headers = []
    ) {

        $extra = [ 'verify' => false ]; # suppress SSL verifications - local testing
        if ( ! empty( $params ) ) {
            if ( 'GET' == $method ) {
                $extra['query'] = $params;
            } else {
                $extra['form_params'] = $params;
            }
        }

        if ( ! empty( $headers ) ) {
            $extra = array_merge( $extra, $headers );
        }

        $response = $this->client->request(
            $method,
            $this->baseUrl . $url,
            $extra
        );

        return $response;
    }

    /**
     * Makes assertions for the response
     *
     * @param type $response
     * @param string $filename
     * @param int|int $statusCode
     *
     * @return void
     */
    protected function assertResponse( $response, string $filename, int $statusCode = 200 ): void {
        self::assertEquals( $statusCode, $response->getStatusCode() );

        $this->assertJsonHeader( $response );
        $this->assertJsonResponseContent( $response, $filename );
    }

    /**
     * Checks if the response has proper JSON header set
     *
     * @param type $response
     *
     * @return void
     */
    protected function assertJsonHeader( $response ) {
        $this->assertHeader( $response, 'Content-Type', 'application/json' );
    }

    /**
     * Checks header against given value
     *
     * @param type $response
     * @param string $header
     * @param string $content
     *
     * @return void
     */
    protected function assertHeader( $response, string $header, string $content ) {
        $headerContent = $response->getHeaders()[ $header ][0];
        Assert::string( $headerContent );

        self::assertStringContainsString(
            $content,
            $headerContent
        );
    }

    /**
     * Checks the actual JSON response against the given pattern
     *
     * There must be file provided with the JSON pattern, then method is
     * testing the response against that pattern.
     * Unfortunately there is no easy way to show the exact not matching problem
     * which is pain for large responses, but at least you can test it in general :)
     *
     * @param type $response
     * @param string $filename
     *
     * @return type
     */
    protected function assertJsonResponseContent( $response, string $filename ): void {
        $contents = file_get_contents( $this->responseDir . $filename );
        Assert::string( $contents );

        $expectedResponse = trim( $contents );

        $matcher = $this->getMatcher();
        $actualResponse = trim( $response->getBody()->getContents() );

        $result = $matcher->match( $actualResponse, $expectedResponse );

        if ( ! $result ) {
            self::fail(
                'JSON pattern does not match provided response' . "\n" .
                'Check first that you properly set the @...@ wild card (or if you set it at all)' . "\n" .
                'Also you could try https://php-matcher.norbert.tech/ for some live testing' . "\n" . (string) $matcher->backtrace()
            );
        }
    }

    /**
     * Creates matcher instance
     *
     * @return PHPMatcher
     */
    protected function getMatcher(): PHPMatcher {
        if ( null == $this->matcher ) {
            $this->matcher = new PHPMatcher( new JSONBacktrace() );
        }
        return $this->matcher;
    }

    /**
     * Returns the Guzzle Client
     *
     * @return Client
     */
    public function getClient(): Client {
        return $this->client;
    }

    /**
     * You can set you own Client and assign it whatever options work for you
     * environment.
     * Otherwise it will just create one for you with defaults
     *
     * @param Client|null $client
     *
     * @return type
     */
    public function setClient( Client $client = null ): Client {
        if ( null != $client ) {
            $this->client = $client;
        } else {
            $this->client = new Client(
                [
                    'allow_redirects' => true,
                    'cookies' => true,
                    'http_errors' => false,
                ]
            );
        }
        return $this->client;
    }
}
