<?php
/*
+---------------------------------------------------------------------------+
| SimpleScraper                                                             |
| Copyright (c) 2013-2016, Ramon Kayo                                       |
+---------------------------------------------------------------------------+
| Author        : Ramon Kayo                                                |
| Email         : contato@ramonkayo.com                                     |
| License       : Distributed under the MIT License                         |
| Full license  : https://github.com/ramonztro/simple-scraper               |
+---------------------------------------------------------------------------+
| "Simplicity is the ultimate sophistication." - Leonardo Da Vinci          |
+---------------------------------------------------------------------------+
*/
namespace Ramonztro\SimpleScraper;

use \Exception;
use \DOMDocument;
use GuzzleHttp\Client;
use \InvalidArgumentException;

class SimpleScraper
{	
	private $contentType;
	private $httpClient;

	/**
	 * 
	 * @param string $url
	 * @throws Exception
	 */
	public function __construct(
	) {
		libxml_use_internal_errors(true);
		$this->httpClient = new Client();
		$this->userAgent = 'Mozilla/5.0 (compatible; SimpleScraper)';
	}

	/**
	 * @return DomDocument
	 */
	public function loadDOM($documentBody)
	{
		$dom = new DOMDocument(null, 'UTF-8');
		$dom->loadHTML($documentBody);

		return $dom;
	}

	public function parseTitle($dom)
	{
		$titleTags = $dom->getElementsByTagName('title');
		
		if($titleTags->length > 0){
			return $titleTags->item(0)->nodeValue;
		}

		return null;
	}

	public function parseMeta($dom)
	{
		$data = [];
		$metaTags = $dom->getElementsByTagName('meta');

		for ($i=0; $i<$metaTags->length; $i++) {
			$attributes = $metaTags->item($i)->attributes;
			$attrArray = array();
			foreach ($attributes as $attr) $attrArray[$attr->nodeName] = $attr->nodeValue;
			
			if (
				array_key_exists('property', $attrArray) && 
				preg_match('~og:([a-zA-Z:_]+)~', $attrArray['property'], $matches)
			) {
				$data['og'][$matches[1]] = $attrArray['content'];
			} else if (
				array_key_exists('name', $attrArray) &&
				preg_match('~twitter:([a-zA-Z:_]+)~', $attrArray['name'], $matches)
			) {
				$data['twitter'][$matches[1]] = $attrArray['content'];
			} else if (
				array_key_exists('name', $attrArray) &&
				array_key_exists('content', $attrArray)
			) {
				$data['meta'][$attrArray['name']] = $attrArray['content'];
			}
		}

		return $data;
	}

	/**
	 * @return array
	 */
	public function getData($url)
	{
		$documentBody = $this->fetchResource($url);
		$dom = $this->loadDOM($documentBody);

		$data = $this->parseMeta($dom);
		$title = $this->parseTitle($dom);

		$data['title'] = $title;

		return $data;
	}

	public function setClient($client)
	{
		$this->httpClient = $client;
	}
	
	private function fetchResource($url) 
	{
		$res = $this->httpClient->request('GET', $url);
		
		if (((int) $res->getStatusCode()) >= 400) {
			throw new Exception('STATUS CODE: ' . $res->getStatusCode());
		}

		return utf8_decode($res->getBody());
	}
}