<?php
/**
 * Fabio Ros - FROSIT |
 *
 *
 * @package     alfred-hypernode
 * @author      Fabio Ros <info@frosit.nl>
 * @copyright   Copyright (c) 2016 Fabio Ros - FROSIT
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 * @todo possibly, add caching to speed it up
 * @todo renew this setup to a more stable approach
 */

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Alfred\Workflows\Workflow;

require_once __DIR__.'/vendor/autoload.php';

/**
 * Class ArticlesCrawler
 */
class ArticlesCrawler
{

    const DOMAIN = 'https://support.hypernode.com/';
    public $query;

    public function __construct($query = null)
    {
     //$queryUrl = $this->buildUrl($query);
    }

    /**
     * @param $text
     */
    public function logger($text)
    {
        $file = 'dev.log';
        $objDateTime = new DateTime('NOW');
        $objDateTime->format('Y-m-d\TH:i:s.u');
        $handle = fopen($file, 'ab+');
        fwrite($handle, "\n ".$objDateTime->format('Y-m-d\TH:i:s.u').' :: '.$text);
        fclose($handle);
    }

    /**
     * Build the URL
     * @todo on empty
     * @todo make query string instead encoded
     * @param $query
     * @return string
     */
    public function buildUrl($query)
    {
        $url = self::DOMAIN;
        if (isset($query)) {
            $url .= '?s='.urlencode($query).'&post_type=knowledgebase';
        }

        return $url;
    }

    /**
     * @param $url
     * @return bool|null|string
     */
    public function getHtml($url)
    {
        $client = new Client();
        $html = null;
        try {
            $crawler = $client->request('GET', $url);
            $html = $crawler->html();
        } catch (Exception $e) {
            $this->logger($e->getMessage());
            $html = false;
        }

        return $html;
    }

    /**
     * @param null $query
     * @return Crawler
     */
    public function getArticles($query = null)
    {
        if (!isset($this->query)) {
            $this->query = $this->buildUrl($query);
        }

        $html = $this->getHtml($this->query);
        $articles = $this->getArticlesData($html);

        return $articles;
    }

    /**
     * @param $html
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getArticlesData($html)
    {
        $crawler = new \Symfony\Component\DomCrawler\Crawler();
        $crawler->addContent($html);
        $nodeValues = $crawler->filter('main a')->each(
            function (Crawler $node, $i) {
                $node = array(
                    'text' => $node->text(),
                    'link' => $node->attr('href'),
                );

                return $node;
            }
        );

        $articles = $nodeValues;
        return $articles;
    }
}

/**
 * Starting argument input here
 * @todo optimize output
 */
if (isset($query)) {

    $workflow = new Workflow();
    $articles = new ArticlesCrawler();
    $articles = $articles->getArticles($query);

    foreach ($articles as $article) {
        $result = $workflow->result()
            ->uid(str_replace(' ', '-', $article['text']))
            ->title($article['text'])
            ->subtitle($article['link'])
            ->quicklookurl($article['link'])
            ->type('default')
            ->valid(true)
            ->arg($article['link'])
            ->mod('cmd', 'Open for'.$article['link'], 'open')
            ->icon('hypernode.png')
            ->autocomplete($article['text']);
    }

    echo $workflow->output();
}
