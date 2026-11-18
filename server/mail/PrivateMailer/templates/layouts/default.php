<?php

use PrivateMailer\Config;

$title = $title ?? Config::get('MAIL_FROM_NAME');

$content = $content ?? '';

$primary = Config::get('MAIL_PRIMARY_COLOR') ?: '#0d6efd';

$logo = Config::get('MAIL_LOGO');

$appName = Config::get('MAIL_FROM_NAME');

$signature = Config::get('MAIL_SIGNATURE') ?: "The {$appName} Team";

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($title) ?></title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{

background:#edf2f7;

font-family:
Segoe UI,
Tahoma,
Geneva,
Verdana,
sans-serif;

padding:40px 15px;

color:#333;

}

.wrapper{

max-width:650px;

margin:auto;

}

.card{

background:#ffffff;

border-radius:16px;

overflow:hidden;

box-shadow:
0 15px 40px
rgba(0,0,0,.12);

}

.header{

background:<?= $primary ?>;

padding:30px;

display:flex;

align-items:center;

}

.logo{

width:55px;

height:55px;

border-radius:10px;

background:#fff;

padding:8px;

margin-right:15px;

object-fit:contain;

}

.logo-placeholder{

width:55px;

height:55px;

border-radius:10px;

background:#ffffff22;

display:flex;

align-items:center;

justify-content:center;

font-size:24px;

font-weight:bold;

color:#fff;

margin-right:15px;

}

.header h1{

color:#fff;

font-size:30px;

font-weight:700;

}

.content{

padding:40px;

font-size:16px;

line-height:1.8;

}

.content h2{

font-size:32px;

margin-bottom:20px;

color:#222;

}

.content h3{

margin-bottom:10px;

font-size:14px;

letter-spacing:2px;

text-transform:uppercase;

color:<?= $primary ?>;

}

.content p{

margin-bottom:20px;

}

.button{

display:inline-block;

padding:15px 30px;

background:<?= $primary ?>;

color:#fff !important;

text-decoration:none;

border-radius:8px;

font-weight:bold;

box-shadow:
0 8px 20px rgba(0,0,0,.15);

transition:.3s;

}

.notice{

margin:30px 0;

padding:18px;

background:#fff7e8;

border:1px solid #ffd27f;

border-radius:8px;

}

.notice strong{

color:#444;

}

.footer{

margin-top:35px;

padding-top:30px;

border-top:1px solid #e8e8e8;

font-size:14px;

color:#666;

}

.footer p{

margin-bottom:12px;

}

.small{

font-size:13px;

color:#888;

}

a{

color:<?= $primary ?>;

word-break:break-word;

}

@media only screen and (max-width:600px){

.header{

padding:22px;

}

.header h1{

font-size:24px;

}

.logo,
.logo-placeholder{

width:45px;

height:45px;

}

.content{

padding:25px;

}

.content h2{

font-size:26px;

}

.button{

display:block;

text-align:center;

}

}

</style>

</head>

<body>

<div class="wrapper">

<div class="card">

<div class="header">

<?php if(!empty($logo)): ?>

<img
class="logo"
src="<?= htmlspecialchars($logo) ?>"
alt="<?= htmlspecialchars($appName) ?>">

<?php else: ?>

<div class="logo-placeholder">

<?= strtoupper(substr($appName,0,1)) ?>

</div>

<?php endif; ?>

<h1>

<?= htmlspecialchars($appName) ?>

</h1>

</div>

<div class="content">

<?= $content ?>

</div>

<div class="footer">

<p>

Stay safe,

<br><br>

<strong>

<?= htmlspecialchars($signature) ?>

</strong>

</p>

<br>

<p class="small">

If you didn't request this email,

you may safely ignore it.

</p>

<br>

<p class="small">

© <?= date('Y') ?>

<?= htmlspecialchars($appName) ?>

All rights reserved.

</p>

</div>

</div>

</div>

</body>

</html>