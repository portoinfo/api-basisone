<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'EMPRESA';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'PARENTE_ID',
        'RAZAO_SOCIAL',
        'NOME_FANTASIA',
        'NUMERO',
        'COMPLEMENTO',
        'CNPJ',
        'IE',
        'INSC_MUNICIPAL',
        'MATRIZ',
        'EMAIL_VALIDAR',
        'VALIDAR_EMAIL_CHAVE',
        'REG_MIN_AGRIC',
        'EXCLUIDO',
        'LOGRADOURO_ID',
        'FONE',
        'EMAIL',
        'EMAIL_PEDIDO_COMPRA',
        'CAIXA_POSTAL',
        'DDD',
        'DDDFAX',
        'FAX',
        'WWW',
        'MUD_ENDERECO_EMPRESA',
        'CPF_RESPONSAVEL',
        'NOME_RESPONSAVEL',
        'IDE_TIP_INSC_EMPRESA',
        'INSC_EMPRESA',
        'NUM_CEI',
        'IDE_TIP_INSC_FORNEC',
        'INSC_FORNECEDOR',
        'COD_EMPRESA_CEF',
        'NUM_PROPRIETARIO',
        'COD_CENTRALIZACAO',
        'FP_CNAE_ID',
        'FP_PAGAMENTO_GPS_ID',
        'MAT_EMPRESA_INSS',
        'OUTRAS_ENTIDADE',
        'FP_CODIGO_RECOLHIMENTO_ID',
        'BANCO_GPS_ID',
        'AGE_GPS',
        'BANCO_PIS_ID',
        'AGE_PIS',
        'NUM_CONVENIO_MINTRAB',
        'FP_NATUREZA_DIRF_ID',
        'FP_CAD_FPAS_ID',
        'PER_CONTR_FPAS_EMPR',
        'PER_CONTR_FPAS_AUTON',
        'PER_ALIQ_RAT',
        'TIP_IDENTIFICACAO_RAIS',
        'COD_IBGE',
        'MES_BASE_CATEGORIA',
        'IDE_ENC_ATIVIDADE',
        'DAT_ENC_ATIVIDADE',
        'FP_NATUREZA_JURIDICA_ID',
        'IDE_PORTE_EMPRESA',
        'FP_OPTANTE_SIMPLES_ID',
        'IDE_EMP_PARTICIPA_PAT',
        'PER_PAT_SERV_PROPRIO',
        'PER_PAT_ADM_COZINHA',
        'PER_PAT_REF_CONVENIO',
        'PER_PAT_REF_TRANSP',
        'PER_PAT_CESTA_BASICA',
        'PER_PAT_ALIM_CONVE',
        'BANCO_ID',
        'AGE_BANCO',
        'CON_CORRENTE',
        'IRRF_ESPECIE_ID',
        'IRRF_PARCEIRO_ID',
        'IRRF_REGRA',
        'IRRF_VENCIMENTO',
        'IRRF_IDE_PAG_FDS',
        'PIS_COFINS_ESPECIE_ID',
        'PIS_COFINS_PARCEIRO_ID',
        'PIS_COFINS_REGRA',
        'PIS_COFINS_PROCEDURE',
        'INSS_ESPECIE_ID',
        'INSS_PARCEIRO_ID',
        'INSS_COFINS_REGRA',
        'INSS_VENCIMENTO',
        'INSS_IDE_PAG_FDS',
        'AUTONOMO_INSS_ALIQUOTA',
        'AUTONOMO_BASE_FRETE',
        'AUTONOMO_SEST',
        'AUTONOMO_BASE_OUTROS',
        'AUTONOMO_INSS_EMPRESA',
        'AUTONOMO_TETO',
        'AUTONOMO_BASE_IRRF',
        'AUTONOMO_BASE_IRRF_FRETE',
        'AUTONOMO_BASE_IRRF_PASSAGEIRO',
        'CODIGO',
        'DT_ULTATU_PDV',
        'RESP_CONTAB',
        'CPF_RESP_CONTAB',
        'CRC_RESP_CONTAB',
        'REGISTRO_JUCESP',
        'DT_REG_JUCESP',
        'FIN_CONTA_BANCARIA_ID',
        'HORA_PERMITIDA_LEITURA_Z',
        'IDE_USA_LIMITE_CREDITO',
        'BLOQUEAR_VENDA_SEM_ESTOQUE',
        'IDE_PAGA_ADTO_FERIAS',
        'AUTONOMO_BASE_IRRF_OPERADOR_MAQ',
        'EXERCITO_REGISTRO',
        'EXERCITO_VALIDADE',
        'SSP_REGISTRO',
        'SSP_VALIDADE',
        'POLICIA_FEDERAL_REGISTRO',
        'POLICIA_FEDERAL_VALIDADE',
        'RG_RESPONSAVEL',
        'ORGAO_RG_RESPONSAVEL',
        'UF_RG_RESPONSAVEL',
        'DT_EXP_RG_RESPONSAVEL',
        'EXERCITO_RESPONSAVEL',
        'EXERCITO_RESP_CARGO',
        'EXERCITO_CODIGO_EMPRESA',
        'SSP_CODIGO_EMPRESA',
        'SSP_LICENCA',
        'SSP_VALIDADE_LICENCA',
        'PAIS_ID',
        'DT_FUNDACAO',
        'FATU_OBS_PADRAO',
        'UF_ID',
        'LOCALIDADE_ID',
        'EMAIL_NFE_ENVIADA',
        'IDE_NFE_DANFE_PADRAO',
        'NIRE',
        'SPED_RESPONSAVEL_ADM_CAD_ID',
        'CODIGO_REGIME_TRIBUTARIO_ID',
        'COM_OBS_PADRAO',
        'CPR_OBS_PADRAO',
        'SUFRAMA',
        'IDE_ESCRITURA_IPI_NO_LIVRO',
        'TIPO_CONTROLE_PONTO',
        'IDE_CUSTO_IMPOSTOS_CUSTO_MEDIO',
        'RNTRC',
        'FP_CNAE_PREPONDERANTE_ID',
        'EMPRESA_ID_INCORPORADORA',
        'DT_INCORPORACAO',
        'IBPT_TOKEN',
        'TOKEN_INTEGRA_NOTAS'
    ];
}
