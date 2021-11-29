<?php declare(strict_types=1);

namespace Tests\Unit;

use bredmor\CommentAnalyzer\Analyzer;
use bredmor\CommentAnalyzer\Comment;

final class ScoreModelsTest extends \Tests\TestCase
{
    private Analyzer $api;

    public function setUp(): void
    {
        $this->api = new Analyzer($_ENV['PERSPECTIVE_API_KEY']);
    }

    public function testCanUseMultipleAttributes(): void
    {
        $this->api->addAttributeModel(Analyzer::MODEL_TOXICITY);
        $this->api->addAttributeModel(Analyzer::MODEL_INSULT);

        $comment = new Comment("You are very stupid, you idiot.");

        $this->api->analyze($comment);

        $score_toxicity = $comment->getSummaryScore(Analyzer::MODEL_TOXICITY);
        $score_insult = $comment->getSummaryScore(Analyzer::MODEL_INSULT);

        $this->assertNotNull($score_toxicity);
        $this->assertNotNull($score_insult);
        $this->assertNotSame($score_insult, $score_toxicity);
    }

    public function testCanUseExperimentalAttributes(): void
    {
        $this->api->enableModelType(Analyzer::MODELTYPE_EXPERIMENTAL);
        $this->api->addAttributeModel(Analyzer::MODEL_FLIRTATION_EXPERIMENTAL);

        $comment = new Comment("Hey sweetie, why don't you come back to my place and show me what that ear can do?");

        $this->api->analyze($comment);

        $score = $comment->getSummaryScore(Analyzer::MODEL_FLIRTATION_EXPERIMENTAL);

        $this->assertNotNull($score);
    }

}