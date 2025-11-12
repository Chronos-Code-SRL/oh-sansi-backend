<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'ci',
        'phone_number',
        'genre',
        'roles_id',
        'profesion'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'profesion' => 'nullable|string|max:100|regex:/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\']+$/',
        ];
    }

    /**
     * Get custom validation messages for the model.
     *
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        return [
            'profesion.regex' => 'El campo profesión solo puede contener letras, espacios y apóstrofes.',
            'profesion.max' => 'El campo profesión no puede tener más de 100 caracteres.',
        ];
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

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'user_area_olympiads');
    }

    public function roles()
    {
        return $this->belongsToMany(Roles::class, 'user_roles', 'user_id', 'role_id');
    }
}
