<?php
$title = "Verify Email";
?>

<h3>EMAIL VERIFICATION</h3>

<h2>Verify your email address</h2>

<p>

Hello
<strong><?= htmlspecialchars($name) ?></strong>,

</p>

<p>

Click the button below to verify your email.

</p>

<p style="text-align:center;margin:35px 0;">

<a
class="button"
href="<?= htmlspecialchars($verifyLink) ?>">

Verify Email

</a>

</p>

<div class="notice">

This verification link may expire after some time.

</div>