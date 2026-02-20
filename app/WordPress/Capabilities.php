<?php

declare(strict_types=1);

namespace App\WordPress;

final class Capabilities
{
    public const VIEW_ALL_REQUESTS = 'cata_treco_view_all_requests';
    public const APPROVE_REQUESTS = 'cata_treco_approve_requests';
    public const ASSIGN_REQUESTS = 'cata_treco_assign_requests';
    public const FINISH_OWN_REQUESTS = 'cata_treco_finish_own_requests';
    public const CONFIGURE_WHATSAPP = 'cata_treco_configure_whatsapp';
    public const ACCESS_ADMIN_PANEL = 'cata_treco_access_admin_panel';
    public const ACCESS_EMPLOYEE_PANEL = 'cata_treco_access_employee_panel';

    /** @return string[] */
    public static function adminCapabilities(): array
    {
        return [
            self::VIEW_ALL_REQUESTS,
            self::APPROVE_REQUESTS,
            self::ASSIGN_REQUESTS,
            self::FINISH_OWN_REQUESTS,
            self::CONFIGURE_WHATSAPP,
            self::ACCESS_ADMIN_PANEL,
            self::ACCESS_EMPLOYEE_PANEL,
        ];
    }

    /** @return string[] */
    public static function employeeCapabilities(): array
    {
        return [
            self::FINISH_OWN_REQUESTS,
            self::ACCESS_EMPLOYEE_PANEL,
        ];
    }
}
