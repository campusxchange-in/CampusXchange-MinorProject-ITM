<?php
$title = "Account Approved";
?>

<h3>ACCOUNT APPROVED</h3>

<h2>

Congratulations!

</h2>

<p>

Hello
<strong><?= htmlspecialchars($name) ?></strong>,

</p>

<p>

Your account has been reviewed and approved.

</p>

<p>

You now have access to all features.

</p>

<div class="notice">

Thank you for being part of
<strong>

<?= htmlspecialchars(\PrivateMailer\Config::get('MAIL_FROM_NAME')) ?>

</strong>

</div>