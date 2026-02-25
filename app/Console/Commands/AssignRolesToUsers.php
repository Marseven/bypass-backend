<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignRolesToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign Spatie roles to existing users based on their role field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Assignation des rôles Spatie aux utilisateurs existants...');
        
        $roleMapping = [
            'user' => 'user',
            'operator' => 'user',
            'supervisor' => 'supervisor',
            'director' => 'director',
            'administrator' => 'administrator',
            'admin' => 'administrator',
        ];
        
        $users = User::all();
        $assigned = 0;
        
        foreach ($users as $user) {
            $oldRole = strtolower($user->role ?? 'user');
            $spatieRoleName = $roleMapping[$oldRole] ?? 'user';
            
            $role = Role::where('name', $spatieRoleName)->first();
            
            if ($role) {
                // Retirer tous les rôles existants
                $user->syncRoles([]);
                
                // Assigner le nouveau rôle
                $user->assignRole($role);
                $assigned++;
                
                $this->line("✓ Utilisateur {$user->full_name} ({$user->email}) : rôle '{$spatieRoleName}' assigné");
            } else {
                $this->warn("⚠ Rôle '{$spatieRoleName}' non trouvé pour l'utilisateur {$user->full_name}");
            }
        }
        
        $this->info("\n✓ {$assigned} utilisateur(s) ont reçu un rôle Spatie.");
        
        return Command::SUCCESS;
    }
}
