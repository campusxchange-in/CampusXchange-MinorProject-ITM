<?php
$title = "Welcome";
?>

<h3>WELCOME</h3>

<h2>

Welcome to
<?= htmlspecialchars(\PrivateMailer\Config::get('MAIL_FROM_NAME')) ?>

</h2>

<p>

Hello
<strong><?= htmlspecialchars($name) ?></strong>,

</p>

<p>

Your account has been created successfully.

</p>

<p>

We're excited to have you with us.

</p>

<div class="notice">

You can now sign in and start using all available features.

</div>