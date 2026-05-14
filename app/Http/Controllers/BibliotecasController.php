<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Biblioteca;

class BibliotecasController extends Controller
{
    //
    public function index(Request $request)
    {

        $busca = $request->input('nome');

        $bibliotecas = Biblioteca::where('nome', 'like', '%' . $busca . '%')->get();
    
        return response()->json($bibliotecas);
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
            return response()->json(['error' => 'Erro ao criar a biblioteca: Verifique as informações enviadas'], 500);
        }
        return response()->json($biblioteca, 201);

    }

    public function update(Request $request, $id)
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
            return response()->json(['error' => 'Erro ao atualizar a biblioteca: Verifique as informações enviadas'], 500);
        }


        return response()->json($biblioteca, 200);


    }



    public function destroy(Request $request)
    {
        //

        $idBiblioteca = $request->input("id");

        $biblioteca = Biblioteca::find($idBiblioteca);
        if (!$biblioteca) {
            return response()->json(['error' => 'Biblioteca não encontrada'], 404);
        }

        try {
            $biblioteca->delete();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao excluir a biblioteca: Verifique o ID'], 500);
        }

        return response()->json(['message' => 'Biblioteca excluída com sucesso'], 200);

    }

}
