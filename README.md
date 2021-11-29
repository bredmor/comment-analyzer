# CommentAnalyzer

CommentAnalyzer is an API Wrapper for the [Google Perspective Comment Analyzer API](https://www.perspectiveapi.com/#/home)

This package enables you to programmatically scan any kind of text written in supported languages for various metrics associated with negative tones and apply the resulting score, such as automatically hiding harassing or explicit comments from a blog or social media platform.

## Installation / Usage


### From Source:
Clone the repository from GitHub or unzip into your vendor directory. CommentAnalyzer is packaged for [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloading.

### From Composer:
`composer require bredmor/comment-analyzer`

### Basic Usage:

CommentAnalyzer accepts text in the form of `Comment` objects that are constructed with a single argument - the text you wish to analyze.

Instantiate the `Analyzer` object, providing it your Perspective API key and an optional [PSR-3](https://www.php-fig.org/psr/psr-3/) compliant `LoggerInterface`. Then add the attribute models you wish to score comments on by calling the instance's `addAttributeModel()` method. **Note**: You *must* provide at least one attribute model for scoring before calling the `analyze()` method or an `AnalyzerException` will be thrown.

Pass an instance of a Comment object to the `Analyzer` object's analyze method. This will start the API call process.

**Note**: If you wish to use this library in an asynchronus manner, the Comment object holds a state variable of `STATE_CREATED`, `STATE_SUBMITTED` and `STATE_ANALYZED`. You can check for the instance's current state via its `getState()` method, to ensure you aren't trying to process the same comment via multiple threads.

After successfully completing the call, the Comment object, which is passed by reference, will be filled out with `SummaryScore` and `SpanScore` objects representing the summary and span scores data returned by the API, respectively. These objects are accessed by calling the Comment object's `getSummaryScore()` or `getSpanScore()` methods with one required argument - one of the attribute models you provided to the Analyzer instance.

#### Example:
```php
<?php
require './vendor/autoload.php';
use bredmor\CommentAnalyzer\Comment;
use bredmor\CommentAnalyzer\Analyzer;

$key = 'your_api_key';

try {
    // Instantiate API and define an attribute model
    $api = new Analyzer($key);
    $api->addAttributeModel(Analyzer::MODEL_TOXICITY);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

// Build your comments from text
$comment = new Comment('Hello my good sir, how are you this fine evening?');
$comment2 = new Comment('You suck, jerkwad.');


try {
    // Analyze the comments and fetch a score for the attribute model you want
    $api->analyze($comment);
    $scoreObj = $comment->getSummaryScore(Analyzer::MODEL_TOXICITY);
    if($scoreObj) {
        echo 'Comment 1 Toxicity rating: ' . floor($scoreObj->value * 100) . '%';
    }

    $api->analyze($comment2);
    $scoreObj2 = $comment2->getSummaryScore(Analyzer::MODEL_TOXICITY);
    if($scoreObj2) {
        echo "\n" . 'Comment 2 Toxicity rating: ' . floor($scoreObj2->value * 100) . '%';
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

This example code should output something like:
```$bash
Comment 1 Toxicity rating: 2%
Comment 2 Toxicity rating: 95%
```

Other examples can be found in the [/tests](/tests) directory.

Please see the [Perspective API Documentation](https://developers.perspectiveapi.com/s/docs) for full reference on available attribute models and score meanings.

## Error Handling
Every part of the library that relies on input or proper function use will throw a `CommentException` or `AnalyzerException` as appropriate when an error is encountered.

The Analyzer object accepts an optional PSR-3 compliant `LoggerInterface`, which logs a `critical` error when the API is unreachable or responds with a non-200 HTTP error code.

## API Support

CommentAnalyzer supports the following features of the Perspective API:

- Analysis of all attribute models in the Production, Experimental and NYT categories via `Analyzer::addAttributeModel()`.
- Summary Score of provided text via `Comment::getSummaryScore()`.
- Span Scores of provided text via `Comment::getSpanScore()`.
- Full language support via [ISO 631-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) two-letter codes via `Analyzer::addLanguage()`.
- Language Autodetection - languages can be explicitly declared via `Analyzer::addLanguage()`.

## Requirements

CommentAnalyzer version `2.x` is tested on PHP `8.0` and later.

## Authors

- Morgan Breden  | [GitHub](https://github.com/bredmor)  | [Twitter](https://twitter.com/bredmor)

## Contributing

Pull requests, bug reports and feature requests are welcome.

If you add a new feature, or change an existing feature that does not yet have a test - please add one in your PR!

## Testing

Testing is handled via PHPUnit.

You can run all current tests with `composer run test`.

## License

CommentAnalyzer is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
