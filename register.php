<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireGuest();

$users  = new UserRepository(Database::connection());
$errors = [];
$old    = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $name     = str_param($_POST, 'name');
    $email    = mb_strtolower(str_param($_POST, 'email'));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm  = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $old      = ['name' => $name, 'email' => $email];

    $v = (new Validator())
        ->required('name', $name, 'Name')
        ->length('name', $name, 2, 60, 'Name')
        ->matches('name', "/^[\p{L} .'-]+$/u", $name, 'Use letters, spaces, periods, apostrophes or hyphens only.')
        ->required('email', $email, 'Email')
        ->length('email', $email, 6, 120, 'Email')
        ->email('email', $email)
        ->required('password', $password, 'Password')
        ->length('password', $password, 8, 72, 'Password')
        ->matches('password', '/^(?=.*[A-Za-z])(?=.*\d)/', $password, 'Password needs at least one letter and one number.')
        ->same('confirm_password', $confirm, $password, 'Passwords do not match.');

    if (!$v->has('email') && $users->emailExists($email)) {
        $v->add('email', 'That email already has an account.');
    }

    if (!$v->fails()) {
        try {
            $users->create($name, $email, $password);
            Session::flash('success', 'Welcome to the kusina! Log in to start cooking.');
            redirect('login.php');
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {   // 23000 = UNIQUE(email) hit by a simultaneous sign-up
                throw $e;
            }
            $v->add('email', 'That email already has an account.');
        }
    }
    $errors = $v->errors();
}

$pageTitle = 'Join';
$activeNav = 'register';
require __DIR__ . '/partials/header.php';
?>
<section class="auth">
    <div class="auth-copy">
        <p class="eyebrow">Para sa mga taga-luto</p>
        <h1>Bring your <em>sikretong sangkap</em> to the barangay table.</h1>
        <p>Share the dishes your family asks for, find tonight's ulam, and keep the recipes you love in one place.</p>
    </div>

    <form class="panel" method="post" action="register.php" data-validate novalidate>
        <h2>Create your account</h2>
        <?= csrf_field() ?>

        <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" autocomplete="name" value="<?= e($old['name']) ?>"
                   required minlength="2" maxlength="60" pattern="[\p{L} .'\-]+"
                   data-pattern-msg="Use letters, spaces, periods, apostrophes or hyphens only.">
            <p class="error"><?= e($errors['name'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="<?= e($old['email']) ?>"
                   required minlength="6" maxlength="120">
            <p class="error"><?= e($errors['email'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   required minlength="8" maxlength="72" pattern="(?=.*[A-Za-z])(?=.*\d).+"
                   data-pattern-msg="Use at least one letter and one number.">
            <p class="hint">8–72 characters, with at least one letter and one number.</p>
            <p class="error"><?= e($errors['password'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password"
                   required data-match="password" data-match-msg="Passwords do not match.">
            <p class="error"><?= e($errors['confirm_password'] ?? '') ?></p>
        </div>

        <button class="btn btn-block" type="submit">Create account</button>
        <p class="muted center">Already a member? <a href="login.php">Log in</a></p>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
