<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\FatuNf;
use App\Models\FatuNfeCce;
use App\Models\FatuNfeInutilizacao;
use App\Models\FatuNfeNumeracao;
use App\Models\FatuNfItem;
use App\Models\FatuNfStatus;
use App\Services\NfeService;
use App\Utilities\Standardize;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use NFePHP\DA\NFe\Daevento;
use NFePHP\DA\NFe\Danfe;

class NFeController extends Controller
{
    /**
     * @var int
     */
    private $empresa_id = null;
    /**
     * @var int
     */
    private $filial_id = null;
    /**
     * @var int
     */
    private $nf_id = null;
    /**
     * @var FatuNf
     */
    private $nfe;
    /**
     * @var int
     */
    private $usuario_id = null;
    /**
     * @var int
     */
    private $colaborador_id = null;

    public function envia(Request $request)
    {
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->nf_id = $request->nf_id;
        $this->usuario_id = $request->usuario_id;
        $this->colaborador_id = $request->colaborador_id;

        $this->nfe = FatuNf::select([
            'CFOP_ID',
            'NRO_NOTA',
            'SERIE_NF',
            'CFOP_IDE_CONTROLA_ESTOQUE',
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NF_ID', $this->nf_id)
            ->first();

        $empresa = Empresa::where('EMPRESA_ID', $this->empresa_id)
            ->first();

        // controla o numero sequencial da nfe
        if (empty($this->nfe->NRO_NOTA)) {
            $fatuNumeracao = FatuNfeNumeracao::where('EMPRESA_ID', $this->empresa_id)
                ->where('FILIAL_ID', $this->filial_id)
                ->where('SITUACAO_NUMERO_ID', 'LIVRE')
                ->where('SERIE', $this->nfe->SERIE_NF)
                ->first();
            if (empty($fatuNumeracao)) {
                $fatuNumeracao = FatuNfeNumeracao::where('EMPRESA_ID', $this->empresa_id)
                    ->where('FILIAL_ID', $this->filial_id)
                    ->where('SITUACAO_NUMERO_ID', 'UTILIZADO')
                    ->where('SERIE', $this->nfe->SERIE_NF)
                    ->orderBy('NUMERO', 'desc')
                    ->first();
                $proximoNumero = $fatuNumeracao->NUMERO + 1;
                $fatuNumeracao = FatuNfeNumeracao::create([
                    'EMPRESA_ID' => $this->empresa_id,
                    'FILIAL_ID' => $this->filial_id,
                    'SERIE' => $this->nfe->SERIE_NF,
                    'NUMERO' => (empty($fatuNumeracao)) ? 1 : $proximoNumero,
                    'SITUACAO_NUMERO_ID' => 'UTILIZADO',
                    'FATU_NF_ID' => $this->nf_id
                ]);
            } else {
                DB::statement("UPDATE FATU_NFE_NUMERACAO SET NUMERO = '{$fatuNumeracao->NUMERO}',
                              SITUACAO_NUMERO_ID = 'UTILIZADO'
                              WHERE EMPRESA_ID = '{$this->empresa_id}'
                              AND FILIAL_ID = '{$this->filial_id}'
                              AND SERIE = '{$fatuNumeracao->SERIE}'
                              AND NUMERO = '{$fatuNumeracao->NUMERO}'");
            }
            DB::statement("UPDATE FATU_NF SET NRO_NOTA = '{$fatuNumeracao->NUMERO}'
                                WHERE EMPRESA_ID = '{$this->empresa_id}'
                                AND FILIAL_ID = '{$this->filial_id}'
                                AND FATU_NF_ID = '{$this->nf_id}'");
        }

        $payload = NfeService::payload($this->empresa_id, $this->filial_id, $this->nf_id);

        $this->atualizaStatus('NFeAGTRANS', now()->format("m/d/Y H:i:s"), 'Preparando envio');

        $resp = NfeService::envia($empresa, $payload);
        if ($resp->sucesso) {
            $chave = $resp->chave;

            DB::statement("UPDATE FATU_NF SET PROTNFE_CHNFE = '{$chave}',
                                NFE_CHAVE_ACESSO = '{$chave}'
                                WHERE EMPRESA_ID = '{$this->empresa_id}'
                                AND FILIAL_ID = '{$this->filial_id}'
                                AND FATU_NF_ID = '{$this->nf_id}'");

            sleep(3);
            $tentativa = 1;
            $processou = false;
            while ($tentativa <= 5) {
                $resp = NfeService::consulta($empresa, $chave);
                if ($resp->codigo != 5023) {
                    if ($resp->sucesso) {
                        // autorizado
                        $this->autoriza($resp);
                        $processou = true;
                        break;
                    } else {
                        // rejeição
                        $this->atualizaStatus('NFeREJEITA', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
                        $processou = true;
                        break;
                    }
                }
                sleep(3);
                $tentativa++;
            }
            if (!$processou) {
                $this->atualizaStatus('NFeAGPROCE', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
            }
            return response()->json($resp);
        } else if (in_array($resp->codigo, [5001, 5002])) {
            // erro nos campos
            $erros = [];
            foreach ($resp->erros as $erro) {
                $erros[] = "[{$erro->campo}] {$erro->erro} {$erro->descricao} {$erro->detalhes}";
            }
            $this->atualizaStatus('NFeERRO', now()->format("m/d/Y H:i:s"), implode(" ", $erros));
            return response()->json($resp);
        } else if ($resp->codigo == 5008 or ($resp->codigo >= 7000 and $resp->codigo < 8000)) {
            $chave = $resp->chave;
            if ($resp->codigo == 5008) {
                $resp = NfeService::consulta($empresa, $chave);
                if ($resp->codigo != 5023) {
                    if ($resp->sucesso) {
                        // autorizado
                        $this->autoriza($resp);
                    } else {
                        // rejeição
                        $this->atualizaStatus('NFeREJEITA', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
                    }
                } else {
                    $this->atualizaStatus('NFeAGPROCE', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
                }
                return response()->json($resp);
            } else {
                $this->atualizaStatus('FALHACOMUN', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
                return response()->json($resp);
            }
        } else {
            $this->atualizaStatus('NFeREJEITA', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
            return response()->json($resp);
        }
        return response()->json([
           'sucesso' => $resp->sucesso,
            'pdf' => $resp->pdf ?? null
        ]);
    }

    private function autoriza($resp)
    {
        if ($resp->codigo == '100') {
            if (substr($this->nfe->CFOP_ID, 0, 1) == 3 and trim($this->nfe->CFOP_IDE_CONTROLA_ESTOQUE) == 'S') {
                $status = "NFeAGENTRA";
            } else {
                $status = 'NFeAUTORIZ';
            }
        } else if ($resp->codigo == '110' || $resp->codigo == '301' || $resp->codigo == '302') {
            $status = 'NFeDENEGAD';
        } else {
            $status = "";
        }
        if (!empty($status)) {
            $this->atualizaStatus($status, now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
        }

        $ym = '20' . substr($resp->chave, 2, 4);
        $pathPDF = "documents/nfe/emitidas/{$ym}/{$resp->chave}.pdf";
        Storage::put($pathPDF, base64_decode($resp->pdf));

        $data = Carbon::parse($resp->data_hora_evento)->format("m/d/Y H:i:s");
        $xml = "data:application/gzip;base64," . base64_encode(gzencode(base64_decode($resp->xml)));
        DB::statement("UPDATE FATU_NF SET PROTNFE_CSTAT = '{$resp->codigo}',
                    PROTNFE_XMOTIVO = '{$resp->mensagem}',
                    NFE_VERSAO_XML = '4.00',
                    PROTNFE_DHRECBTO = '{$data}',
                    PROTNFE_NPROT = '{$resp->protocolo}',
                    PROTNFE_DT_HORA_RECBTO = '{$data}',
                    PROTNFE_DIGVAL = '',
                    TP_AMBIENTE_NFE = '2',
                    XML_NFE_AUTORIZADA = '{$xml}'
                    WHERE EMPRESA_ID = '{$this->empresa_id}'
                    AND FILIAL_ID = '{$this->filial_id}'
                    AND FATU_NF_ID = '{$this->nf_id}'");
    }

    private function atualizaStatus($status, $statusData, $statusObservacao)
    {
        $obs = substr($statusObservacao, 0, 4000);
        DB::statement("insert into FATU_NF_STATUS (EMPRESA_ID,
                            FILIAL_ID,
                            FATU_NF_ID,
                            FATU_NF_STATUS_ID,
                            DATA,
                            USUARIO_ID,
                            COLABORADOR_ID,
                            OBSERVACAO)
                            values ({$this->empresa_id},
                            {$this->filial_id},
                            {$this->nf_id},
                            '{$status}',
                            '{$statusData}',
                            {$this->usuario_id},
                            {$this->colaborador_id},
                            '{$obs}')");
        $itens = FatuNfItem::select([
            'FATU_NFITEM_ID',
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NF_ID', $this->nf_id)
            ->get();
        foreach ($itens as $item) {
            DB::statement("insert into FATU_NFITEM_STATUS (EMPRESA_ID,
                                FILIAL_ID,
                                FATU_NFITEM_ID,
                                FATU_NF_ID,
                                FATU_NFITEM_STATUS_ID,
                                DATA,
                                USUARIO_ID,
                                COLABORADOR_ID,
                                OBSERVACAO)
                                values ({$this->empresa_id},
                                {$this->filial_id},
                                {$item->FATU_NFITEM_ID},
                                {$this->nf_id},
                                '{$status}',
                                '{$statusData}',
                                {$this->usuario_id},
                                {$this->colaborador_id},
                                '{$obs}')");
        }
    }

    public function consulta(Request $request)
    {
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->nf_id = $request->nf_id;

        $empresa = Empresa::where('EMPRESA_ID', $this->empresa_id)
            ->first();

        $this->nfe = FatuNf::select([
            'CFOP_ID',
            'NRO_NOTA',
            'SERIE_NF',
            'CFOP_IDE_CONTROLA_ESTOQUE',
            'NFE_CHAVE_ACESSO'
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NF_ID', $this->nf_id)
            ->first();

        $resp = NfeService::consulta($empresa, $this->nfe->NFE_CHAVE_ACESSO);
        if ($resp->codigo != 5023) {
            if ($resp->sucesso) {
                // autorizado
                $this->autoriza($resp);
            } else {
                // rejeição
                $this->atualizaStatus('NFeREJEITA', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
            }
        } else {
            $this->atualizaStatus('NFeAGPROCE', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
        }
        return response()->json([
            'sucesso' => $resp->sucesso,
            'pdf' => $resp->pdf ?? null
        ]);
    }

    public function cancela(Request $request)
    {
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->nf_id = $request->nf_id;
        $this->usuario_id = $request->usuario_id;
        $this->colaborador_id = $request->colaborador_id;
        $justificativa = $request->justificativa;

        $this->nfe = FatuNf::select([
            'CFOP_ID',
            'NRO_NOTA',
            'SERIE_NF',
            'CFOP_IDE_CONTROLA_ESTOQUE',
            'NFE_CHAVE_ACESSO',
            'LAST_STATUS_ID'
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NF_ID', $this->nf_id)
            ->first();

        $empresa = Empresa::where('EMPRESA_ID', $this->empresa_id)
            ->first();

        $fatuLastStatus = FatuNfStatus::select('FATU_NF_STATUS_ID')
            ->where('ID', $this->nfe->LAST_STATUS_ID)
            ->first();

        $this->atualizaStatus('NFeAGHCANC', now()->format("m/d/Y H:i:s"), "Aguardando cancelamento");

        $resp = NfeService::cancela($empresa, $this->nfe->NFE_CHAVE_ACESSO, $justificativa);
        if ($resp->sucesso) {
            $data = Carbon::parse($resp->data_hora_evento)->format("m/d/Y H:i:s");
            $xml = "data:application/gzip;base64," . base64_encode(gzencode(base64_decode($resp->xml)));
            DB::statement("UPDATE FATU_NF SET PROTCANCNFE_CHNFE = '{$this->nfe->NFE_CHAVE_ACESSO}',
                    PROTCANCNFE_DHRECBTO = '{$data}',
                    PROTCANCNFE_NPROT = '{$resp->protocolo}',
                    PROTCANCNFE_XML_RETCANC = '{$xml}',
                    PROTCANCNFE_CSTAT = '{$resp->codigo}',
                    PROTCANCNFE_XMOTIVO = '{$resp->mensagem}',
                    PROTCANCNFE_CSTAT_EVENTO = '{$resp->codigo}',
                    PROTCANCNFE_XMOTIVO_EVENTO = '{$resp->mensagem}'
                    WHERE EMPRESA_ID = '{$this->empresa_id}'
                    AND FILIAL_ID = '{$this->filial_id}'
                    AND FATU_NF_ID = '{$this->nf_id}'");

            $ym = '20' . substr($resp->chave, 2, 4);
            $pathPDF = "documents/nfe/emitidas/{$ym}/{$resp->chave}.pdf";
            Storage::put($pathPDF, base64_decode($resp->pdf));

            $respCanc = NfeService::consulta($empresa, $this->nfe->NFE_CHAVE_ACESSO);
            $xml = "data:application/gzip;base64," . base64_encode(gzencode(base64_decode($respCanc->xml)));
            DB::statement("UPDATE FATU_NF SET XML_DISTRIBUICAO_CANCELAMENTO = '{$xml}'
                    WHERE EMPRESA_ID = '{$this->empresa_id}'
                    AND FILIAL_ID = '{$this->filial_id}'
                    AND FATU_NF_ID = '{$this->nf_id}'");

            $this->atualizaStatus('CANC', now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
        } else {
            $this->atualizaStatus($fatuLastStatus->FATU_NF_STATUS_ID, now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
        }
        return response()->json([
            'sucesso' => $resp->sucesso,
            'pdf' => $resp->pdf ?? null
        ]);
    }

    public function corrige(Request $request)
    {
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->nf_id = $request->nf_id;
        $this->usuario_id = $request->usuario_id;
        $this->colaborador_id = $request->colaborador_id;
        $correcao = $request->correcao;

        $this->nfe = FatuNf::select([
            'CFOP_ID',
            'NRO_NOTA',
            'SERIE_NF',
            'CFOP_IDE_CONTROLA_ESTOQUE',
            'NFE_CHAVE_ACESSO',
            'LAST_STATUS_ID'
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NF_ID', $this->nf_id)
            ->first();

        $empresa = Empresa::where('EMPRESA_ID', $this->empresa_id)
            ->first();

        $fatuLastStatus = FatuNfStatus::select('FATU_NF_STATUS_ID')
            ->where('ID', $this->nfe->LAST_STATUS_ID)
            ->first();

        $this->atualizaStatus('NFeAGHCCe', now()->format("m/d/Y H:i:s"), "Aguardando correção");

        $FATU_NFE_CCE_ID = FatuNfeCce::select('FATU_NFE_CCE_ID')
                ->where('EMPRESA_ID', $this->empresa_id)
                ->where('FILIAL_ID', $this->filial_id)
                ->where('FATU_NF_ID', $this->nf_id)
                ->max('FATU_NFE_CCE_ID') + 1;
        $resp = NfeService::correcao($empresa, $this->nfe->NFE_CHAVE_ACESSO, $correcao);
        if ($resp->sucesso) {
            $ym = '20' . substr($resp->chave, 2, 4);
            $pathPDF = "documents/nfe/emitidas/{$ym}/{$this->nf_id}-{$FATU_NFE_CCE_ID}.pdf";
            Storage::put($pathPDF, base64_decode($resp->pdf_carta_correcao));

            $xml = base64_decode($resp->xml_carta_correcao);

            $st = new Standardize($xml);
            $std = $st->toStd();

            $VERSAO = $std->evento->infEvento->verEvento;
            $VERSAO_EVENTO = $std->evento->infEvento->verEvento;
            $VERSAO_INF_EVENTO = $std->evento->infEvento->verEvento;
            $ID = $std->evento->infEvento->attributes->Id;
            $ID_LOTE = null;
            $VERSAO_DET_EVENTO = $std->evento->infEvento->detEvento->attributes->versao;
            $DESC_EVENTO = $std->evento->infEvento->detEvento->descEvento;
            $X_CORRECAO = $std->evento->infEvento->detEvento->xCorrecao;
            $X_COND_USO = $std->evento->infEvento->detEvento->xCondUso;
            $N_SEQ_EVENTO = $std->evento->infEvento->nSeqEvento;
            $RET_TPAMB = $std->retEvento->infEvento->tpAmb;
            $RET_VERAPLIC = $std->retEvento->infEvento->verAplic;
            $RET_CORGAO = $std->retEvento->infEvento->cOrgao;
            $RET_CSTAT = $std->retEvento->infEvento->cStat;
            $RET_XMOTIVO = $std->retEvento->infEvento->xMotivo;
            $RET_INFEVENTO_CSTAT = $std->retEvento->infEvento->cStat;
            $RET_INFEVENTO_XMOTIVO = $std->retEvento->infEvento->xMotivo;
            $RET_INFEVENTO_NPROT = $std->retEvento->infEvento->nProt;
            $RET_INFEVENTO_XEVENTO = $std->retEvento->infEvento->xEvento;
            $RET_INFEVENTO_CNPJDEST = $std->retEvento->infEvento->CNPJDest ?? null;
            $RET_INFEVENTO_CPFDEST = $std->retEvento->infEvento->CPFDest ?? null;
            $RET_INFEVENTO_EMAILDEST = null;
            $DH_EVENTO = Carbon::parse($std->retEvento->infEvento->dhRegEvento)->format("m/d/Y H:i:s");

            DB::statement("insert into FATU_NFE_CCE (EMPRESA_ID,
                                FILIAL_ID,
                                FATU_NF_ID,
                                FATU_NFE_CCE_ID,
                                N_SEQ_EVENTO,
                                VERSAO,
                                VERSAO_EVENTO,
                                VERSAO_INF_EVENTO,
                                ID,
                                ID_LOTE,
                                VERSAO_DET_EVENTO,
                                DESC_EVENTO,
                                X_CORRECAO,
                                X_COND_USO,
                                RET_TPAMB,
                                RET_VERAPLIC,
                                RET_CORGAO,
                                RET_CSTAT,
                                RET_XMOTIVO,
                                RET_INFEVENTO_CSTAT,
                                RET_INFEVENTO_XMOTIVO,
                                RET_INFEVENTO_NPROT,
                                RET_INFEVENTO_XEVENTO,
                                RET_INFEVENTO_CNPJDEST,
                                RET_INFEVENTO_CPFDEST,
                                RET_INFEVENTO_EMAILDEST,
                                DH_EVENTO,
                                XML_DISTRIBUICAO,
                                COLABORADOR_ID,
                                USUARIO_ID)
                                values ({$this->empresa_id},
                                {$this->filial_id},
                                {$this->nf_id},
                                {$FATU_NFE_CCE_ID},
                                '{$N_SEQ_EVENTO}',
                                '{$VERSAO}',
                                '{$VERSAO_EVENTO}',
                                '{$VERSAO_INF_EVENTO}',
                                '{$ID}',
                                '{$ID_LOTE}',
                                '{$VERSAO_DET_EVENTO}',
                                '{$DESC_EVENTO}',
                                '{$X_CORRECAO}',
                                '{$X_COND_USO}',
                                '{$RET_TPAMB}',
                                '{$RET_VERAPLIC}',
                                '{$RET_CORGAO}',
                                '{$RET_CSTAT}',
                                '{$RET_XMOTIVO}',
                                '{$RET_INFEVENTO_CSTAT}',
                                '{$RET_INFEVENTO_XMOTIVO}',
                                '{$RET_INFEVENTO_NPROT}',
                                '{$RET_INFEVENTO_XEVENTO}',
                                '{$RET_INFEVENTO_CNPJDEST}',
                                '{$RET_INFEVENTO_CPFDEST}',
                                '{$RET_INFEVENTO_EMAILDEST}',
                                '{$DH_EVENTO}',
                                '{$xml}',
                                '{$this->colaborador_id}',
                                '{$this->usuario_id}')");
            DB::statement("UPDATE FATU_NF SET IDE_CCE = 'S'
                    WHERE EMPRESA_ID = '{$this->empresa_id}'
                    AND FILIAL_ID = '{$this->filial_id}'
                    AND FATU_NF_ID = '{$this->nf_id}'");
        }
        $this->atualizaStatus($fatuLastStatus->FATU_NF_STATUS_ID, now()->format("m/d/Y H:i:s"), "{$resp->codigo} - {$resp->mensagem}");
        return response()->json([
            'sucesso' => $resp->sucesso,
            'pdf' => $resp->pdf_carta_correcao ?? null
        ]);
    }

    public function inutiliza(Request $request)
    {
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->fatu_nfe_inutilizacao_id = $request->fatu_nfe_inutilizacao_id;

        $fatuNfInut = FatuNfeInutilizacao::select([
            'NUMERO_INICIAL',
            'NUMERO_FINAL',
            'SERIE',
            'JUSTIFICATIVA',
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NFE_INUTILIZACAO_ID', $this->fatu_nfe_inutilizacao_id)
            ->first();

        $empresa = Empresa::where('EMPRESA_ID', $this->empresa_id)
            ->first();

        $resp = NfeService::inutiliza($empresa, $fatuNfInut->NUMERO_INICIAL, $fatuNfInut->NUMERO_FINAL, $fatuNfInut->SERIE, $fatuNfInut->JUSTIFICATIVA);
        if ($resp->sucesso) {
            $xml = base64_decode($resp->xml);

            $st = new Standardize($xml);
            $std = $st->toStd();

            $ANO = $std->infInut->ano;
            $CODIGO_UF = $std->infInut->cUF;
            $AMBIENTE = $std->infInut->tpAmb == 2 ? 'H' : 'P';
            $ID_TAG = null;
            $VERSAO = $std->attributes->versao;
            $RET_VERSAO = $std->attributes->versao;
            $RET_CODIGO_STATUS = $std->infInut->cStat;
            $RET_MOTIVO_STATUS = $std->infInut->xMotivo;

            $RET_DATA_HORA_RECIBO = Carbon::parse($std->infInut->dhRecbto)->format('d/m/Y H:i:s');

            $RET_NUMERO_PROTOCOLO = $std->infInut->nProt;
            $XML_RETORNO = $xml;

            DB::statement("UPDATE FATU_NFE_INUTILIZACAO SET ANO = '{$ANO}',
                                 CODIGO_UF = '{$CODIGO_UF}',
                                 VERSAO = '{$VERSAO}',
                                 AMBIENTE = '{$AMBIENTE}',
                                 ID_TAG = '{$ID_TAG}',
                                 RET_VERSAO = '{$RET_VERSAO}',
                                 RET_CODIGO_STATUS = '{$RET_CODIGO_STATUS}',
                                 RET_MOTIVO_STATUS = '{$RET_MOTIVO_STATUS}',
                                 RET_DATA_HORA_RECIBO = '{$RET_DATA_HORA_RECIBO}',
                                 RET_NUMERO_PROTOCOLO = '{$RET_NUMERO_PROTOCOLO}',
                                 XML_RETORNO = '{$XML_RETORNO}'
                    WHERE FATU_NFE_INUTILIZACAO_ID = '{$this->fatu_nfe_inutilizacao_id}'");
        } else {
            DB::statement("UPDATE FATU_NFE_INUTILIZACAO SET RET_CODIGO_STATUS = '{$resp->codigo}',
                                 RET_MOTIVO_STATUS = '{$resp->mensagem}'
                    WHERE FATU_NFE_INUTILIZACAO_ID = '{$this->fatu_nfe_inutilizacao_id}'");
        }
        return response()->json([
            'sucesso' => $resp->sucesso,
        ]);
    }

    public function pdf(Request $request)
    {
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->nf_id = $request->nf_id;

        $this->nfe = FatuNf::select([
            'NFE_CHAVE_ACESSO',
            'XML_NFE_AUTORIZADA'
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NF_ID', $this->nf_id)
            ->first();
        $ym = '20' . substr($this->nfe->NFE_CHAVE_ACESSO, 2, 4);
        $pathPDF = "documents/nfe/emitidas/{$ym}/{$this->nfe->NFE_CHAVE_ACESSO}.pdf";
        if (Storage::exists($pathPDF)) {
            $pdf = Storage::get($pathPDF);
        } else {
            $xml = gzdecode(base64_decode($this->nfe->XML_NFE_AUTORIZADA));
            $danfe = new Danfe(str_replace('data:application/gzip;base64,', '', $xml));
            $pdf = $danfe->render();
        }
        return $pdf;
    }

    public function cce(Request $request)
    {
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->nf_id = $request->nf_id;
        $FATU_NFE_CCE_ID = $request->fatu_nfe_cce_id;

        $this->nfe = FatuNf::select([
            'NFE_CHAVE_ACESSO',
            'XML_NFE_AUTORIZADA'
        ])->where('EMPRESA_ID', $this->empresa_id)
            ->where('FILIAL_ID', $this->filial_id)
            ->where('FATU_NF_ID', $this->nf_id)
            ->first();
        $ym = '20' . substr($this->nfe->NFE_CHAVE_ACESSO, 2, 4);
        $pathPDF = "documents/nfe/emitidas/{$ym}/{$this->nf_id}-{$FATU_NFE_CCE_ID}.pdf";
        if (Storage::exists($pathPDF)) {
            $pdf = Storage::get($pathPDF);
        } else {
            $cce = FatuNfeCce::select('XML_DISTRIBUICAO')
                ->where('EMPRESA_ID', $this->empresa_id)
                ->where('FILIAL_ID', $this->filial_id)
                ->where('FATU_NF_ID', $this->nf_id)
                ->where('FATU_NFE_CCE_ID', $FATU_NFE_CCE_ID)
                ->first();
            $xml = gzdecode(base64_decode($this->nfe->XML_NFE_AUTORIZADA));
            $dom = new \DOMDocument('1.0', 'utf-8');
            $dom->loadXML(str_replace('data:application/gzip;base64,', '', $xml));
            $emit = $dom->getElementsByTagName("emit")->item(0);
            $enderEmit = $emit->getElementsByTagName("enderEmit")->item(0);
            $dacce = new Daevento(
                $cce->XML_DISTRIBUICAO,
                [
                    'razao' => $emit->getElementsByTagName("xNome")->item(0)->nodeValue,
                    'logradouro' => $enderEmit->getElementsByTagName("xLgr")->item(0)->nodeValue,
                    'numero' => $enderEmit->getElementsByTagName("nro")->item(0)->nodeValue,
                    'complemento' => !empty($enderEmit->getElementsByTagName("xCpl")->item(0)->nodeValue)
                        ? $enderEmit->getElementsByTagName("xCpl")->item(0)->nodeValue
                        : '',
                    'bairro' => $enderEmit->getElementsByTagName("xBairro")->item(0)->nodeValue,
                    'CEP' => $enderEmit->getElementsByTagName("CEP")->item(0)->nodeValue,
                    'municipio' => $enderEmit->getElementsByTagName("xMun")->item(0)->nodeValue,
                    'UF' => $enderEmit->getElementsByTagName("UF")->item(0)->nodeValue,
                    'telefone' => !empty($enderEmit->getElementsByTagName("fone")->item(0)->nodeValue)
                        ? $enderEmit->getElementsByTagName("fone")->item(0)->nodeValue
                        : '',
                    'email' => ''
                ]
            );
            $pdf = $dacce->render();
        }
        return $pdf;
    }

    public function zipNfe(Request $request)
    {
        // empresa, filial, data inicio e fim, cliente e cnpj
        $this->empresa_id = $request->empresa_id;
        $this->filial_id = $request->filial_id;
        $this->nf_id = $request->nf_id;

        $notas = FatuNf::select([
            'NFE_CHAVE_ACESSO',
            'XML_NFE_AUTORIZADA'
        ])->where('EMPRESA_ID', $this->empresa_id);

        if (!empty($this->filial_id)) {
            $notas->where('FILIAL_ID', $this->filial_id);
        }

        $notas = $notas->get();
    }
}
