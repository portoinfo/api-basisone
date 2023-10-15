<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
//    $empresa = \App\Models\FatuNf::select('NRO_NOTA')
//        ->with('itens')
//        ->where('FILIAL_ID', 1)
//        ->where('EMPRESA_ID', 1)
//        ->where('FATU_NF_ID', 106796)
//        ->first();

    $dados = \Illuminate\Support\Facades\DB::raw(\Illuminate\Support\Facades\DB::select('
            SELECT
            CFOP_NATUREZA,
                NRO_NOTA,
                SERIE_NF,
                DT_EMISSAO,
                HORA_EMISSAO,
                DT_SAIDA,
                CNPJ_CPF
            FROM
                "FATU_NF"
            WHERE
                FILIAL_ID = 1
                AND EMPRESA_ID = 1
                AND FATU_NF_ID = 106796
    '));
    dd($dados);
});
