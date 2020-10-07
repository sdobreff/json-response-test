# json-response-test

JsonApiTest is a PHPUnit TestCase that will make your life as a PHP API developer much easier.

Thanks to [PHP-Matcher](https://github.com/coduo/php-matcher) you can, test you API JSON responses against provided schema.

## Sandbox

You can use [PHP-Matcher Sandbox](https://php-matcher.norbert.tech/), to create your JSON schema.

## Installation

Assuming you already have Composer installed globally:
```
$ composer require sdobreff/json-response-test
```
## Usage
With this in place, any string under key message will match the pattern. More complicated expected response could look like this:
```
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
```
And will match the following list of products:
```
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
```

## Available patterns

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
    
## Available pattern expanders

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
    
## Example usage
Your test class
```php
<?php
declare(strict_types=1);

class LocationTest extends BaseApiTest {

    public function testGetLocation() {
 
        $addHead = [
            'headers' => [
                'Authorization' => 'Bearer some-key',
            ],
        ];
        
        $response = $this->request(
            '/api/v1/search',
            'POST',
            [
                'q' => 'sp',
                'filters' => '{}',
            ],
            $addHead
        );

        $this->assertResponse( $response, 'location/tst.json' );
    }
}

```
BaseApiTest for initializing the properties
```php
<?php
declare(strict_types=1);

use JsonResponseTest\APITest\JsonApiTest;

class BaseApiTest extends JsonApiTest {

    public function setUp() {
        $dir = __DIR__ . '/responses/';

        parent::setResponseDir( $dir );
        parent::setClient();
        parent::setBaseUrl( 'https://local-test.com' );
    }
}

```

