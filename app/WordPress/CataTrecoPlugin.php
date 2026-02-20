<?php

declare(strict_types=1);

namespace App\WordPress;

use App\Controllers\AdminController;
use App\Controllers\EmployeeController;

final class CataTrecoPlugin
{
    public static function init(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        add_action('admin_menu', [self::class, 'registerMenus']);
        add_action('rest_api_init', [self::class, 'registerRestRoutes']);
        add_action('wp_ajax_cata_treco_admin_requests', [self::class, 'ajaxAdminRequests']);
        add_action('wp_ajax_cata_treco_admin_update', [self::class, 'ajaxAdminUpdate']);
        add_action('wp_ajax_cata_treco_employee_start', [self::class, 'ajaxEmployeeStart']);
        add_action('wp_ajax_cata_treco_employee_finish', [self::class, 'ajaxEmployeeFinish']);
    }

    public static function activate(): void
    {
        if (!function_exists('add_role')) {
            return;
        }

        remove_role('cata_treco_administrador');
        remove_role('cata_treco_funcionario');

        add_role(
            'cata_treco_administrador',
            'Cata Treco Administrador',
            self::mapCaps(Capabilities::adminCapabilities())
        );

        add_role(
            'cata_treco_funcionario',
            'Cata Treco Funcionário',
            self::mapCaps(Capabilities::employeeCapabilities())
        );
    }

    public static function registerMenus(): void
    {
        add_menu_page(
            'Cata Treco Admin',
            'Cata Treco',
            Capabilities::ACCESS_ADMIN_PANEL,
            'cata-treco-admin',
            [self::class, 'renderAdminPanel'],
            'dashicons-trash'
        );

        add_submenu_page(
            'cata-treco-admin',
            'Painel do Funcionário',
            'Funcionário',
            Capabilities::ACCESS_EMPLOYEE_PANEL,
            'cata-treco-funcionario',
            [self::class, 'renderEmployeePanel']
        );
    }

    public static function renderAdminPanel(): void
    {
        if (!current_user_can(Capabilities::ACCESS_ADMIN_PANEL)) {
            wp_die('Você não tem permissão para acessar este painel.');
        }

        (new AdminController())->dashboard();
    }

    public static function renderEmployeePanel(): void
    {
        if (!current_user_can(Capabilities::ACCESS_EMPLOYEE_PANEL)) {
            wp_die('Você não tem permissão para acessar este painel.');
        }

        (new EmployeeController())->dashboard();
    }

    public static function registerRestRoutes(): void
    {
        register_rest_route('cata-treco/v1', '/admin/requests', [
            'methods' => 'GET',
            'callback' => [self::class, 'restAdminRequests'],
            'permission_callback' => static fn () => current_user_can(Capabilities::VIEW_ALL_REQUESTS),
        ]);

        register_rest_route('cata-treco/v1', '/admin/update', [
            'methods' => 'POST',
            'callback' => [self::class, 'restAdminUpdate'],
            'permission_callback' => static fn () => current_user_can(Capabilities::APPROVE_REQUESTS),
        ]);

        register_rest_route('cata-treco/v1', '/employee/start', [
            'methods' => 'POST',
            'callback' => [self::class, 'restEmployeeStart'],
            'permission_callback' => static fn () => current_user_can(Capabilities::ACCESS_EMPLOYEE_PANEL),
        ]);

        register_rest_route('cata-treco/v1', '/employee/finish', [
            'methods' => 'POST',
            'callback' => [self::class, 'restEmployeeFinish'],
            'permission_callback' => static fn () => current_user_can(Capabilities::FINISH_OWN_REQUESTS),
        ]);
    }

    public static function restAdminRequests()
    {
        ob_start();
        (new AdminController())->requests();
        return rest_ensure_response(json_decode((string) ob_get_clean(), true));
    }

    public static function restAdminUpdate()
    {
        ob_start();
        (new AdminController())->update();
        return rest_ensure_response(json_decode((string) ob_get_clean(), true));
    }

    public static function restEmployeeStart()
    {
        ob_start();
        (new EmployeeController())->start();
        return rest_ensure_response(json_decode((string) ob_get_clean(), true));
    }

    public static function restEmployeeFinish()
    {
        ob_start();
        (new EmployeeController())->finish();
        return rest_ensure_response(json_decode((string) ob_get_clean(), true));
    }

    public static function ajaxAdminRequests(): void
    {
        if (!current_user_can(Capabilities::VIEW_ALL_REQUESTS)) {
            wp_send_json_error(['message' => 'Sem permissão'], 403);
        }
        (new AdminController())->requests();
        wp_die();
    }

    public static function ajaxAdminUpdate(): void
    {
        if (!current_user_can(Capabilities::APPROVE_REQUESTS)) {
            wp_send_json_error(['message' => 'Sem permissão'], 403);
        }
        (new AdminController())->update();
        wp_die();
    }

    public static function ajaxEmployeeStart(): void
    {
        if (!current_user_can(Capabilities::ACCESS_EMPLOYEE_PANEL)) {
            wp_send_json_error(['message' => 'Sem permissão'], 403);
        }
        (new EmployeeController())->start();
        wp_die();
    }

    public static function ajaxEmployeeFinish(): void
    {
        if (!current_user_can(Capabilities::FINISH_OWN_REQUESTS)) {
            wp_send_json_error(['message' => 'Sem permissão'], 403);
        }
        (new EmployeeController())->finish();
        wp_die();
    }

    /** @param string[] $caps */
    private static function mapCaps(array $caps): array
    {
        $mapped = ['read' => true];
        foreach ($caps as $cap) {
            $mapped[$cap] = true;
        }

        return $mapped;
    }
}
