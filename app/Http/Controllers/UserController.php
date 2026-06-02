<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO DO CÓDIGO ORIGINAL:
        // O método User::all() executava um "SELECT * FROM users", forçando o SGBD a extrair e 
        // trafegar o hash de senhas criptografadas (password) e tokens de sessão de todos os usuários 
        // do sistema para a memória da aplicação. Além do gargalo de rede e memória RAM, isso gera uma 
        // vulnerabilidade de segurança (exposição desnecessária de dados confidenciais na camada de memória).
        //
        // REFATORAÇÃO DE BD2:
        // 1. Uso de select() para projetar exclusivamente as colunas exibidas na listagem (id, name, email).
        // 2. Aplicação de paginação (paginate) para limitar fisicamente as linhas processadas no MySQL.
        // ==========================================================================================

        $users = User::select('id', 'name', 'email')->paginate(10);
        
        return view('users.index', compact('users'));
    }

    public function show($id)
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO E OTIMIZAÇÃO:
        // A busca pontual por chave primária indiciada (find) é eficiente no MySQL, mas o uso padrão 
        // continuava trazendo a coluna 'password'. Restringimos a busca aos atributos de exibição do perfil.
        // ==========================================================================================

        $user = User::select('id', 'name', 'email', 'created_at')->find($id);
        
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'Usuário não encontrado');
        }
        return view('users.show', compact('user'));
    }

    public function create()
    {
        return view('users.new');
    }

    public function store(Request $request)
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - CONSIDERAÇÕES DE ESCRITA:
        // O método realiza uma operação de INSERT baseada em atribuição em massa. Do ponto de vista do 
        // banco, a operação é atômica e segura. O e-mail deve possuir uma restrição de unicidade (UNIQUE KEY) 
        // no banco de dados para evitar redundância e inconsistência na indexação.
        // ==========================================================================================

        $name = $request->input('name');
        $email = $request->input('email');
        $password = bcrypt($request->input('password'));

        try {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password
            ]);
        } catch (\Exception $e) {
            return redirect()->route('users.create')->with('error', 'Erro ao criar o usuário: Verifique as informações enviadas');
        }

        return redirect()->route('users.index')->with('message', 'Usuário criado com sucesso');
    }

    public function edit($id)
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - DIAGNÓSTICO E OTIMIZAÇÃO:
        // Assim como no método 'show', isolamos apenas as colunas necessárias para preencher o formulário 
        // de edição, expurgando o hash da senha da árvore de carregamento de dados (Data Hydration).
        // ==========================================================================================

        $user = User::select('id', 'name', 'email', 'role')->find($id);
        
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'Usuário não encontrado');
        }
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'Usuário não encontrado');
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $role = $request->input('role');

        try {
            $user->name = $name;
            $user->email = $email;
            $user->role = $role;
            $user->save(); 
        } catch (\Exception $e) {
            return redirect()->route('users.edit', ['id' => $id])->with('error', 'Erro ao atualizar o usuário: Verifique as informações enviadas');
        }

        return redirect()->route('users.index')->with('message', 'Usuário atualizado com sucesso');
    }

    public function destroy($id)
    {
        // ==========================================================================================
        // ANÁLISE DE BANCO DE DADOS II - INTEGRIDADE REFERENCIAL (CHAVE ESTRANGEIRA):
        // O 'User' está associado à tabela 'bibliotecas' pelo campo 'created_by'. Tentar executar um 
        // DELETE físico em um usuário ativo disparará uma falha de restrição de integridade (FK Constraint Violation).
        // ==========================================================================================

        $user = User::find($id);
        if (!$user) {
            return redirect()->route('users.index')->with('error', 'Usuário não encontrado');
        }

        try {
            $user->delete();
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Erro ao excluir o usuário: Restrições de integridade violadas.');
        }

        return redirect()->route('users.index')->with('message', 'Usuário excluído com sucesso');
    }
}