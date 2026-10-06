<?php

namespace App\Support;

/**
 * The fixed set of permissions the app actually checks. Roles and who holds
 * them are freely editable by an admin (see App\Livewire\Roles), but the
 * permission keys themselves are not — each one corresponds to a specific
 * abort_unless()/@can() check in the code, so a permission with no matching
 * check would do nothing, and the admin UI has no way to invent one.
 */
class Permissions
{
    public const ALL = [
        'manage-members' => 'Add and edit member companies, record their payments, and manage their products and brochures',
        'delete-payments' => 'Remove a recorded payment',
        'manage-membership-status' => 'Cancel or reinstate a membership',
        'manage-business-types' => 'Manage the list of business types (industries)',
        'manage-member-types' => 'Manage membership tiers and their monthly fees',
        'manage-events' => 'Create, edit and delete events',
        'send-announcements' => 'Compose and send email announcements to members',
        'manage-staff' => 'Add, remove and assign roles to staff accounts',
        'manage-roles' => 'Create roles and choose what each one can do',
    ];

    /** What the original hardcoded 'staff' role could do, kept as the default for new roles. */
    public const STAFF_DEFAULTS = [
        'manage-members', 'manage-business-types', 'manage-events', 'send-announcements',
    ];
}
