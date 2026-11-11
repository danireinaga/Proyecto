<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class ProvisionAdminAccounts extends Command
{
    protected $signature = 'app:provision-admin-accounts
        {--force : Allow provisioning in production}';

    protected $description = 'Create the initial administrator account once';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->components->error('En producción debes ejecutar el comando con --force después de verificar el entorno y la base de datos.');

            return self::FAILURE;
        }

        $credentials = [
            'admin_email' => mb_strtolower(trim((string) config('auth.administrator.email'))),
            'admin_password' => (string) config('auth.administrator.password'),
        ];

        $validator = Validator::make($credentials, [
            'admin_email' => ['required', 'string', 'email', 'max:255'],
            'admin_password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($credentials): void {
                $this->laravel->make(RolePermissionSeeder::class)->run();

                $this->provisionAccount(
                    User::ROL_ADMIN,
                    $credentials['admin_email'],
                    $credentials['admin_password'],
                    'Administrador'
                );
            });
        } catch (DomainException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function provisionAccount(string $roleName, string $email, string $password, string $name): void
    {
        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->firstOrFail();

        $roleUsers = User::role($roleName)->get();

        if ($roleUsers->count() > 1) {
            throw new DomainException("Hay más de una cuenta con el rol protegido {$roleName}; requiere revisión manual.");
        }

        if ($roleUsers->isNotEmpty()) {
            $this->components->info("La cuenta {$roleName} ya existe; no se modificó.");

            return;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            throw new DomainException("El correo configurado para {$roleName} ya pertenece a otra cuenta; no se asignaron permisos.");
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->assignRole($role);

        $this->components->info("Cuenta {$roleName} creada.");
    }
}
