<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdTabelaAnp extends Model
{
    protected $table = 'PROD_TABELA_ANP';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'CODIGO_ANP_ID',
        'DESCRICAO_PRODUTO_ANP',
        'FAMILIA_ANP',
        'GUPO_ANP',
        'SUB_GUPO_ANP',
        'SUB_SUB_GUPO_ANP',
        'UNIDADE_GRANDEZA_ANP',
        'UNIDADE_MEDIDA',
        'RAMO_ANP',
        'DT_INCLUSAO',
        'DT_FINAL_VALIDADE',
    ];
}
