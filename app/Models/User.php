<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Relacionamento many-to-many com Bibliotecas.
     */
    public function bibliotecas()
    {
        return $this->belongsToMany(Biblioteca::class, 'biblioteca_user')->withPivot('role');
    }

    /**
     * Retorna a role do usuário em uma biblioteca específica.
     */
    public function roleInBiblioteca($biblioteca)
    {
        $pivot = $this->bibliotecas()->where('biblioteca_id', $biblioteca->id)->first()?->pivot;

        return $pivot?->role;
    }

    /**
     * Verifica se o usuário tem uma determinada role na biblioteca.
     */
    public function hasBibliotecaRole($biblioteca, string $role): bool
    {
        return $this->roleInBiblioteca($biblioteca) === $role;
    }

    /**
     * Verifica se o usuário é owner da biblioteca.
     */
    public function isOwnerOfBiblioteca($biblioteca): bool
    {
        return $this->hasBibliotecaRole($biblioteca, 'owner');
    }

    /**
     * Verifica se o usuário é admin da biblioteca.
     */
    public function isAdminOfBiblioteca($biblioteca): bool
    {
        return $this->hasBibliotecaRole($biblioteca, 'admin');
    }

    /**
     * Verifica se o usuário é editor da biblioteca.
     */
    public function isEditorOfBiblioteca($biblioteca): bool
    {
        return $this->hasBibliotecaRole($biblioteca, 'editor');
    }

    /**
     * Verifica se o usuário é viewer da biblioteca.
     */
    public function isViewerOfBiblioteca($biblioteca): bool
    {
        return $this->hasBibliotecaRole($biblioteca, 'viewer');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
