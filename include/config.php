<?php
// ============================================================
// config.php — تنظیمات سرور
// ------------------------------------------------------------
// ۱) این فایل را با نام config.php کپی کنید
// ۲) رمز واقعی دیتابیس را وارد کنید
// ۳) فایل را بیرون از پوشه عمومی بگذارید (یک پوشه بالاتر از public_html)
//    یا اگر نشد، در همین پوشه include/
// ۴) هرگز این فایل را در گیت کامیت نکنید
// ============================================================

const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'madahino_madahi';
const DB_USER = 'madahino_user';
const DB_PASS = '@65877856.com';


// آدرس پایه اپ (بدون اسلش انتهایی)
const APP_BASE_URL = 'https://madahinote.ir';

// 'production' یا 'development' (نمایش خطاها فقط در development)
const APP_ENV = 'production';
