<?php
$title = "One Time Password";
?>

<h3>ACCOUNT SECURITY</h3>

<h2>Your One-Time Password</h2>

<p>

Hello
<strong><?= htmlspecialchars($name) ?></strong>,

</p>

<p>

Use the following OTP to continue.

</p>

<div
style="
margin:35px auto;
width:220px;
text-align:center;
font-size:34px;
font-weight:bold;
letter-spacing:8px;
padding:20px;
background:#f5f5f5;
border-radius:10px;
">

<?= htmlspecialchars($otp) ?>

</div>

<div class="notice">

Never share this OTP with anyone.

</div>