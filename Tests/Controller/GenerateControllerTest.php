<?php
namespace Werkspot\Bundle\SitemapBundle\Tests\Controller;

use Mockery;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Werkspot\Bundle\SitemapBundle\Service\Generator;
use Werkspot\Bundle\SitemapBundle\Sitemap\SitemapIndex;
use Werkspot\Bundle\SitemapBundle\Sitemap\SitemapSection;
use Werkspot\Bundle\SitemapBundle\Sitemap\SitemapSectionPage;
use Werkspot\Bundle\SitemapBundle\Sitemap\Url;

class GenerateControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = $this->getClient();
        $url = $client->getContainer()->get('router')->generate(
            'werkspot_sitemap_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $mockPageCount = 20;

        $mockSection = Mockery::mock(SitemapSection::class);
        $mockSection->shouldReceive('getName')->andReturn('MockSection');
        $mockSection->shouldReceive('getPageCount')->andReturn($mockPageCount);

        $mockSections = [
            $mockSection
        ];

        $mockSitemapIndex = Mockery::mock(SitemapIndex::class);
        $mockSitemapIndex->shouldReceive('getSections')->andReturn($mockSections);

        $mockGenerator = Mockery::mock(Generator::class);
        $mockGenerator->shouldReceive('generateIndex')->andReturn($mockSitemapIndex);

        $client->getContainer()->set('werkspot.sitemap.generator', $mockGenerator);
        $client->request('GET', $url);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $xml = simplexml_load_string($client->getResponse()->getContent());

        $this->assertEquals($mockPageCount, count($xml->sitemap));
        $this->assertGreaterThan(0, $client->getResponse()->getTtl());
    }

    public function testSectionAction()
    {
        $mockPage = 20;
        $mockSectionName = 'test';
        $mockUrlCount = 123;

        $client = $this->getClient();
        $url = $client->getContainer()->get('router')->generate(
            'werkspot_sitemap_section_page',
            [
                'section' => $mockSectionName,
                'page'    => $mockPage
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $mockUrls = [];

        for ($i = 1; $i <= $mockUrlCount; $i++) {
            $mockUrl = Mockery::mock(Url::class);
            $mockUrl->shouldIgnoreMissing();
            $mockUrls[] = $mockUrl;
        }

        $mockSectionPage = Mockery::mock(SitemapSectionPage::class);
        $mockSectionPage->shouldReceive('getUrls')->andReturn($mockUrls);
        $mockSectionPage->shouldReceive('getCount')->andReturn($mockUrlCount);

        $mockGenerator = Mockery::mock(Generator::class);
        $mockGenerator->shouldReceive('generateSectionPage')->andReturn($mockSectionPage);

        $client->getContainer()->set('werkspot.sitemap.generator', $mockGenerator);
        $client->request('GET', $url);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $xml = simplexml_load_string($client->getResponse()->getContent());

        $this->assertEquals($mockUrlCount, count($xml->url));
        $this->assertGreaterThan(0, $client->getResponse()->getTtl());
    }

    public function testSectionActionOutOfRange()
    {
        $mockPage = 20;
        $mockSectionName = 'test';

        $client = $this->getClient();
        $url = $client->getContainer()->get('router')->generate(
            'werkspot_sitemap_section_page',
            [
                'section' => $mockSectionName,
                'page'    => $mockPage
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $mockSectionPage = Mockery::mock(SitemapSectionPage::class);
        $mockSectionPage->shouldReceive('getCount')->andReturn(0);

        $mockGenerator = Mockery::mock(Generator::class);
        $mockGenerator->shouldReceive('generateSectionPage')->andReturn($mockSectionPage);

        $client->getContainer()->set('werkspot.sitemap.generator', $mockGenerator);
        $client->request('GET', $url);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return static::createClient();
    }
}