<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user tenant assignments and roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all(['name', 'email', 'tenant_id']);

        $this->info('User assignments:');
        $this->info('================');

        foreach ($users as $user) {
            $tenantId = $user->tenant_id ? "tenant_id: {$user->tenant_id}" : "tenant_id: NULL (Super Admin)";
            $this->line("- {$user->name} ({$user->email}) - {$tenantId}");
        }

        $this->newLine();
        $this->info('Tenant Admin Check:');
        $this->info('==================');

        $tenantAdmin = User::where('email', 'tenant-admin@adpro.test')->first();
        if ($tenantAdmin) {
            $this->line("Tenant Admin user:");
            $this->line("- tenant_id: " . ($tenantAdmin->tenant_id ?? 'NULL'));
            $this->line("- isSuperAdmin(): " . ($tenantAdmin->isSuperAdmin() ? 'YES' : 'NO'));
            $this->line("- memberships count: " . $tenantAdmin->memberships()->count());
        } else {
            $this->error("Tenant Admin user not found!");
        }

        return 0;
    }
}
