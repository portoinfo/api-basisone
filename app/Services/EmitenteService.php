<?php

namespace App\Services;

use App\Models\Tenant\Company;
use CloudDfe\SdkPHP\Certificado;
use CloudDfe\SdkPHP\Emitente;

class EmitenteService
{
    private static function config(Company $company)
    {
        return [
            'token' => $company->cloud_token,
            'ambiente' => $company->cloud_ambiente,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
    }

    /**
     * Atualiza o emitente na API
     * @param Company $company
     * @param array $payload
     * @return \stdClass
     * @throws \Exception
     */
    public static function atualiza(Company $company, array $payload)
    {
        $emitente = new Emitente(self::config($company));
        return $emitente->atualiza($payload);
    }

    /**
     * Atualiza o certificado na API
     * @param Company $company
     * @param array $payload
     * @return \stdClass
     * @throws \Exception
     */
    public static function certificado(Company $company, array $payload)
    {
        $certificado = new Certificado(self::config($company));
        return $certificado->atualiza($payload);
    }
}
