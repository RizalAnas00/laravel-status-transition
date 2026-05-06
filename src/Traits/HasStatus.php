<?php

namespace Rizalsaja\LaravelStatusTransition\Traits;

use Illuminate\Database\Eloquent\Builder;
use Rizalsaja\LaravelStatusTransition\Exceptions\InvalidStatusTransitionException;
use Rizalsaja\LaravelStatusTransition\Models\StatusHistory;

trait HasStatus
{
    public static function bootHasStatus(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getStatusColumn()})) {
                $model->{$model->getStatusColumn()} = $model->getInitialStatus();
            }
        });
    }


    // -------- Config --------- //
    /**
     * Get all allowed statuses for this model.
     * Override in model: protected $statuses = ['draft', 'published'];
     *
     * @return array<string>
     */
    protected function getAllowedStatuses(): array
    {
        return $this->statuses ?? config('status-flow.default_statuses', []);
    }

    /**
     * Get the allowed transition map.
     * Override in model to restrict transition flow.
     * Returns null if all transitions are allowed.
     *
     * @return array<string, array<string>>|null
     */
    protected function getAllowedTransitions(): ?array
    {
        return $this->transitions ?? null;
    }

    /**
     * Extract allowed status names from transitions map.
     * Handles both simple strings and callback arrays.
     *
     * @return array<string>
     */
    protected function getAllowedTransitionKeys(string $fromStatus): array
    {
        $transitions = $this->getAllowedTransitions();

        if ($transitions === null || ! isset($transitions[$fromStatus])) {
            return [];
        }

        $result = [];

        foreach ($transitions[$fromStatus] as $key => $value) {
            if (is_string($key)) {
                $result[] = $key;
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Get the column name used to store the status.
     * Override in model: protected $statusColumn = 'state';
     *
     * @return string
     */
    protected function getStatusColumn(): string
    {
        return $this->statusColumn ?? 'status';
    }

    /**
     * Get the initial status applied when a model is first created.
     *
     * @return string
     */
    protected function getInitialStatus(): string
    {
        return $this->initialStatus ?? $this->getAllowedStatuses()[0] ?? 'active';
    }

    /**
     * Get the config's value of record_history
     * if false, then StatusHistory will not record any histories
     * on default if there is no config, then status history will be recorded
     *
     * @return bool  
    */
    protected function shouldRecordHistory(): bool
    {
        return config('status-flow.record_history', true);
    }


    // -------- Core ---------- //
    /**
     * Transition the model to a new status.
     * Validates against allowed statuses and transition map before saving.
     *
     * @param  string       $newStatus
     * @param  string|null  $reason
     * @return static
     *
     * @throws \InvalidArgumentException
     * @throws InvalidStatusTransitionException
     */
    public function transitionTo(string $newStatus, ?string $reason = null): static
    {
        $currentStatus = $this->getCurrentStatus();

        if (! in_array($newStatus, $this->getAllowedStatuses())) {
            throw new \InvalidArgumentException(
                "Status [{$newStatus}] is not a valid status."
            );
        }

        $transitions = $this->getAllowedTransitions();

        if ($transitions !== null) {
            $allowed = $this->getAllowedTransitionKeys($currentStatus);

            if (! in_array($newStatus, $allowed)) {
                throw new InvalidStatusTransitionException(
                    $currentStatus, $newStatus, $allowed
                );
            }
        }

        $callbacks = $this->getCallbacksForTransition($currentStatus, $newStatus);

        if (isset($callbacks['before'])) {
            $this->executeCallback($callbacks['before']);
        }

        $this->{$this->getStatusColumn()} = $newStatus;
        $this->save();

        if (isset($callbacks['after'])) {
            $this->executeCallback($callbacks['after']);
        }

        if (! $this->shouldRecordHistory()) {
            return $this;
        }

        StatusHistory::create([
            'statusable_type' => get_class($this),
            'statusable_id'   => $this->getKey(),
            'from'            => $currentStatus,
            'to'              => $newStatus,
            'changed_by'      => auth()->id(),
            'reason'          => $reason,
        ]);

        return $this;
    }

    // ----- Helpers ------- //
    /**
     * Get the current status value.
     *
     * @return string
     */
    public function getCurrentStatus(): string
    {
        return $this->{$this->getStatusColumn()} ?? $this->getInitialStatus();
    }

    /**
     * Check if the model is currently at the given status.
     *
     * @param  string  $status
     * @return bool
     */
    public function isStatus(string $status): bool
    {
        return $this->getCurrentStatus() === $status;
    }

    /**
     * Check if the model is NOT at the given status.
     *
     * @param  string  $status
     * @return bool
     */
    public function isNotStatus(string $status): bool
    {
        return ! $this->isStatus($status);
    }

    /**
     * Check whether a transition to the given status is currently allowed.
     *
     * @param  string  $status
     * @return bool
     */
    public function canTransitionTo(string $status): bool
    {
        $transitions = $this->getAllowedTransitions();

        if ($transitions === null) {
            return in_array($status, $this->getAllowedStatuses());
        }

        return in_array($status, $transitions[$this->getCurrentStatus()] ?? []);
    }

    /**
     * Get all statuses the model can transition to from its current status.
     *
     * @return array<string>
     */
    public function availableTransitions(): array
    {
        $transitions = $this->getAllowedTransitions();

        if ($transitions === null) {
            return array_values(array_filter(
                $this->getAllowedStatuses(),
                fn($s) => $s !== $this->getCurrentStatus()
            ));
        }

        return $this->getAllowedTransitionKeys($this->getCurrentStatus());
    }

    /**
     * Get all transitions from a given status
     * @param string $status
     * @return string[]
     */
    public function getStatusTransitions(string $status): array
    {
        $transition = $this->getAllowedTransitions();

        return $transition[$status] ?? [];
    }

    /**
     * Get before/after callbacks for a specific transition.
     * Handles both: 'status' => [] (with callbacks) and 'status' (plain string)
     *
     * @return array<string, mixed>
     */
    protected function getCallbacksForTransition(string $fromStatus, string $toStatus): array
    {
        $transitions = $this->getAllowedTransitions();

        if ($transitions === null) {
            return [];
        }

        $target = $transitions[$fromStatus][$toStatus] ?? null;

        if (is_string($target)) {
            return [];
        }

        return is_array($target) ? $target : [];
    }

    /**
     * Execute a callback, supports method name string or callable.
     *
     * @param  string|callable  $callback
     * @return void
     */
    protected function executeCallback(string|callable $callback): void
    {
        if (is_callable($callback)) {
            $callback($this);
        } elseif (method_exists($this, $callback)) {
            $this->{$callback}();
        }
    }

    // ------- Relations -------- //
    /**
     * Get all status history records, ordered by latest.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function statusHistory()
    {
        return $this->morphMany(StatusHistory::class, 'statusable')
            ->orderBy('id', 'desc');
    }

    /**
     * Get the most recent status history record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function latestStatus()
    {
        return $this->morphOne(StatusHistory::class, 'statusable')
            ->orderBy('id', 'desc');
    }


    // ------ Scopes ------- //
    /**
     * Filter models by an exact status value.
     *
     * @param  Builder  $query
     * @param  string   $status
     * @return Builder
     */
    public function scopeWhereStatus(Builder $query, string $status): Builder
    {
        return $query->where($this->getStatusColumn(), $status);
    }

    /**
     * Filter models that do not have the given status.
     *
     * @param  Builder  $query
     * @param  string   $status
     * @return Builder
     */
    public function scopeWhereNotStatus(Builder $query, string $status): Builder
    {
        return $query->where($this->getStatusColumn(), '!=', $status);
    }

    /**
     * Filter models whose status is within the given list.
     *
     * @param  Builder        $query
     * @param  array<string>  $statuses
     * @return Builder
     */
    public function scopeWhereStatusIn(Builder $query, array $statuses): Builder
    {
        return $query->whereIn($this->getStatusColumn(), $statuses);
    }
}