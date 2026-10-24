<?php

use App\Livewire\Project\Edit;
use App\Models\Environment;
use App\Models\InstanceSettings;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    InstanceSettings::updateOrCreate(['id' => 0]);
    $this->team = Team::factory()->create();
    $this->admin = User::factory()->create();
    $this->admin->teams()->attach($this->team, ['role' => 'admin']);
    $this->member = User::factory()->create();
    $this->member->teams()->attach($this->team, ['role' => 'member']);
    $this->project = Project::create([
        'uuid' => (string) Str::uuid(),
        'name' => 'Assigned Project',
        'team_id' => $this->team->id,
    ]);
});

it('lets a team admin assign a project to selected team members', function (): void {
    $this->actingAs($this->admin);
    session(['currentTeam' => $this->team]);

    Livewire::test(Edit::class, ['project_uuid' => $this->project->uuid])
        ->set('selectedMemberIds', [])
        ->call('saveMembers')
        ->assertDispatched('success');

    $this->actingAs($this->member);
    session(['currentTeam' => $this->team]);

    expect(Project::ownedByCurrentTeam()->whereKey($this->project->id)->exists())->toBeFalse()
        ->and(Environment::where('project_id', $this->project->id)->exists())->toBeFalse();
});

it('keeps team admins able to access projects without an explicit assignment', function (): void {
    $this->project->members()->detach($this->admin);
    $this->actingAs($this->admin);
    session(['currentTeam' => $this->team]);

    expect(Project::ownedByCurrentTeam()->whereKey($this->project->id)->exists())->toBeTrue();
});
