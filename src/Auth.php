<?php
declare(strict_types=1);

/** Who is logged in, and which pages they may reach. */
final class Auth
{
    public static function check(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function id(): int
    {
        return (int) Session::get('user_id', 0);
    }

    public static function name(): string
    {
        return (string) Session::get('user_name', '');
    }

    /** @param array{id:int|string,name:string} $user */
    public static function login(array $user): void
    {
        Session::regenerate();                       // stop session fixation
        Session::set('user_id', (int) $user['id']);
        Session::set('user_name', $user['name']);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    /** Member-only pages. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Please log in to continue.');
            redirect('login.php');
        }
    }

    /** Guest-only pages (login, register). */
    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('index.php');
        }
    }
}
