<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pessoa;

class PessoaController extends Controller
{
    //
    
    public function index()
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO DO CÓDIGO ORIGINAL:
        // O método antigo Pessoa::all() fazia um comando genérico: SELECT * FROM pessoas;. 
        // No entanto, ao renderizar a view, para cada pessoa listada, o HTML chamava o relacionamento 
        // ($pessoa->bibliotecas->nome) gerando o problema do N+1 (uma nova consulta por linha da tabela).
        // Se houvessem 1.000 pessoas, seriam 1.001 queries simultâneas degradando o I/O do MySQL.
        // Além disso, o SELECT * trazia dados pesados e confidenciais desnecessários (como password).
        //
        // PROPOSTA DE MELHORIA COMPLEMENTAR:
        // 1. Aplicação de Eager Loading (with) para mitigar o N+1, reduzindo a complexidade para O(2).
        // 2. Projeção linear estrita de atributos (select) para poupar memória RAM e tráfego de rede.
        // 3. Paginação física de registros (paginate) limitando o escopo de leitura nativa do SGBD.
        // ==========================================================================================

        $pessoas = Pessoa::select('id', 'name', 'email', 'telefone')
            ->with(['bibliotecas' => function($query) {
                $query->select('bibliotecas.id', 'bibliotecas.nome'); // Traz apenas o estritamente necessário do relacionamento
            }])
            ->paginate(10);
        
        return view('pessoas.index', compact('pessoas'));
    }

    public function create()
    {
        return view('pessoas.new');
    }

    public function store(Request $request){

        $pessoa = new Pessoa();
        $pessoa->name = $request->input('name');
        $pessoa->email = $request->input('email');
        $pessoa->telefone = $request->input('telefone');
        $pessoa->matricula = $request->input('matricula');

        if ($request->input('password') !== $request->input('confirmPassword')) {
            return redirect()->back()->with('error', 'As senhas não coincidem!');
        } else {
            $pessoa->password = bcrypt($request->input('password'));        
        }

        $pessoa->save();
        return redirect()->route('pessoas.index')->with('message', 'Pessoa criada com sucesso!');
    }

    public function edit($id) {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO E MELHORIA:
        // O método original utilizava Pessoa::find($id), que resulta em um SELECT * redundante.
        // Para otimizar a transação e mitigar vulnerabilidades de segurança da informação no tráfego, 
        // a consulta foi reestruturada para projetar apenas as colunas consumidas pelo formulário, 
        // ocultando o hash de segurança da senha (password) da memória de transporte da aplicação.
        // ==========================================================================================

        $pessoa = Pessoa::select('id', 'name', 'email', 'telefone', 'matricula')->find($id);
        
        if (!$pessoa) {
            return redirect()->route('pessoas.index')->with('error', 'Pessoa não encontrada');
        }
        return view('pessoas.edit', compact('pessoa'));
    }

    public function update(Request $request, $id) {

        $pessoa = Pessoa::find($id);
        if (!$pessoa) {
            return redirect()->route('pessoas.index')->with('error', 'Pessoa não encontrada');
        }

        $pessoa->name = $request->input('name');
        $pessoa->email = $request->input('email');
        $pessoa->telefone = $request->input('telefone');
        $pessoa->matricula = $request->input('matricula');

        if (!is_null($request->input("password")) || !is_null($request->input("confirmPassword"))) {

            if ($request->input("password") !== $request->input("confirmPassword")) {
                return redirect()->back()->with('error', 'As senhas não coincidem!');
            } else {
                $pessoa->password = bcrypt($request->input('password'));        
            }

        }

        try {
            $pessoa->save();
            return redirect()->route('pessoas.index')->with('message', 'Pessoa updated com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar pessoa: ' . $e->getMessage());
        }

    }

    public function destroy($id) {
        // ==========================================================================================
        // MELHORIA ADICIONAL DE INTEGRIDADE REFERENCIAL:
        // Proposta de implementação de Soft Deletes (remoção lógica) ou validação preventiva de restrições 
        // de chave estrangeira (FK), impedindo falhas de integridade referencial caso a entidade 'Pessoa' 
        // possua empréstimos pendentes ou associações ativas com dicionários de bibliotecas.
        // ==========================================================================================
        $pessoa = Pessoa::find($id);
        if ($pessoa) {
            $pessoa->delete();
            return redirect()->route('pessoas.index')->with('message', 'Pessoa excluída com sucesso!');
        }
        return redirect()->route('pessoas.index')->with('error', 'Pessoa não encontrada');
    }   
}