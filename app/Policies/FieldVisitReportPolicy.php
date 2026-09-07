<?php

namespace App\Policies;

use App\Models\FieldVisitReport;
use App\Models\User;

class FieldVisitReportPolicy
{
    /**
     * Row-level scoping (Volunteers only see their own reports) is handled
     * in FieldVisitReportResource::getEloquentQuery().
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, FieldVisitReport $report): bool
    {
        return $user->hasAnyRole(['verification_admin', 'executive_admin', 'super_admin'])
            || $report->volunteer_id === $user->id;
    }
}
