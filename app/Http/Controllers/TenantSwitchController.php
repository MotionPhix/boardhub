<?php

namespace App\Http\Controllers;

/**
 * DEPRECATED: This controller has been consolidated into OrganizationController
 * All functionality has been moved to provide better organization and consistency.
 * This file can be safely deleted once all references are updated.
 *
 * @deprecated Use OrganizationController instead
 * @see App\Http\Controllers\OrganizationController
 */
class TenantSwitchController extends Controller
{
    public function __construct()
    {
        throw new \Exception('TenantSwitchController is deprecated. Use OrganizationController instead.');
    }
}