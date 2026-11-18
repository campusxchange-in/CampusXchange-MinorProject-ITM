<?php
$title = "Password Reset";
?>

<h3>ACCOUNT SECURITY</h3>

<h2>Reset your password</h2>

<p>
Hello
<strong><?= htmlspecialchars($name) ?></strong>,
</p>

<p>
We received a request to reset your password.
Click the button below to create a new password.
</p>

<p style="text-align:center;margin:35px 0;">

<a
class="button"
href="<?= htmlspecialchars($resetLink) ?>">

Reset Password

</a>

</p>

<div class="notice">

This link expires in
<strong>1 hour</strong>.

</div>

<p>

If the button doesn't work,
copy and paste this link into your browser.

</p>

<p>

<a href="<?= htmlspecialchars($resetLink) ?>">

<?= htmlspecialchars($resetLink) ?>

</a>

</p>