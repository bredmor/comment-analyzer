<?php declare(strict_types=1);
namespace Tests\Feature;

use Tests\TestCase;
use bredmor\CommentAnalyzer\Comment;
use bredmor\CommentAnalyzer\Analyzer;
use bredmor\CommentAnalyzer\SummaryScore;

final class BasicFeatureTest extends TestCase {

    private Analyzer $api;

    public function setUp(): void
    {
        $this->api = new Analyzer($_ENV['PERSPECTIVE_API_KEY']);
    }

    public function testApiKeyWasAdded(): void
    {
        $this->assertArrayHasKey("PERSPECTIVE_API_KEY", $_ENV);
        $this->assertNotEquals("YOUR_API_KEY_HERE", $_ENV['PERSPECTIVE_API_KEY'], "Please copy phpunit.xml.dist to phpunit.xml and add your API Key to the Environment Variables!");
    }

    public function testCanAnalyzeToxicity(): void
    {
        $this->assertInstanceOf(Analyzer::class, $this->api);

        $this->api->addAttributeModel(Analyzer::MODEL_TOXICITY);

        $comment = new Comment('Hello my good sir, how are you this fine evening?');
        $comment2 = new Comment('You suck, jerkwad.');

        $analyzeResult1 = $this->api->analyze($comment);
        $this->assertTrue($analyzeResult1, "Failed asserting that the API successfully analyzed a comment.");

        $scoreObj = $comment->getSummaryScore(Analyzer::MODEL_TOXICITY);

        $this->assertInstanceOf(SummaryScore::class, $scoreObj);

        $this->assertNotEmpty($scoreObj->value);

        $this->api->analyze($comment2);
        $scoreObj2 = $comment2->getSummaryScore(Analyzer::MODEL_TOXICITY);

        $this->assertNotEmpty($scoreObj2->value);

        $this->assertGreaterThan($scoreObj->value, $scoreObj2->value, "Failed asserting that toxic comment was scored higher than a polite comment.");
    }
}

