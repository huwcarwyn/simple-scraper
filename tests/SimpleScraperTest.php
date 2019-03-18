<?php 

namespace Ramonztro\SimpleScraper;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\HandlerStack as GuzzleHandlerStack;
use GuzzleHttp\Handler\MockHandler as GuzzleMockHandler;

class SimpleScraperTest extends TestCase
{
    public function testCreationBadUrl()
    {
        $this->expectException(ConnectException::class);

        $ss = new SimpleScraper();

        $ss->getData('blablabla');
    }

    public function testTitleTagRetrieval()
    {
        $ss = new SimpleScraper();

        $ss->setClient($this->createMockedGuzzleClient([
            new GuzzleResponse(200, [], $this->getMockedResponse()),
        ]));

        $data = $ss->getData('https://github.com');
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Mocked response');
    }

    public function testMetaDataRetrieval()
    {
        $ss = new SimpleScraper();

        $ss->setClient($this->createMockedGuzzleClient([
            new GuzzleResponse(200, [], $this->getMockedResponse()),
        ]));

        $data = $ss->getData('https://github.com');

        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('og', $data);
        $this->assertArrayHasKey('twitter', $data);
        $this->assertEquals(count($data['meta']), 1);
        $this->assertEquals(count($data['og']), 6);
        $this->assertEquals(count($data['twitter']), 4);
    }

    protected function createMockedGuzzleClient(array $responses) : Client
    {
        return new Client([
            'handler' => GuzzleHandlerStack::create(
                new GuzzleMockHandler($responses)
            ),
        ]);
    }

    protected function getMockedResponse() : string
    {
        return <<<HTML
            <html>
                <head>
                    <title>Mocked response</title>
                    <meta name="description" content="Description">
                    <meta property="og:title" content="Open graph title">
                    <meta property="og:description" content="Open Graph description">
                    <meta property="og:locale:alternate" content="en_GB">
                    <meta property="og:rich_attachment" content="true">
                    <meta property="og:image" content="http://example.com/image.jpg">
                    <meta property="og:image:secure_url" content="https://example.com/image.jpg">
                    <meta name="twitter:card" content="summary">
                    <meta name="twitter:title" content="This is a Twitter title">
                    <meta name="twitter:description" content="This is a Twitter description">
                    <meta name="twitter:site" content="@twitter">
                </head>
                <body></body>
            </html>
HTML;
    }

}