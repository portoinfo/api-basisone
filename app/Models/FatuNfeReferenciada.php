<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfeReferenciada extends Model
{
    protected $table = 'FATU_NFE_REFERENCIADA';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'FILIAL_ID',
        'FATU_NF_ID',
        'FATU_NFE_REFERENCIADA_ID',
        'NFEREF_EMPRESA_ID',
        'NFEREF_FILIAL_ID',
        'NFEREF_FATU_NF_ID',
        'NFEREF_CHAVE_ACESSO',
        'NFREF_PAIS_ID',
        'NFREF_UF_ID',
        'NFREF_MODELO_DOC_FISCAL_ID',
        'NFREF_CNPJ',
        'NFREF_CPF',
        'NFREF_DT_EMISSAO',
        'NFREF_NUMERO',
        'NFREF_SERIE',
    ];
}
