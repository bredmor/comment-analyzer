<?php
namespace bredmor\CommentAnalyzer;

use bredmor\CommentAnalyzer\Exception\AnalyzerException;
use bredmor\CommentAnalyzer\Exception\CommentException;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Analyzer {
    private $api_key;
    private $logger;
    private $client;
    private $experimental = false;
    private $attribute_models = [];
    private $languages = []; // By default, the API will try to auto-detect the language used in each comment

    const API_URL = 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze';

    const MODEL_TOXICITY = 'TOXICITY';
    const MODEL_SEVERE_TOXICITY = 'SEVERE_TOXICITY';
    const MODELS = [
        'TOXICITY',
        'SEVERE_TOXICITY'
    ];

    const MODEL_IDENTITY_ATTACK = 'IDENTITY_ATTACK';
    const MODEL_INSULT = 'INSULT';
    const MODEL_PROFANITY = 'PROFANITY';
    const MODEL_THREAT = 'THREAT';
    const MODEL_SEXUALLY_EXPLICIT = 'SEXUALLY_EXPLICIT';
    const MODEL_FLIRTATION = 'FLIRTATION';
    const EXPERIMENTAL_MODELS = [
        'IDENTITY_ATTACK',
        'INSULT',
        'PROFANITY',
        'THREAT',
        'SEXUALLY_EXPLICIT',
        'FLIRTATION'
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
     * Enables the use of experimental scoring models
     * WARNING: These models are not as robustly trained as the supported models and should be used with caution.
     */
    public function enableExperimentalModels(): void {
        $this->experimental = true;
    }

    /**
     * Disables the use of experimental scoring models and removes any enabled experimental scoring models currently
     * being used by the API instance.
     */
    public function disableExperimentalModels(): void {
        $this->experimental = false;
        foreach($this->attribute_models as $model => $null) {
            if(in_array($model, static::EXPERIMENTAL_MODELS)) {
                unset($this->attribute_models[$model]);
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
        if($this->experimental === true) {
            $enabled_models = array_merge($enabled_models, static::EXPERIMENTAL_MODELS);
        }

        if(!in_array($model, $enabled_models)) {
            throw new AnalyzerException(sprintf('Trying to enable an unsupported model: "%s".', $model));
        }

        // Could throw a warning when trying to add a model that's already added but I don't see a situation in which
        // doing so would be unwanted behavior since the result would not change.
        $this->attribute_models[$model] = null;
    }


    /**
     * Removes the given attribute model from scoring of future comments
     * @param $model
     * @throws AnalyzerException
     */
    public function removeAttributeModel($model): void {
        $models = array_merge(static::MODELS, static::EXPERIMENTAL_MODELS);

        if(!in_array($model, $models)) {
            throw new AnalyzerException(sprintf('Trying to remove an unsupported model: "%s".', $model));
        }

        // Could throw a warning when trying to remove a model that's already removed but I don't see a situation in which
        // doing so would be unwanted behavior since the result would not change.
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
            $config = new \stdClass(); //todo suport config for model attributes
            $api_data['requestedAttributes'][$attribute] = $config;
        }

        return $api_data;
    }

    private function doApiCall($request_data): string {
        try {
            $response = $this->client->post(static::API_URL . '?key=' . $this->api_key, [
                RequestOptions::JSON => $request_data
            ]);
        } catch(Exception $e) {
            if($this->logger) {
                $this->logger->critical(sprintf('Call to Perspective API Failed: %s', $e->getMessage()));
            }
            throw new AnalyzerException(sprintf('Call to Perspective API Failed: %s', $e->getMessage()));   
        }

        if($response->getStatusCode() != 200) {
            if($this->logger) {
                $this->logger->critical(sprintf('Call to Perspective API Failed with status code %s. Response: %s',$response->getStatusCode() , $response->getBody()));
            }
            throw new AnalyzerException(sprintf('Call to Perspective API Failed: HTTP %s', $response->getStatusCode()));
        }

        return $response->getBody();
    }


}
