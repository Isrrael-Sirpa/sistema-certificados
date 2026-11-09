<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('certificates:create-admin {email : Correo institucional} {--name= : Nombre del administrador}')]
#[Description('Crea una cuenta de administración central con una contraseña ingresada en privado')]
class CreateCertificateAdministrator extends Command
{
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Ejecuta el comando en una terminal interactiva para ingresar la contraseña.');

            return self::FAILURE;
        }

        $role = Role::where('name', Role::ADMIN)->first();
        if (! $role) {
            $this->error('Carga los datos iniciales con php artisan db:seed antes de crear el administrador.');

            return self::FAILURE;
        }

        $email = strtolower(trim($this->argument('email')));
        $identity = Validator::make([
            'name' => trim($this->option('name') ?? $this->ask('Nombre del administrador', 'Administración central') ?? ''),
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($identity->fails()) {
            foreach ($identity->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña nueva (mínimo 12 caracteres)', false);
        $confirmation = $this->secret('Confirma la contraseña', false);

        if (! is_string($password) || mb_strlen($password) < 12 || strlen($password) > 72 || $password !== $confirmation) {
            $this->error('Las contraseñas deben coincidir, tener al menos 12 caracteres y no superar 72 bytes.');

            return self::FAILURE;
        }

        $user = User::firstOrCreate(['email' => $email], [
            'name' => $identity->validated()['name'],
            'password' => $password,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        if (! $user->wasRecentlyCreated) {
            $this->error('El correo ya está registrado.');

            return self::FAILURE;
        }

        $this->info('Cuenta de administración central creada.');

        return self::SUCCESS;
    }
}
