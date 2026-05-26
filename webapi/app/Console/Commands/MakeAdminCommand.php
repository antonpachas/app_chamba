<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminCommand extends Command
{
    protected $signature = 'chamba:make-admin
        {--email= : Correo del admin}
        {--name= : Nombre completo}
        {--phone= : Teléfono (opcional)}
        {--password= : Contraseña (mínimo 8 caracteres). Si se omite, se preguntará.}
        {--promote : Si el correo ya existe, promueve al usuario a admin en vez de fallar.}
        {--list : Solo lista los usuarios existentes y termina.}';

    protected $description = 'Crea un usuario admin para Chamba (o promueve uno existente).';

    public function handle(): int
    {
        if ($this->option('list')) {
            $rows = User::query()
                ->select('id', 'full_name', 'email', 'role', 'status')
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                $this->warn('No hay usuarios.');
                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Email', 'Rol', 'Estado', 'Nombre'],
                $rows->map(fn ($u) => [$u->id, $u->email, $u->role, $u->status, $u->full_name])->toArray(),
            );
            return self::SUCCESS;
        }

        $email = $this->option('email') ?: $this->ask('Correo del admin');
        $email = strtolower(trim((string) $email));

        $existing = User::query()->where('email', $email)->first();

        if ($existing && ! $this->option('promote')) {
            $this->error("Ya existe un usuario con ese correo (id={$existing->id}, role={$existing->role}).");
            $this->line('Si quieres convertir a ese usuario en admin, vuelve a ejecutar con --promote.');
            return self::FAILURE;
        }

        if ($existing && $this->option('promote')) {
            $existing->role = 'admin';
            $existing->status = 'activo';
            $existing->save();

            if ($pwd = $this->option('password')) {
                $existing->password_hash = Hash::make($pwd);
                $existing->save();
                $this->info('Contraseña actualizada.');
            }

            $this->info("Usuario #{$existing->id} ({$existing->email}) ahora es ADMIN.");
            return self::SUCCESS;
        }

        $name = $this->option('name') ?: $this->ask('Nombre completo');
        $phone = $this->option('phone');
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        $payload = [
            'full_name' => trim((string) $name),
            'email' => $email,
            'phone' => $phone ? trim((string) $phone) : null,
            'password' => (string) $password,
        ];

        $rules = [
            'full_name' => 'required|string|min:2|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|max:255',
        ];

        $v = Validator::make($payload, $rules);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }
            return self::FAILURE;
        }

        $user = new User();
        $user->full_name = $payload['full_name'];
        $user->email = $payload['email'];
        $user->phone = $payload['phone'];
        $user->password_hash = Hash::make($payload['password']);
        $user->role = 'admin';
        $user->status = 'activo';
        $user->save();

        $this->info("Admin creado: id={$user->id}, email={$user->email}");
        $this->line('Ya puede entrar en /app/acceder eligiendo cualquier rol (admin pasa el filtro de roles).');

        return self::SUCCESS;
    }
}
