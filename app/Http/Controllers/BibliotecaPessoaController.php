<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Biblioteca;
use App\Models\Pessoa;

class BibliotecaPessoaController extends Controller
{
    public function create(Biblioteca $biblioteca)
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO E OTIMIZAÇÃO:
        // O uso de 'whereDoesntHave' é semanticamente correto, pois gera uma subquery com a cláusula
        // 'NOT EXISTS' diretamente no SGBD, o que é altamente eficiente.
        // Contudo, a instrução original realizava um 'SELECT *' via ->get() sem limitação de escopo.
        //
        // REFATORAÇÃO DE BD2:
        // 1. Inclusão de projeção cirúrgica via select('id', 'name') para isolar o tráfego na rede,
        //    impedindo o carregamento de campos confidenciais e pesados (como senhas criptografadas).
        // 2. Substituição do ->get() por um limitador de escopo ou paginação para evitar sobrecarga de 
        //    memória (Memory Exhaustion) caso o volume de dados da tabela 'pessoas' seja massivo.
        // ==========================================================================================

        $pessoas = Pessoa::select('id', 'name')
            ->whereDoesntHave('bibliotecas', function ($query) use ($biblioteca) {
                $query->where('biblioteca_id', $biblioteca->id);
            })
            ->take(50)
            ->get();

        return view('bibliotecas.add_pessoa', compact('biblioteca', 'pessoas'));
    }

    public function store(Request $request, Biblioteca $biblioteca)
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - INTEGRIDADE REFERENCIAL E ATOMICIDADE:
        // A validação 'exists:pessoas,id' garante a consistência dos dados antes de submeter a query,
        // certificando que nenhuma restrição de chave estrangeira (FK) seja violada.
        // O método 'syncWithoutDetaching' gerencia a tabela associativa (pivô) eficientemente,
        // inserindo o registro apenas se o par ordenado (biblioteca_id, pessoa_id) não for duplicado,
        // mantendo a terceira forma normal (3FN) do modelo relacional estável e livre de redundâncias.
        // ==========================================================================================

        $request->validate([
            'pessoa_id' => 'required|exists:pessoas,id',
        ]);

        $pessoaId = $request->input('pessoa_id');

        $result = $biblioteca->pessoas()->syncWithoutDetaching([$pessoaId]);

        if (empty($result['attached'])) {
            return redirect()->route('bibliotecas.pessoas.create', ['biblioteca' => $biblioteca->id])
                ->with('error', 'Pessoa já está associada a esta biblioteca.');
        }

        return redirect()->route('bibliotecas.edit', ['id' => $biblioteca->id])
            ->with('message', 'Pessoa adicionada à biblioteca com sucesso.');
    }
}
