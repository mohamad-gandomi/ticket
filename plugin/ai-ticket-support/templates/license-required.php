<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فعال‌سازی لایسنس</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            direction: rtl;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .icon {
            font-size: 56px;
            margin-bottom: 24px;
        }
        h1 {
            font-size: 22px;
            color: #1a1a2e;
            margin-bottom: 16px;
        }
        p {
            font-size: 15px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 32px;
        }
        a.btn {
            display: inline-block;
            background: #0068ff;
            color: #fff;
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 15px;
            transition: background .2s;
        }
        a.btn:hover { background: #0055cc; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔒</div>
        <h1>لایسنس فعال نیست</h1>
        <p>برای دسترسی به پنل مدیریت، باید لایسنس افزونه را فعال کنید.<br>
        لطفاً از لینک زیر لایسنس خود را تهیه و فعال نمایید.</p>
        <a class="btn" href="https://www.rtl-theme.com/blog/smart-management-plugin/" target="_blank" rel="noopener">
            فعال‌سازی لایسنس
        </a>
    </div>
</body>
</html>
