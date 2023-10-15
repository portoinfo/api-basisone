<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\FatuNf;
use App\Models\FatuNfeInfoComplementar;
use App\Models\FatuNfeReferenciada;
use App\Models\FatuNfeVolumes;
use App\Models\FatuNfItem;
use App\Models\FatuNfItemCombustivel;
use App\Models\FatuNfParcela;
use App\Utilities\Functions;
use CloudDfe\SdkPHP\Nfe;

class NfeService
{
    const ENV_PENDENTE = 'P';
    const ENV_AUTORIZADO = 'A';
    const ENV_CANCELADO = 'C';
    const ENV_REJEITADO = 'R';
    const ENV_ENVIADO = 'E';
    const ENV_SEPARADA = 'S';
    const ENV_FINALIZADA = 'F';

    private static function config(Empresa $empresa)
    {
        return [
            'token' => $empresa->TOKEN_INTEGRA_NOTAS,
            /**
             * @todo ver depois
             */
            'ambiente' => 2,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
    }

    /**
     * Cria o payload a partir dos dados da NFe Emitida
     * @param int $empresa_id Codigo da empresa
     * @param int $filial_id Codigo da filial
     * @param int $nf_id Codido da nota fiscal
     * @param boolean $preview
     * @return array
     */
    public static function payload($empresa_id, $filial_id, $nf_id, $preview = false)
    {
        $nfe = FatuNf::select([
            'CFOP_ID',
            'CFOP_NATUREZA',
            'NRO_NOTA',
            'SERIE_NF',
            'DT_EMISSAO',
            'HORA_EMISSAO',
            'DT_SAIDA',
            'HORA_SAIDA',
            'NFE_FINALIDADE_ID',
            'ES',
            'IDE_CONSUMIDOR_FINAL',
            'NFE_INDICADOR_PRESENCA_ID',
            'CNPJ_CPF',
            'NOME_FANTASIA',
            'RAZAO_SOCIAL',
            'NFE_INDICATIVO_IE_ID',
            'IE',
            'LOGRADOURO',
            'NUMERO',
            'COMPLEMENTO',
            'BAIRRO',
            'LOCALIDADE_COD_IBGE',
            'LOCALIDADE',
            'UF_ID',
            'CEP',
            'LOCALIDADE_COD_IBGE_PAIS',
            'PAIS',
            'TELEFONE',
            'EMAIL',
            'IND_NAT_FRETE_ID',
            'VL_TOTAL_NF',
            'DI_NUMERO_CONHECIMENTO_EMBARQUE',
            'DI_DT_EMISSAO',
            'DI_DESEMBARACO_LOCAL',
            'DI_DESEMBARACO_UF_ID',
            'DI_DESEMBARACO_DATA',
            'NFE_LOCAL_EMBARQUE_UF_ID',
            'NFE_LOCAL_EMBARQUE',
            'NFE_LOCAL_DESPACHO',
        ])->where('EMPRESA_ID', $empresa_id)
            ->where('FILIAL_ID', $filial_id)
            ->where('FATU_NF_ID', $nf_id)
            ->first();

        $data_entrada_saida = null;
        if (!empty($nfe->DT_SAIDA) and !empty($nfe->HORA_SAIDA)) {
            $data_entrada_saida = date("{$nfe->DT_SAIDA}\T{$nfe->HORA_SAIDA}P");
        }
        $cnpjcpf = Functions::soNumeros($nfe->CNPJ_CPF);
        if (substr($nfe->CFOP_ID, 0, 1) == 3) {
            $nfe->LOCALIDADE_COD_IBGE = '9999999';
            $nfe->UF_ID = 'EX';
        }
        $payload = [
            "natureza_operacao" => "{$nfe->CFOP_NATUREZA}",
            "serie" => "{$nfe->SERIE_NF}",
            "numero" => $preview ? '999' : "{$nfe->NRO_NOTA}",
            "data_emissao" => date("{$nfe->DT_EMISSAO}\T{$nfe->HORA_EMISSAO}P"),
            "data_entrada_saida" => $data_entrada_saida,
            "tipo_operacao" => (trim($nfe->ES) == 'E') ? '0' : '1',
            "finalidade_emissao" => "{$nfe->NFE_FINALIDADE_ID}",
            "consumidor_final" => (trim($nfe->IDE_CONSUMIDOR_FINAL) == 'S') ? '1' : '0',
            "presenca_comprador" => "{$nfe->NFE_INDICADOR_PRESENCA_ID}",
            "notas_referenciadas" => [],
            "intermediario" => [
                "indicador" => "0"
            ],
            "destinatario" => [
                "cnpj" => (strlen($cnpjcpf) == 14) ? $cnpjcpf : null,
                "cpf" => (strlen($cnpjcpf) == 11) ? $cnpjcpf : null,
                "nome" => substr($nfe->NOME_FANTASIA, 0, 60),
                "razao" => substr($nfe->RAZAO_SOCIAL, 0, 60),
                "indicador_inscricao_estadual" => "{$nfe->NFE_INDICATIVO_IE_ID}",
                "inscricao_estadual" => Functions::soNumeros($nfe->IE),
                "endereco" => [
                    "logradouro" => $nfe->LOGRADOURO,
                    "numero" => $nfe->NUMERO,
                    "complemento" => $nfe->COMPLEMENTO,
                    "bairro" => $nfe->BAIRRO,
                    "codigo_municipio" => $nfe->LOCALIDADE_COD_IBGE,
                    "nome_municipio" => $nfe->LOCALIDADE,
                    "uf" => $nfe->UF_ID,
                    "cep" => Functions::soNumeros($nfe->CEP),
                    "codigo_pais" => strval(intval($nfe->LOCALIDADE_COD_IBGE_PAIS)),
                    "nome_pais" => $nfe->PAIS,
                    "telefone" => Functions::soNumeros($nfe->TELEFONE),
                    "email" => $nfe->EMAIL
                ]
            ],
            "itens" => [],
            "frete" => [
                "modalidade_frete" => "{$nfe->IND_NAT_FRETE_ID}",
                "volumes" => []
            ],
            "cobranca" => [
                "fatura" => [
                    "numero" => "001",
                    "valor_original" => $nfe->VL_TOTAL_NF,
                    "valor_desconto" => "0.00",
                    "valor_liquido" => $nfe->VL_TOTAL_NF
                ],
                "duplicatas" => []
            ],
            "pagamento" => [
                "formas_pagamento" => [
                    [
                        "meio_pagamento" => "14",
                        "valor" => $nfe->VL_TOTAL_NF
                    ]
                ]
            ]
        ];

        if (!empty($nfe->NFE_LOCAL_EMBARQUE_UF_ID)) {
            $payload['exportacao'] = [
                'uf_local_embarque' => "{$nfe->NFE_LOCAL_EMBARQUE_UF_ID}",
                'local_embarque' => "{$nfe->NFE_LOCAL_EMBARQUE}",
                'local_despacho' => "{$nfe->NFE_LOCAL_DESPACHO}",
            ];
        }

        $volumes = FatuNfeVolumes::where('EMPRESA_ID', $empresa_id)
            ->where('FILIAL_ID', $filial_id)
            ->where('FATU_NF_ID', $nf_id)
            ->get();
        foreach ($volumes as $volume) {
            $payload['frete']['volumes'][] = [
                'quantidade' => "{$volume->QUANTIDADE}",
                'especie' => "{$volume->ESPECIE}",
                'marca' => "{$volume->MARCA}",
                'numero' => "{$volume->NUMERACAO}",
                'peso_liquido' => "{$volume->PESO_LIQUIDO}",
                'peso_bruto' => "{$volume->PESO_BRUTO}"
            ];
        }

        $notas_referenciadas = FatuNfeReferenciada::where('EMPRESA_ID', $empresa_id)
            ->where('FILIAL_ID', $filial_id)
            ->where('FATU_NF_ID', $nf_id)
            ->get();
        foreach ($notas_referenciadas as $nota_referenciada) {
            if (!empty($nota_referenciada->NFEREF_CHAVE_ACESSO)) {
                $payload['notas_referenciadas'][] = [
                    "nfe" => [
                        "chave" => $nota_referenciada->NFEREF_CHAVE_ACESSO
                    ]
                ];
            } else {
                $nfeRef = FatuNf::select('NFE_CHAVE_ACESSO')
                    ->where('EMPRESA_ID', $nota_referenciada->NFEREF_EMPRESA_ID)
                    ->where('FILIAL_ID', $nota_referenciada->NFEREF_FILIAL_ID)
                    ->where('FATU_NF_ID', $nota_referenciada->NFEREF_FATU_NF_ID)
                    ->first();
                if (!empty($nfeRef)) {
                    $payload['notas_referenciadas'][] = [
                        "nfe" => [
                            "chave" => $nfeRef->NFE_CHAVE_ACESSO
                        ]
                    ];
                }
            }
        }

        $pagamentos = FatuNfParcela::where('EMPRESA_ID', $empresa_id)
            ->where('FILIAL_ID', $filial_id)
            ->where('FATU_NF_ID', $nf_id)
            ->get();
        foreach ($pagamentos as $pagamento) {
            $payload['cobranca']['duplicatas'][] = [
                "data_vencimento" => $pagamento->DT_VENCIMENTO,
                "valor" => $pagamento->VL_PARCELA
            ];
        }

        $infoComplementar = FatuNfeInfoComplementar::where('EMPRESA_ID', $empresa_id)
            ->where('FILIAL_ID', $filial_id)
            ->where('FATU_NF_ID', $nf_id)
            ->first();
        if (!empty($infoComplementar)) {
            $payload['informacoes_adicionais_contribuinte'] = $infoComplementar->INFO_COMPLEMENTAR_CONTRIBUINTE;
        }

        $itens = FatuNfItem::select([
            'CODIGO_COMPLETO_PRODUTO',
            'DESCRICAO_COMPLETA_PRODUTO',
            'PROD_CLASSIFICACAO_FISCAL',
            'CFOP_ID',
            'CEST',
            'CODIGO_BARRA',
            'PROD_UNIDADE_SIGLA',
            'QUANTIDADE',
            'VL_UNITARIO',
            'VL_TOTAL',
            'UNIDADE_TRIBUTARIA_SIGLA',
            'QUANTIDADE_TRIBUTARIA',
            'VL_UNITARIO_TRIBUTARIA',
            'SITUACAO_TRIBUTARIA_ID',
            'VL_BASE_CALCULO_ICMS',
            'ICMS_BASE_CALCULO',
            'ICMS_ALIQUOTA',
            'VL_ICMS',
            'VL_BASE_ICMS_SUBST',
            'REDUCAO_BASE_ICMSST',
            'ICMST_ALIQUOTA_INTERNA_UF',
            'VL_ICMS_SUBST',
            'MARGEM_SUBST_TRIB_ICMS',
            'SITUACAO_TRIBUTARIA_PIS_ID',
            'VL_BASE_PIS',
            'PIS_ALIQUOTA',
            'VL_PIS',
            'SITUACAO_TRIBUTARIA_COFINS_ID',
            'VL_BASE_COFINS',
            'COFINS_ALIQUOTA',
            'VL_COFINS',
            'SITUACAO_TRIBUTARIA_IPI_ID',
            'VL_BASE_CALCULO_IPI',
            'IPI_ALIQUOTA',
            'VL_IPI',
            'CODIGO_ENQUADRAMENTO_IPI_ID',
            'VL_BASE_II',
            'VL_DESPESA_ADUANEIRA',
            'VL_II',
            'VL_IOF_II',
            'SITUACAO_TRIBUTARIA_CSOSN_ID',
            'SIMP_NAC_ALIQUOTA_CRED_LC123',
            'SIMP_NAC_VL_ICMS_CRED_LC123',
            'IMPORTACAO_VIA_TRANSP_DI',
            'IMPORTACAO_VL_AFRMM',
            'IMPORTACAO_IDE_INTERMEDIO',
            'IMPORTACAO_ADQUIRENTE_CNPJ',
            'IMPORTACAO_ADQUIRENTE_UF_ID',
            'DI_ADICAO_COD_FABRICANTE',
            'DI_ADICAO_NSEQ_ITEM',
            'DI_ADICAO_COD_FABRICANTE',
            'IMPORTACAO_NUMERO_DRAWBACK',
        ])->where('EMPRESA_ID', $empresa_id)
            ->where('FILIAL_ID', $filial_id)
            ->where('FATU_NF_ID', $nf_id)
            ->get();
        $nItem = 1;
        foreach ($itens as $item) {
            $produto = [
                "numero_item" => strval($nItem),
                "codigo_produto" => "{$item->CODIGO_COMPLETO_PRODUTO}",
                "descricao" => $item->DESCRICAO_COMPLETA_PRODUTO,
                "codigo_ncm" => Functions::soNumeros($item->PROD_CLASSIFICACAO_FISCAL),
                "cfop" => "{$item->CFOP_ID}",
                "cest" => "{$item->CEST}",
                "codigo_barras_comercial" => "{$item->CODIGO_BARRA}",
                "unidade_comercial" => "{$item->PROD_UNIDADE_SIGLA}",
                "quantidade_comercial" => $item->QUANTIDADE,
                "valor_unitario_comercial" => $item->VL_UNITARIO,
                "valor_bruto" => $item->VL_TOTAL,
                "codigo_barras_tributavel" => "{$item->CODIGO_BARRA}",
                "unidade_tributavel" => "{$item->UNIDADE_TRIBUTARIA_SIGLA}",
                "quantidade_tributavel" => $item->QUANTIDADE_TRIBUTARIA,
                "valor_unitario_tributavel" => $item->VL_UNITARIO_TRIBUTARIA,
                "origem" => substr($item->SITUACAO_TRIBUTARIA_ID, 0, 1),
                "inclui_no_total" => "1",
                "imposto" => [
                    "valor_total_tributos" => 0,
                    "icms" => [],
                    "pis" => [],
                    "cofins" => []
                ],
                "valor_desconto" => 0,
                "valor_frete" => 0,
                "valor_seguro" => 0,
                "valor_outras_despesas" => 0,
                "informacoes_adicionais_item" => ""
            ];

            $infoCombustiveis = FatuNfItemCombustivel::with('anp')
                ->where('EMPRESA_ID', $empresa_id)
                ->where('FILIAL_ID', $filial_id)
                ->where('FATU_NF_ID', $nf_id)
                ->where('FATU_NFITEM_ID', $item->FATU_NFITEM_ID)
                ->first();
            if (!empty($infoCombustiveis)) {
                $produto['combustiveis'] = [
                    'codigo_anp' => "{$infoCombustiveis->CODIGO_ANP_ID}",
                    'descricao_anp' => "{$infoCombustiveis->anp->DESCRICAO_PRODUTO_ANP}",
                    'percentual_glp' => '',
                    'percentual_gas_natural_nacional' => '',
                    'percentual_gas_natural_importado' => '',
                    'valor_partida' => '',
                    'registro_codif' => '',
                    'quantidade_temperatura_ambiente' => '',
                    'sigla_uf' => "{$infoCombustiveis->CONSUMO_UF_ID}",
                    'cide_base_calculo' => '',
                    'cide_aliquota' => '',
                    'cide_valor' => '',
                    'percentual_biodiesel' => '',
                    'encerrante' => '',
                    'origens' => ''
                ];
            }

            if (substr($item->CFOP_ID, 0, 1) == 3) {
                $produto['documentos_importacao'] = [
                    [
                        'numero' => "{$nfe->DI_NUMERO_CONHECIMENTO_EMBARQUE}",
                        'data_registro' => "{$nfe->DI_DT_EMISSAO}",
                        'local_desembaraco_aduaneiro' => "{$nfe->DI_DESEMBARACO_LOCAL}",
                        'uf_desembaraco_aduaneiro' => "{$nfe->DI_DESEMBARACO_UF_ID}",
                        'data_desembaraco_aduaneiro' => "{$nfe->DI_DESEMBARACO_DATA}",
                        'via_transporte' => "{$item->IMPORTACAO_VIA_TRANSP_DI}",
                        'valor_afrmm' => "{$item->IMPORTACAO_VL_AFRMM}",
                        'forma_intermedio' => "{$item->IMPORTACAO_IDE_INTERMEDIO}",
                        'cnpj' => "{$item->IMPORTACAO_ADQUIRENTE_CNPJ}",
                        'uf_terceiro' => "{$item->IMPORTACAO_ADQUIRENTE_UF_ID}",
                        'codigo_exportador' => "{$item->DI_ADICAO_COD_FABRICANTE}",
                        'adicoes' => [
                            [
                                'numero' => "1",
                                'numero_sequencial_item' => "{$item->DI_ADICAO_NSEQ_ITEM}",
                                'codigo_fabricante_estrangeiro' => "{$item->DI_ADICAO_COD_FABRICANTE}",
                                'valor_desconto' => "",
                                'numero_drawback' => "{$item->IMPORTACAO_NUMERO_DRAWBACK}",
                            ]
                        ]
                    ]
                ];
            }

            if (!empty($item->SITUACAO_TRIBUTARIA_ID)) {
                $produto['imposto']['icms'] = [
                    "situacao_tributaria" => substr($item->SITUACAO_TRIBUTARIA_ID, 1),
                    "modalidade_base_calculo" => "3",
                    "valor_base_calculo" => $item->VL_BASE_CALCULO_ICMS,
                    "aliquota_reducao_base_calculo" => ($item->ICMS_BASE_CALCULO > 0) ? 100 - $item->ICMS_BASE_CALCULO : null,
                    "aliquota" => $item->ICMS_ALIQUOTA,
                    "valor" => $item->VL_ICMS,
                    "modalidade_base_calculo_st" => "4",
                    "valor_base_calculo_st" => $item->VL_BASE_ICMS_SUBST,
                    "aliquota_reducao_base_calculo_st" => $item->REDUCAO_BASE_ICMSST,
                    "aliquota_st" => $item->ICMST_ALIQUOTA_INTERNA_UF,
                    "valor_st" => $item->VL_ICMS_SUBST,
                    "aliquota_margem_valor_adicionado_st" => $item->MARGEM_SUBST_TRIB_ICMS
                ];
            } else {
                $produto['imposto']['icms'] = [
                    "situacao_tributaria" => $item->SITUACAO_TRIBUTARIA_CSOSN_ID,
                    "aliquota_credito_simples" => $item->SIMP_NAC_ALIQUOTA_CRED_LC123,
                    "valor_credito_simples" => $item->SIMP_NAC_VL_ICMS_CRED_LC123,
                ];
            }
            if (!empty($item->SITUACAO_TRIBUTARIA_IPI_ID)) {
                $produto['imposto']['ipi'] = [
                    "situacao_tributaria" => substr($item->SITUACAO_TRIBUTARIA_IPI_ID, 1),
                    "valor_base_calculo" => $item->VL_BASE_CALCULO_IPI,
                    "aliquota" => $item->IPI_ALIQUOTA,
                    "valor" => $item->VL_IPI,
                    "codigo_enquadramento_legal" => "{$item->CODIGO_ENQUADRAMENTO_IPI_ID}"
                ];
            }
            if (substr($nfe->CFOP_ID, 0, 1) == 3) {
                $produto['imposto']['importacao'] = [
                    "valor_base_calculo" => $item->VL_BASE_II,
                    "valor_aduaneiro" => $item->VL_DESPESA_ADUANEIRA,
                    "valor" => $item->VL_II,
                    "valor_iof" => $item->VL_IOF_II
                ];
            }
            $produto['imposto']['pis'] = [
                "situacao_tributaria" => substr($item->SITUACAO_TRIBUTARIA_PIS_ID, 1),
                "valor_base_calculo" => $item->VL_BASE_PIS,
                "aliquota" => $item->PIS_ALIQUOTA,
                "valor" => $item->VL_PIS
            ];
            $produto['imposto']['cofins'] = [
                "situacao_tributaria" => substr($item->SITUACAO_TRIBUTARIA_COFINS_ID, 1),
                "valor_base_calculo" => $item->VL_BASE_COFINS,
                "aliquota" => $item->COFINS_ALIQUOTA,
                "valor" => $item->VL_COFINS
            ];
            $payload['itens'][] = $produto;
            $nItem++;
        }
        return $payload;
    }

    /**
     * Envia a NFe para a API
     * @param Empresa $empresa
     * @param array $payload
     * @return \stdClass
     * @throws \Exception
     */
    public static function envia(Empresa $empresa, array $payload)
    {
        $nfe = new Nfe(self::config($empresa));
        return $nfe->cria($payload);
    }

    /**
     * Solicita o preview da NFe
     * @param Empresa $empresa
     * @param array $payload
     * @return \stdClass
     * @throws \Exception
     */
    public static function preview(Empresa $empresa, array $payload)
    {
        $nfe = new Nfe(self::config($empresa));
        return $nfe->preview($payload);
    }

    /**
     * Consulta o status na NFe na API
     * @param Empresa $empresa
     * @param string $chave
     * @return \stdClass
     * @throws \Exception
     */
    public static function consulta(Empresa $empresa, string $chave)
    {
        $payload = [
            'chave' => $chave
        ];
        $nfe = new Nfe(self::config($empresa));
        return $nfe->consulta($payload);
    }

    /**
     * Solicita o cancelamento da NFe na API
     * @param Empresa $empresa
     * @param string $chave
     * @param string $justificativa
     * @return \stdClass
     * @throws \Exception
     */
    public static function cancela(Empresa $empresa, string $chave, string $justificativa)
    {
        $payload = [
            'chave' => $chave,
            'justificativa' => $justificativa
        ];
        $nfe = new Nfe(self::config($empresa));
        return $nfe->cancela($payload);
    }

    /**
     * Solicita emissão de Carta da Correção na API
     * @param Empresa $empresa
     * @param string $chave
     * @param string $justificativa
     * @param mixed $sequencial
     * @return \stdClass
     * @throws \Exception
     */
    public static function correcao(Empresa $empresa, string $chave, string $justificativa, $sequencial = null)
    {
        $payload = [
            'chave' => $chave,
            'justificativa' => $justificativa,
            'sequencial' => $sequencial
        ];
        $nfe = new Nfe(self::config($empresa));
        return $nfe->correcao($payload);
    }

    /**
     * Solicita a inutilização de Faixa de NFe na API
     * @param Empresa $empresa
     * @param string $inicio
     * @param string $final
     * @param string $serie
     * @param string $justificativa
     * @return \stdClass
     * @throws \Exception
     */
    public static function inutiliza(Empresa $empresa, string $inicio, string $final, string $serie, string $justificativa)
    {
        $payload = [
            "serie" => $serie,
            "justificativa" => $justificativa,
            "numero_inicial" => $inicio,
            "numero_final" => $final
        ];
        $nfe = new Nfe(self::config($empresa));
        return $nfe->inutiliza($payload);
    }
}
