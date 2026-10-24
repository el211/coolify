<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['project_id', 'user_id']);
        });

        DB::table('projects')->join('team_user', 'projects.team_id', '=', 'team_user.team_id')
            ->select('projects.id as project_id', 'team_user.user_id')
            ->orderBy('projects.id')
            ->chunk(500, function ($rows): void {
                DB::table('project_user')->insertOrIgnore($rows->map(fn ($row): array => [
                    'project_id' => $row->project_id,
                    'user_id' => $row->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
