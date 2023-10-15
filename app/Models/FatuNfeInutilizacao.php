<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfeInutilizacao extends Model
{
    protected $table = 'FATU_NFE_INUTILIZACAO';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'FATU_NFE_INUTILIZACAO_ID',
        'EMPRESA_ID',
        'FILIAL_ID',
        'MODELO_DOC_FISCAL_ID',
        'SERIE',
        'NUMERO_INICIAL',
        'NUMERO_FINAL',
        'DT_SOLICITACAO',
        'ANO',
        'CODIGO_UF',
        'VERSAO',
        'AMBIENTE',
        'JUSTIFICATIVA',
        'CNPJ',
        'ID_TAG',
        'RET_VERSAO',
        'RET_CODIGO_STATUS',
        'RET_MOTIVO_STATUS',
        'RET_DATA_HORA_RECIBO',
        'RET_NUMERO_PROTOCOLO',
        'XML_ENVIO_OLD',
        'XML_RETORNO_OLD',
        'COLABORADOR_SOLICITANTE_ID',
        'USUARIO_SOLICITANTE_ID',
        'XML_RETORNO',
        'XML_ENVIO',
    ];
}
