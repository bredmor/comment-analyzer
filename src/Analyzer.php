<?php
namespace bredmor\CommentAnalyzer;

use bredmor\CommentAnalyzer\Exception\AnalyzerException;
use bredmor\CommentAnalyzer\Exception\CommentException;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Analyzer {
    private string $api_key;
    private ?LoggerInterface $logger;
    private ClientInterface|Client $client;
    private bool $experimental = false;
    private bool $nyt = false;
    private array $attribute_models = [];
    private array $languages = []; // By default, the API will try to auto-detect the language used in each comment

    const API_URL = 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze';

    /**
     * Model Information: https://developers.perspectiveapi.com/s/about-the-api-attributes-and-languages
     */
    const MODEL_TOXICITY        = 'TOXICITY';
    const MODEL_SEVERE_TOXICITY = 'SEVERE_TOXICITY';
    const MODEL_IDENTITY_ATTACK = 'IDENTITY_ATTACK';
    const MODEL_INSULT          = 'INSULT';
    const MODEL_PROFANITY       = 'PROFANITY';
    const MODEL_THREAT          = 'THREAT';
    const MODELS = [
        'TOXICITY',
        'SEVERE_TOXICITY',
        'IDENTITY_ATTACK',
        'INSULT',
        'PROFANITY',
        'THREAT'
    ];

    const MODELTYPE_EXPERIMENTAL    = 'experimental';
    const MODELTYPE_NYT             = 'nyt';
    const OPTIONAL_MODEL_TYPES = [
        self::MODELTYPE_EXPERIMENTAL,
        self::MODELTYPE_NYT
    ];

    const MODEL_TOXICITY_EXPERIMENTAL           = 'TOXICITY_EXPERIMENTAL';
    const MODEL_SEVERE_TOXICITY_EXPERIMENTAL    = 'SEVERE_TOXICITY_EXPERIMENTAL';
    const MODEL_IDENTITY_ATTACK_EXPERIMENTAL    = 'IDENTITY_ATTACK_EXPERIMENTAL';
    const MODEL_INSULT_EXPERIMENTAL             = 'INSULT_EXPERIMENTAL';
    const MODEL_PROFANITY_EXPERIMENTAL          = 'PROFANITY_EXPERIMENTAL';
    const MODEL_THREAT_EXPERIMENTAL             = 'THREAT_EXPERIMENTAL';
    const MODEL_SEXUALLY_EXPLICIT_EXPERIMENTAL  = 'SEXUALLY_EXPLICIT';
    const MODEL_FLIRTATION_EXPERIMENTAL         = 'FLIRTATION';
    const EXPERIMENTAL_MODELS = [
        'TOXICITY_EXPERIMENTAL',
        'SEVERE_TOXICITY_EXPERIMENTAL',
        'IDENTITY_ATTACK_EXPERIMENTAL',
        'INSULT_EXPERIMENTAL',
        'PROFANITY_EXPERIMENTAL',
        'THREAT_EXPERIMENTAL',
        'SEXUALLY_EXPLICIT',
        'FLIRTATION',
    ];

    const MODEL_NYT_ATTACK_ON_AUTHOR        = 'ATTACK_ON_AUTHOR';
    const MODEL_NYT_ATTACK_ON_COMMENTER     = 'ATTACK_ON_COMMENTER';
    const MODEL_NYT_INCOHERENT              = 'INCOHERENT';
    const MODEL_NYT_INFLAMMATORY            = 'INFLAMMATORY';
    const MODEL_NYT_LIKELY_TO_REJECT        = 'LIKELY_TO_REJECT';
    const MODEL_NYT_OBSCENE                 = 'OBSCENE';
    const MODEL_NYT_SPAM                    = 'SPAM';
    const MODEL_NYT_UNSUBSTANTIAL           = 'UNSUBSTANTIAL';
    const NYT_MODELS = [
        'ATTACK_ON_AUTHOR',
        'ATTACK_ON_COMMENTER',
        'INCOHERENT',
        'INFLAMMATORY',
        'LIKELY_TO_REJECT',
        'OBSCENE',
        'SPAM',
        'UNSUBSTANTIAL'
    ];

    public function __construct($api_key, LoggerInterface $logger = null, ClientInterface $client = null) {
        $this->api_key = $api_key;
        $this->logger = $logger;

        if($client) {
            $this->client = $client;
        } else {
            $this->client = new Client();
        }
    }

    /**
     * Add support for a comment language
     * @param $language - ISO 631-1 two-letter language code
     */
    public function addLanguage(String $language): void {
        $this->languages[$language] = null;
    }

    /**
     * Remove support for a comment language
     * @param String $language - ISO 631-1 two-letter language code
     */
    public function removeLanguage(String $language): void {
        if(in_array($language, $this->languages)) {
            unset($this->languages[$language]);
        }
    }

    /**
     * Get array of models for a given model type
     * @param string $type
     * @return string[]
     */
    private function getModelsForType(string $type): array
    {
        return match($type) {
            'experimental'  => self::EXPERIMENTAL_MODELS,
            'nyt'           => self::NYT_MODELS
        };
    }

    /**
     * Enables the use of optional model types, such as experimental scoring models
     * WARNING: These models are not as robustly trained as the supported models and should be used with caution.
     * @throws AnalyzerException
     * @param ...$types
     */
    public function enableModelType(...$types): void {
        foreach($types as $type) {
            if(!in_array($type, self::OPTIONAL_MODEL_TYPES)) {
                throw new AnalyzerException(sprintf('Trying to enable an unsupported model type: "%s".', $type));
            }

            $this->{$type} = true;
        }
    }

    /**
     * Disables the use of experimental scoring models and removes any enabled experimental scoring models currently
     * being used by the API instance.
     * @throws AnalyzerException
     */
    public function disableModelType(...$types): void {
        foreach($types as $type) {
            if(!in_array($type, self::OPTIONAL_MODEL_TYPES)) {
                throw new AnalyzerException(sprintf('Trying to disable an unsupported model type: "%s".', $type));
            }

            $this->{$type} = false;

            foreach($this->getModelsForType($type) as $model) {
                if(in_array($model, $this->attribute_models)) {
                    unset($this->attribute_models[$model]);
                }
            }
        }
    }

    /**
     * Adds the given attribute model to the list of those comments will be scored by
     * @param $model
     * @throws AnalyzerException
     */
    public function addAttributeModel($model): void {
        $enabled_models = static::MODELS;

        foreach(self::OPTIONAL_MODEL_TYPES as $model_type) {
            if($this->{$model_type} === true) {
                $enabled_models = array_merge($enabled_models, $this->getModelsForType($model_type));
            }
        }

        if(!in_array($model, $enabled_models)) {
            throw new AnalyzerException(sprintf('Trying to enable an unsupported model: "%s".', $model));
        }

        $this->attribute_models[$model] = null;
    }


    /**
     * Removes the given attribute model from scoring of future comments
     * @param $model
     * @throws AnalyzerException
     */
    public function removeAttributeModel($model): void {
        $models = static::MODELS;

        foreach(self::OPTIONAL_MODEL_TYPES as $model_type) {
            $models = array_merge($models, $model_type);
        }

        if(!in_array($model, $models)) {
            throw new AnalyzerException(sprintf('Trying to remove an unsupported model: "%s".', $model));
        }

        if(in_array($model, $this->attribute_models)) {
            unset($this->attribute_models[$model]);
        }
    }

    /**
     * Analyzes a given comment and fills out its scoring data
     * @param Comment $comment
     * @return bool
     * @throws AnalyzerException|CommentException
     */
    public function analyze(Comment &$comment): bool {
        if(empty($this->attribute_models)) throw new AnalyzerException('Trying to analyze a comment with no attribute models enabled.');
        $comment->setState(Comment::STATE_SUBMITTED);
        $requestData = $this->buildApiData($comment);
        $result = $this->doApiCall($requestData);
        $comment->setAnalysis($result);
        return true;
    }

    private function buildApiData(Comment $comment): array {
        $api_data = [];
        $api_data['comment'] = ['text' => $comment->getText()];

        if(!empty($this->languages)) {
            $api_data['languages'] = $this->languages;
        }

        $api_data['requestedAttributes'] = [];
        foreach($this->attribute_models as $attribute => $null) {
            $config = new \stdClass(); //TODO: support config for model attributes (Is this even a thing in the production version of Perspective?)
            $api_data['requestedAttributes'][$attribute] = $config;
        }

        return $api_data;
    }

    /**
     * @throws AnalyzerException
     */
    private function doApiCall($request_data): string {
        try {
            $response = $this->client->post(static::API_URL . '?key=' . $this->api_key, [
                RequestOptions::JSON => $request_data
            ]);
        } catch(\Throwable $e) {
            $this->logger?->critical(sprintf('Call to Perspective API Failed: %s', $e->getMessage()));
            throw new AnalyzerException(sprintf('Call to Perspective API Failed: %s', $e->getMessage()));   
        }

        if($response->getStatusCode() != 200) {
            $this->logger?->critical(sprintf('Call to Perspective API Failed with status code %s. Response: %s', $response->getStatusCode(), $response->getBody()));
            throw new AnalyzerException(sprintf('Call to Perspective API Failed: HTTP %s', $response->getStatusCode()));
        }

        return $response->getBody();
    }


}
