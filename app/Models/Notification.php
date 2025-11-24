<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'data',
        'is_read',
        'data_id',
        'message',
        'notification_for',
    ];

    /**
     * Configuration map for notification types
     */
    private function getNotificationConfig(): array
    {
        return [
            // Deals
            'assign_lead' => [
                'route' => 'leads.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Added you in lead <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-plus',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'create_deal' => [
                'route' => 'leads.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Create deal from lead <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-plus',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'assign_deal' => [
                'route' => 'deals.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Added you in deal <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-plus',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'create_deal_call' => [
                'route' => 'deals.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Create new Deal Call in deal <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-phone',
                'icon_color' => 'bg-info',
                'not_type' => 'simple',
            ],
            'update_deal_source' => [
                'route' => 'deals.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Update Sources in deal <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-file-alt',
                'icon_color' => 'bg-warning',
                'not_type' => 'simple',
            ],
            'create_task' => [
                'route' => 'deals.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Create new Task in deal <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-tasks',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'create_transfer' => [
                'route' => 'bank-transfer.index',
                'route_param' => null,
                'text_template' => '{user} Created a new Bank Transfer <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-tasks',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'create_employee' => [
                'route' => 'employee.index',
                'route_param' => null,
                'text_template' => '{user} Created a new Employee <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-tasks',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'assign_project' => [
                'route' => 'projects.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Assigned a Project <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-tasks',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'add_product' => [
                'route' => 'deals.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Add new Products in deal <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-dolly',
                'icon_color' => 'bg-danger',
                'not_type' => 'simple',
            ],
            'add_discussion' => [
                'route' => 'deals.show',
                'route_param' => 'deal_id',
                'text_template' => '{user} Add new Discussion in deal <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-comments',
                'icon_color' => 'bg-info',
                'not_type' => 'simple',
            ],
            'move_deal' => [
                'route' => 'deals.show',
                'route_param' => 'data_id',
                'text_template' => '{user} Moved the deal <b class="font-weight-bold">{name}</b> from {old_status} to {new_status}',
                'icon' => 'fa fa-arrows-alt',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],

            // Estimations
            'assign_estimation' => [
                'route' => 'estimations.show',
                'route_param' => 'estimation_id',
                'text_template' => '{user} Added you in estimation <b class="font-weight-bold">{estimation_name}</b>',
                'icon' => 'fa fa-plus',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],

            // Leads
            'create_lead_call' => [
                'route' => 'leads.show',
                'route_param' => 'lead_id',
                'text_template' => '{user} Create new Lead Call in lead <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-phone',
                'icon_color' => 'bg-info',
                'not_type' => 'simple',
            ],
            'update_lead_source' => [
                'route' => 'leads.show',
                'route_param' => 'lead_id',
                'text_template' => '{user} Update Sources in lead <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-file-alt',
                'icon_color' => 'bg-warning',
                'not_type' => 'simple',
            ],
            'add_lead_product' => [
                'route' => 'leads.show',
                'route_param' => 'lead_id',
                'text_template' => '{user} Add new Products in lead <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-dolly',
                'icon_color' => 'bg-danger',
                'not_type' => 'simple',
            ],
            'add_lead_discussion' => [
                'route' => 'leads.show',
                'route_param' => 'lead_id',
                'text_template' => '{user} Add new Discussion in lead <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-comments',
                'icon_color' => 'bg-info',
                'not_type' => 'simple',
            ],
            'move_lead' => [
                'route' => 'leads.show',
                'route_param' => 'lead_id',
                'text_template' => '{user} Moved the lead <b class="font-weight-bold">{name}</b> from {old_status} to {new_status}',
                'icon' => 'fa fa-arrows-alt',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],

            // HR Management
            'leave' => [
                'route' => 'leave.show',
                'route_param' => 'data_id',
                'text_template' => '{user} {notification_for} <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],

            // Invoice Management
            'create_invoice' => [
                'route' => 'invoice.show',
                'route_param' => 'data_id',
                'text_template' => '{user} created a new invoice <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-file-invoice',
                'icon_color' => 'bg-primary',
                'not_type' => 'approval',
                'approve_route' => 'invoice.approve',
                'reject_route' => 'invoice.reject',
            ],
            'approval_request_invoice' => [
                'route' => 'invoice.show',
                'route_param' => 'data_id',
                'text_template' => '{user} sent a invoice <b class="font-weight-bold">{name}</b> for approval',
                'icon' => 'fa fa-file-invoice',
                'icon_color' => 'bg-primary',
                'not_type' => 'approval',
                'approve_route' => 'invoice.approve',
                'reject_route' => 'invoice.reject',
            ],
            'create_expanse' => [
                'route' => 'expense.show',
                'route_param' => 'data_id',
                'text_template' => '{user} created a new expanse <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-dollar-sign',
                'icon_color' => 'bg-danger',
                'not_type' => 'approval',
                'approve_route' => 'expense.approve',
                'reject_route' => 'expense.reject',
            ],
            'request_approve_bill' => [
                'route' => 'bill.show',
                'route_param' => 'data_id',
                'text_template' => '{user} sent a bill <b class="font-weight-bold">{name}</b> for approval',
                'icon' => 'fa fa-receipt',
                'icon_color' => 'bg-primary',
                'not_type' => 'approval',
                'approve_route' => 'bill.approve',
                'reject_route' => 'bill.reject',
            ],
            'create_bill' => [
                'route' => 'bill.show',
                'route_param' => 'data_id',
                'text_template' => '{user} created a new bill <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-receipt',
                'icon_color' => 'bg-primary',
                'not_type' => 'approval',
                'approve_route' => 'bill.approve',
                'reject_route' => 'bill.reject',
            ],
            'task' => [
                'route' => 'projects.tasks.index',
                'route_param' => 'project_id',
                'text_template' => '{user} {notification_for} <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'checklist' => [
                'route' => 'projects.tasks.index',
                'route_param' => 'project_id',
                'text_template' => '{user} {notification_for} <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'comment' => [
                'route' => 'projects.tasks.index',
                'route_param' => 'project_id',
                'text_template' => '{user} {notification_for} <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'bug' => [
                'route' => 'task.bug.kanban',
                'route_param' => 'project_id',
                'text_template' => '{user} {notification_for} <b class="font-weight-bold">{name}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'termination' => [
                'route' => 'termination.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for} <b class="font-weight-bold">{user}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'warning' => [
                'route' => 'warning.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for} <b class="font-weight-bold">{user}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'resignation' => [
                'route' => 'resignation.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for}',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'transfer' => [
                'route' => 'transfer.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for} <b class="font-weight-bold">{user}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'promotion' => [
                'route' => 'promotion.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for} {promotion} <b class="font-weight-bold">by {user}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'award' => [
                'route' => 'award.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for} <b class="font-weight-bold">{user}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'travel' => [
                'route' => 'travel.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for}',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'complaint' => [
                'route' => 'complaint.show',
                'route_param' => 'data_id',
                'text_template' => '{name} {notification_for} <b class="font-weight-bold">{user}</b>',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'announcement' => [
                'route' => 'announcement.index',
                'route_param' => null,
                'text_template' => '{user} {notification_for}',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'holiday' => [
                'route' => 'holiday.index',
                'route_param' => null,
                'text_template' => '{user} {notification_for}',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'event' => [
                'route' => 'event.index',
                'route_param' => null,
                'text_template' => '{user} {notification_for}',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
            'policy' => [
                'route' => 'company-policy.index',
                'route_param' => null,
                'text_template' => '{user} {notification_for}',
                'icon' => 'fa fa-bell',
                'icon_color' => 'bg-primary',
                'not_type' => 'simple',
            ],
        ];
    }

    public function toHtml()
    {
        $data = json_decode($this->data);
        $config = $this->getNotificationConfig();

        // Check if notification type exists in config
        if (!isset($config[$this->type])) {
            return '';
        }

        $notificationConfig = $config[$this->type];

        // Get user who triggered the notification
        $user = null;
        if (isset($data->updated_by) && !empty($data->updated_by)) {
            $user = User::find($data->updated_by);
        }

        if (!$user) {
            return '';
        }

        // Build route
        $link = $this->buildRoute($notificationConfig, $data);

        // Build text using template
        $text = $this->buildText($notificationConfig['text_template'], $data, $user);

        // Build HTML
        return $this->buildHtml(
            $link,
            $notificationConfig['icon'],
            $notificationConfig['icon_color'],
            $text,
            $notificationConfig['not_type'],
            $notificationConfig
        );
    }

    private function buildRoute(array $config, object $data): string
    {
        if ($config['route_param'] === null) {
            return route($config['route']);
        }

        $paramValue = $data->{$config['route_param']} ?? null;

        if ($paramValue === null) {
            return '#';
        }
        return route($config['route'], [$paramValue]);
    }

    private function buildText(string $template, object $data, User $user): string
    {
        $replacements = [
            '{user}' => $user->name,
            '{name}' => $data->name ?? '',
            '{estimation_name}' => $data->estimation_name ?? '',
            '{notification_for}' => __($this->notification_for ?? ''),
            '{old_status}' => __(ucwords($data->old_status ?? '')),
            '{new_status}' => __(ucwords($data->new_status ?? '')),
            '{promotion}' => $data->promotion ?? '',
        ];

        $text = $template;
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }

        // Translate the text
        return __($text);
    }

    private function buildHtml(
        string $link,
        string $icon,
        string $iconColor,
        string $text,
        string $notType,
        array $config
    ): string {
        $date = $this->created_at->diffForHumans();

        // Show approve/reject buttons only for non-simple notification types
        $approvalButtons = '';
        if ($notType !== 'simple') {
            $approveRoute = $config['approve_route'] ?? $config['route'] . '.approve';
            $rejectRoute = $config['reject_route'] ?? $config['route'] . '.reject';
            $approvalButtons = '
                <div class="d-flex align-items-center ml-4 ps-4" style="margin-left:1.5rem;">
                    <form method="POST" action="' . route($approveRoute, [$this->data_id]) . '" class="d-inline">
                        ' . csrf_field() . '
                        <button type="submit" class="btn btn-sm btn-success mr-1">
                            ' . __('Approve') . '
                        </button>
                    </form>
                    <form method="POST" action="' . route($rejectRoute, [$this->data_id]) . '" class="d-inline ms-2">
                        ' . csrf_field() . '
                        <button type="submit" class="btn btn-sm btn-danger">
                            ' . __('Reject') . '
                        </button>
                    </form>
                </div>';
        }

        return '<a href="#"
                    class="list-group-item list-group-item-action notification_model border-bottom"
                    onclick="showNotificationModal(this)"
                    data-link="' . $link . '" 
                    data-type="' . $notType . '" 
                    data-notificationId="' . $this->id . '">
                    <div class="d-flex align-items-center p-1 ">
                        <div>
                            <span class="avatar ' . $iconColor . ' text-white rounded-circle" 
                                  style="max-width: 40px; max-height: 40px; margin-right: 7px;">
                                <i class="' . $icon . '"></i>
                            </span>
                        </div>
                        <div class="flex-fill ml-3">
                            <div class="h6 text-sm mb-0 notification-message">' . $text . '</div>
                            <small class="text-muted text-xs">' . $date . '</small>
                        </div>
                    </div>
                    ' . $approvalButtons . '
                </a>';
    }
}