<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireGuest();

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $email    = mb_strtolower(str_param($_POST, 'email'));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $v = (new Validator())
        ->required('email', $email, 'Email')
        ->email('email', $email)
        ->required('password', $password, 'Password');

    if (!$v->fails()) {
        $user = (new UserRepository(Database::connection()))->findByEmail($email);
        if ($user !== null && password_verify($password, $user['password_hash'])) {
            Auth::login($user);
            Session::flash('success', 'Mabuhay, ' . $user['name'] . '! What are we cooking today?');
            redirect('index.php');
        }
        // Same message for unknown email and wrong password
        $v->add('form', 'Wrong email or password.');
    }
    $errors = $v->errors();
}

$pageTitle = 'Log in';
$activeNav = 'login';
require __DIR__ . '/partials/header.php';
?>
<section class="auth">
    <div class="auth-copy">
        <p class="eyebrow">Tuloy po kayo</p>
        <h1>The barangay's <em>recipe box</em> is open.</h1>
        <p>Log in to browse what your neighbors are cooking, save favorites, and leave a word for the cook.</p>
    </div>

    <form class="panel" method="post" action="login.php" data-validate novalidate>
        <h2>Log in</h2>
        <?= csrf_field() ?>
        <?php if (isset($errors['form'])): ?>
            <div class="flash flash-error"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="<?= e($email) ?>"
                   required maxlength="120">
            <p class="error"><?= e($errors['email'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <p class="error"><?= e($errors['password'] ?? '') ?></p>
        </div>

        <button class="btn btn-block" type="submit">Log in</button>
        <p class="muted center">New here? <a href="register.php">Create an account</a></p>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
