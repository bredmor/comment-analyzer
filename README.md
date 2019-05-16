# CommentAnalyzer

CommentAnalyzer is an API Wrapper for the [Google Perspective Comment Analyzer API](https://console.developers.google.com/apis/api/commentanalyzer.googleapis.com/overview)

## Installation / Usage


### From Source:
Clone the repository from GitHub or unzip into your vendor directory. CommentAnalyzer is packaged for [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloading.

### From Composer:
`composer require bredmor/CommentAnalyzer`

### Basic Usage:

```$php
<?php
require './vendor/autoload.php';
use bredmor\CommentAnalyzer\Comment;
use bredmor\CommentAnalyzer\Analyzer;

$key = 'your_api_key';

try {
    // Instantiate API and define attribute model
    $api = new Analyzer($key);
    $api->addAttributeModel(Analyzer::MODEL_TOXICITY);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

// Build your comment from text
$comment = new Comment('Hello my good sir, how are you this fine evening?');
$comment2 = new Comment('You suck, jerkwad.');


try {
    // Analyze the comment and fetch a score for the attribute model you want
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

Please see the [Perspective API Documentation](https://github.com/conversationai/perspectiveapi/blob/master/api_reference.md) for reference on available attribute models and score meanings.

## API Support

CommentAnalyzer supports the following features of the Perspective API:

- Analysis via the TOXICITY, SEVERE_TOXICITY, IDENTITY_ATTACK, INSULT, PROFANITY, THREAT, SEXUALLY_EXPLICIT and FLIRTATION attribute models via `Analyzer::addAttributeModel()`.
- Summary Score of provided text via `Comment::getSummaryScore()`.
- Span Scores of provided text via `Comment::getSpanScore()`.
- Full language support via [ISO 631-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) two-letter codes via `Analyzer::addLanguage()`.
- Language Autodetection - languages can be explicitly declared via `Analyzer::addLanguage()`.

CommentAnalyzer **does not** yet support the following features of the Perspective API:

- Attribute Model configurations
- Conversation Context (Not yet used by the Perspective API)

## Requirements

CommentAnalyzer is tested on PHP `7.3` and later.

## Authors

- Morgan Breden  | [GitHub](https://github.com/bredmor)  | [Twitter](https://twitter.com/bredmor) | <morganbreden@gmail.com>

## Contributing

Pull requests, bug reports and feature requests are welcome.

## License

CommentAnalyzer is licensed under the GPLv3 License - see the [LICENSE](LICENSE) file for details