<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfeCce extends Model
{
    protected $table = 'FATU_NFE_CCE';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'FILIAL_ID',
        'FATU_NF_ID',
        'FATU_NFE_CCE_ID',
        'N_SEQ_EVENTO',
        'ID',
        'VERSAO',
        'VERSAO_DET_EVENTO',
        'VERSAO_INF_EVENTO',
        'VERSAO_EVENTO',
        'ID_LOTE',
        'DESC_EVENTO',
        'C_ORGAO',
        'TP_EVENTO',
        'DH_EVENTO',
        'X_CORRECAO',
        'X_COND_USO',
        'RET_IDLOTE',
        'RET_TPAMB',
        'RET_VERAPLIC',
        'RET_CORGAO',
        'RET_CSTAT',
        'RET_XMOTIVO',
        'RET_INFEVENTO_CSTAT',
        'RET_INFEVENTO_NPROT',
        'RET_INFEVENTO_XMOTIVO',
        'RET_INFEVENTO_XEVENTO',
        'RET_INFEVENTO_CNPJDEST',
        'RET_INFEVENTO_CPFDEST',
        'RET_INFEVENTO_EMAILDEST',
        'XML_ENVIO_EVENTO',
        'XML_RETORNO_EVENTO',
        'XML_DISTRIBUICAO',
        'COLABORADOR_ID',
        'USUARIO_ID',
    ];
}
