<?php

namespace App\Utilities;

use InvalidArgumentException;
use NFePHP\Common\Validator;
use stdClass;

class Standardize
{
    /**
     * @var string
     */
    public $node = '';
    /**
     * @var array
     */
    public $rootTagList = [
        'distDFeInt',
        'resNFe',
        'resEvento',
        'envEvento',
        'ConsCad',
        'consSitNFe',
        'consReciNFe',
        'downloadNFe',
        'enviNFe',
        'procInutNFe',
        'retInutNFe',
        'inutNFe',
        'admCscNFCe',
        'consStatServ',
        'retDistDFeInt',
        'retEnvEvento',
        'retConsCad',
        'retConsSitNFe',
        'retConsReciNFe',
        'retDownloadNFe',
        'retEnviNFe',
        'retAdmCscNFCe',
        'retConsStatServ',
        'procEventoNFe',
        'procNFe',
        'nfeProc',
        'NFe'

    ];
    /**
     * @var string
     */
    public $json = '';

    public function __construct($xml = null)
    {
        $this->toStd($xml);
    }

    /**
     * Identify node and extract from XML for convertion type
     * @param string $xml
     * @return string identificated node name
     * @throws InvalidArgumentException
     */
    public function whichIs($xml)
    {
        if (!Validator::isXML($xml)) {
            throw new InvalidArgumentException(
                "O argumento passado não é um XML válido."
            );
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        foreach ($this->rootTagList as $key) {
            $node = !empty($dom->getElementsByTagName($key)->item(0))
                ? $dom->getElementsByTagName($key)->item(0)
                : '';
            if (!empty($node)) {
                $this->node = $dom->saveXML($node);
                return $key;
            }
        }
        throw new InvalidArgumentException(
            "Este xml não pertence ao projeto NFSe GovBR2."
        );
    }

    /**
     * Returns extract node from XML
     * @return string
     */
    public function __toString()
    {
        return $this->node;
    }

    /**
     * Returns stdClass converted from xml
     * @param string $xml
     * @return stdClass
     */
    public function toStd($xml = null)
    {
        if (!empty($xml)) {
            $this->whichIs($xml);
        }
        $sxml = simplexml_load_string($this->node);
        $this->json = str_replace(
            '@attributes',
            'attributes',
            json_encode($sxml, JSON_PRETTY_PRINT)
        );
        return json_decode($this->json);
    }

    /**
     * Retruns JSON string form XML
     * @param string $xml
     * @return string
     */
    public function toJson($xml = null)
    {
        if (!empty($xml)) {
            $this->toStd($xml);
        }
        return $this->json;
    }

    /**
     * Returns array from XML
     * @param string $xml
     * @return array
     */
    public function toArray($xml = null)
    {
        if (!empty($xml)) {
            $this->toStd($xml);
        }
        return json_decode($this->json, true);
    }
}
