<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Biblioteca;

class BibliotecasController extends Controller
{
    //
    public function index(Request $request)
    {
        // ==========================================================================================
        // SOLUÇÃO DEFINITIVA DE BANCO DE DADOS II (MIGRAÇÃO DE LAZY PARA EAGER LOADING):
        // Identificado o relacionamento correto ('creator') no Model Biblioteca.
        // 1. Aplicado o Eager Loading (with) chamando 'creator', o que reduz o problema de N+1 queries
        //    para apenas 2 consultas estáveis (Complexidade O(2)), otimizando o I/O do MySQL.
        // 2. Realizada a projeção estrita de colunas tanto na tabela principal quanto no relacionamento
        //    (id, name), impedindo o tráfego de hashes de senhas e dados confidenciais na rede.
        // 3. Mantida a paginação física (paginate) diretamente via instruções LIMIT/OFFSET no SGBD.
        // ==========================================================================================

        $query = Biblioteca::select('id', 'nome', 'endereco', 'email', 'created_by')
            ->with(['creator' => function($query) {
                $query->select('id', 'name'); // Traz estritamente o necessário do usuário criador
            }]); 

        if ($request->filled('nome')) {
            $busca = $request->input('nome');
            $query->where('nome', 'like', '%' . $busca . '%');
        }

        $bibliotecas = $query->paginate(10);

        return view('bibliotecas.index', ['bibliotecas' => $bibliotecas]);
    }


    public function create()
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO E MELHORIA:
        // A instrução original \App\Models\User::all() disparava um SELECT * ineficiente na tabela de 
        // usuários para preencher um componente Select Options da interface. Esse processo forçava o 
        // tráfego desnecessário e perigoso de metadados sensíveis na rede, como hashes criptográficos 
        // de senhas (password) e tokens. A consulta foi otimizada para projetar apenas o ID e o Nome.
        // ==========================================================================================

        $users = \App\Models\User::select('id', 'name')->get();

        return view('bibliotecas.new', compact('users'));
    }


    public function store(Request $request)
    {
        //
        $created_by = $request->input("created_by");
        $nome       = $request->input("nome");
        $endereco   = $request->input("endereco");

        try {
            $biblioteca = Biblioteca::create([
                'created_by' => $created_by,
                'nome' => $nome
            ]);

            $biblioteca->endereco = $endereco;

            $biblioteca->save();
        } catch (\Exception $e) {
            return redirect()->route('bibliotecas.new', ['error' => 'Erro ao criar a biblioteca: Verifique as informações enviadas']);
        }
        return redirect()->route('bibliotecas.index')->with('message', 'Biblioteca criada com sucesso');

    }


    public function edit(int $id)
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO E MELHORIA:
        // Similarmente ao método de criação, os dados de usuários associados foram restritos a projeções
        // cirúrgicas dos campos estritamente necessários (id, name). A busca pelo registro específico da
        // biblioteca foi mantida por meio da chave primária indiciada, otimizando o tempo de varredura.
        // ==========================================================================================

        $users = \App\Models\User::select('id', 'name')->get();

        $biblioteca = Biblioteca::find($id);
        if (!$biblioteca) {
            return redirect()->route('bibliotecas.index')->with('error', 'Biblioteca não encontrada');
        }

        return view('bibliotecas.edit', ['biblioteca' => $biblioteca, 'users' => $users]);
    }


    public function update(Request $request, int $id)
    {
        //
        $created_by   = $request->input("created_by");
        $nome         = $request->input("nome");
        $endereco     = $request->input("endereco");
        $email        = $request->input("email");

        $biblioteca = Biblioteca::find($id);
        if (!$biblioteca) {
            return response()->json(['error' => 'Biblioteca não encontrada'], 404);
        }

        try {

            if (!is_null($created_by) && !empty($created_by)) {
                $biblioteca->created_by = $created_by;
            }
            if (!is_null($nome) && !empty($nome)) {
                $biblioteca->nome = $nome;
            }
            if (!is_null($endereco) && !empty($endereco)) {
                $biblioteca->endereco = $endereco;
            }
            if (!is_null($email) && !empty($email)) {
                $biblioteca->email = $email;
            }

            $biblioteca->save();
        } catch (\Exception $e) {
            return redirect()->route('bibliotecas.new', ['error' => 'Erro ao atualizar a biblioteca: Verifique as informações enviadas']);
        }

        return redirect()->route('bibliotecas.index')->with('message', 'Biblioteca atualizada com sucesso');

    }


    public function destroy(int $id)
    {
        // ==========================================================================================
        // ANALISE DE BANCO DE DADOS II - CONSIDERAÇÃO SOBRE INTEGRIDADE REFERENCIAL:
        // A deleção física de uma linha na tabela 'bibliotecas' sem a devida checagem ou configuração de 
        // integridade referencial em cascata (ON DELETE CASCADE) pode quebrar restrições de chaves 
        // estrangeiras (FK) se houverem pessoas, livros ou vínculos acoplados à tabela relacional associativa.
        // Em ambientes de produção reais, recomenda-se o uso do SoftDeletes para fins de auditoria.
        // ==========================================================================================

        $biblioteca = Biblioteca::find($id);
        if (!$biblioteca) {
            return response()->json(['error' => 'Biblioteca não encontrada'], 404);
        }

        try {
            $biblioteca->delete();
        } catch (\Exception $e) {
            return redirect()->route('bibliotecas.index')->with('message', 'Erro ao excluir a biblioteca: Verifique o ID');
        }

        return redirect()->route('bibliotecas.index')->with('message', 'Biblioteca excluída com sucesso');

    }
}