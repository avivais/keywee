<?php

namespace App\Action;

use Redis;
use DOMDocument;
use DOMXPath;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ScrapeAction
{
    private $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function getMD(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
	{
		$response->getBody()->write(json_encode($this->scrape($args['url-unique-id'])));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function postURL(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
	{
		$response->getBody()->write(json_encode($this->canonicalize($_GET['url'])));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function scrape($uuid)
    {
        $result = ['id' => $uuid];
        if (($canonicalURL = $this->redis->hGet($uuid, 'canonical')) !== false) {
            if (($scrape_status = $this->redis->hGet($uuid, 'scrape_status')) !== false) {
                $result['scrape_status'] = $scrape_status;
                if ($scrape_status == "Done") {
                    $result = $this->redis->hGetAll($uuid);
                }
            }
            else {
                $this->redis->hSet($uuid, 'scrape_status', "Pending");
                if (($dom = $this->getDOM($canonicalURL)) === false) {
                    $scrape_status = "Error";
                    $this->redis->hSet($uuid, 'scrape_status', $scrape_status);
                    $result['scrape_status'] = $scrape_status;
                }
                else {
                    $ogTags = $this->searchForOGTags($dom);
                    foreach ($ogTags as $key => $val) {
                        $key = substr($key, 3);
                        $this->redis->hSet($uuid, $key, $val);
                    }
                    $scrape_status = "Done";
                    $this->redis->hSet($uuid, 'scrape_status', $scrape_status);
                    $result = $this->redis->hGetAll($uuid);
                }
            }
        }
        return $result;
    }

    private function canonicalize($url)
    {
        if (($dom = $this->getDOM($url)) !== false) {
            if (($canonicalURL = $this->searchForLinkWithRelCanonical($dom)) !== false) {
                return ['id' => $this->storeURL($canonicalURL)];
            }

            $ogTags = $this->searchForOGTags($dom);
            if (isset($ogTags['og:url'])) {
                return ['id' => $this->storeURL($ogTags['og:url'])];
            }

            return ['id' => $this->storeURL($url)];
        }
        return ['id' => false];
    }

    private function getUID()
    {
        return number_format(microtime(true),4,'','');
    }

    private function getDOM($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36'
        ]);
        $page = curl_exec($ch);

        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == 200) {
                return $this->parseHTML($page);
            }
        }

        return false;
    }

    private function parseHTML($htmlString)
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlString, LIBXML_NOWARNING|LIBXML_NOERROR);
        libxml_clear_errors();
        return $dom;
    }

    private function searchForLinkWithRelCanonical($dom)
    {
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            if($link->hasAttribute('rel') && ($rel = $link->getAttribute('rel')) !== '') {
                $rels = preg_split('/\s+/', trim($rel));
            }
            if(in_array('canonical', $rels)) {
                return $link->getAttribute('href');
            }
        }
        return false;
    }

    private function searchForOGTags($dom)
    {
        $xpath = new DOMXPath($dom);
        $query = '//*/meta[starts-with(@property, \'og:\')]';
        $metatags = $xpath->query($query);
        $metas = array();
        foreach ($metatags as $meta) {
            $property = $meta->getAttribute('property');
            $content = $meta->getAttribute('content');
            $metas[$property] = $content;
        }
        return $metas;
    }

    private function storeURL($url)
    {
        if (($id = $this->redis->get($url)) !== false) {
            return $id;
        }
        $id = $this->getUID();
        $this->redis->set($url, $id);
        $this->redis->hSet($id, 'id', $id);
        $this->redis->hSet($id, 'canonical', $url);
        return $id;
    }
}
