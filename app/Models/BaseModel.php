<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('project_membership', function (Builder $builder): void {
            $model = $builder->getModel();
            $user = auth()->user();

            $hasEnvironment = method_exists($model, 'environment');
            $hasProject = method_exists($model, 'project');

            if (! $user || (! $hasEnvironment && ! $hasProject)) {
                return;
            }

            $teamId = currentTeam()?->id;
            if ($teamId === null) {
                return;
            }

            $projectRelation = $hasEnvironment ? 'environment.project' : 'project';

            $builder->whereHas($projectRelation, function (Builder $projects) use ($user, $teamId): void {
                $projects->where('team_id', $teamId)
                    ->where(function (Builder $access) use ($user, $teamId): void {
                        $access->whereHas('members', fn (Builder $members) => $members->whereKey($user->id));

                        if ($user->isAdminOfTeam($teamId)) {
                            $access->orWhere('team_id', $teamId);
                        }
                    });
            });
        });

        static::creating(function (Model $model) {
            // Generate a UUID if one isn't set
            if (! $model->uuid) {
                $model->uuid = new_public_id();
            }
        });
    }

    public function sanitizedName(): Attribute
    {
        return new Attribute(
            get: fn () => sanitize_string($this->getRawOriginal('name')),
        );
    }

    public function image(): Attribute
    {
        return new Attribute(
            get: fn () => sanitize_string($this->getRawOriginal('image')),
        );
    }
}
