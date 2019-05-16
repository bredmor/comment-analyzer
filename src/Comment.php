<?php
namespace bredmor\CommentAnalyzer;
use bredmor\CommentAnalyzer\Exception\CommentException;

class Comment {
    private $content;
    private $state = Comment::STATE_CREATED;
    private $summary_scores;
    private $span_scores;

    /**
     * Comment state
     */
    const STATE_CREATED = 0;
    const STATE_SUBMITTED = 1;
    const STATE_ANALYZED = 2;
    const STATE = [
        0 => 'STATE_CREATED',
        1 => 'STATE_SUBMITTED',
        2 => 'STATE_ANALYZED'
    ];

    public function __construct(String $content) {
        $this->content = $content;
        $this->state = static::STATE_CREATED;
    }

    /**
     * Retrieve the comment text
     * @return String
     */
    public function getText(): String {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getState(): int {
        return $this->state;
    }

    /**
     * @param $model
     * @return SummaryScore|null
     * @throws CommentException
     */
    public function getSummaryScore($model): ?SummaryScore {
        if($this->state !== static::STATE_ANALYZED) throw new CommentException('Trying to get summary score from a comment that has not been analyzed.');
        if(!in_array($model, Analyzer::MODELS)) throw new CommentException(sprintf('Attribute model %s not found in library.', $model));
        if(!array_key_exists($model, $this->summary_scores)) return null;

        return $this->summary_scores[$model];
    }

    /**
     * @param $model
     * @return SummaryScore|null
     * @throws CommentException
     */
    public function getSpanScores($model): ?SpanScore {
        if($this->state !== static::STATE_ANALYZED) throw new CommentException('Trying to get span score from a comment that has not been analyzed.');
        if(!in_array($model, array_merge(Analyzer::MODELS, Analyzer::EXPERIMENTAL_MODELS))) throw new CommentException(sprintf('Attribute model %s not found in library.', $model));
        if(!array_key_exists($model, $this->summary_scores)) return null;

        return $this->span_scores[$model];
    }

    /**
     * @param Int $state
     * @throws CommentException
     */
    public function setState(Int $state): void {
        if($this->state === static::STATE_ANALYZED) {
            throw new CommentException('Trying to change state of a comment that has already been analyzed.');
        }
        if(!array_key_exists($state, static::STATE)) {
            throw new CommentException('Trying to set invalid state %s on a comment.');
        }

        $this->state = $state;
    }

    /**
     * @param $scores
     * @throws CommentException
     */
    public function setAnalysis($scores): void {
        if($this->state !== static::STATE_SUBMITTED) {
            throw new CommentException('Trying to set analysis of a comment out of flow.');
        }

        $score_data = json_decode($scores, true);
        if(!array_key_exists('attributeScores', $score_data)) throw new CommentException('Received malformed score data from Perspective API');
        foreach($score_data['attributeScores'] as $attribute => $data) {
            $summaryScore = new SummaryScore($data['summaryScore']['value'], $data['summaryScore']['type']);
            $this->summary_scores[$attribute] = $summaryScore;

            if(array_key_exists('spanScores', $data)) {
                foreach($data['spanScores'] as $spanData) {
                    $spanScore = new SpanScore($spanData['score']['value'], $spanData['score']['type'], $spanData['begin'], $spanData['end']);
                    $this->span_scores[$attribute] = $spanScore;
                }
            }
        }

        $this->setState(static::STATE_ANALYZED);
    }

}