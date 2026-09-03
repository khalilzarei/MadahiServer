<?php
declare(strict_types=1);

/**
 * لایه دسترسی به داده — همه کوئری‌ها با Prepared Statement
 * برای جلوگیری از SQL Injection.
 */
class DB_Functions
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::get();
    }

    // ================== عمومی ==================

    public function getAppInfo(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM `app_detail` ORDER BY id DESC LIMIT 1');
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    public function getMessages(int $userId): array
    {
        // اگر بعداً پیام خصوصی خواستید: WHERE user_id = ? OR user_id = 0
        $stmt = $this->db->query('SELECT * FROM `messages` ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    // ================== احراز هویت ==================

    public function getUserByMobile(string $mobile): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `users` WHERE mobile = ? LIMIT 1');
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getUserByToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            'SELECT * FROM `users`
             WHERE api_token_hash = ?
               AND (api_token_expires_at IS NULL OR api_token_expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function issueToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32)); // ۶۴ کاراکتر تصادفی
        $hash  = hash('sha256', $token);    // فقط هش در دیتابیس ذخیره می‌شود

        $stmt = $this->db->prepare(
            'UPDATE `users`
             SET api_token_hash = ?, api_token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
             WHERE id = ?'
        );
        $stmt->execute([$hash, $userId]);

        return $token;
    }

    public function refreshTokenExpiry(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `users` SET api_token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?'
        );
        $stmt->execute([$userId]);
    }

    public function loginByMobile(string $mobile): array
    {
        $user = $this->getUserByMobile($mobile);
        if ($user === null) {
            return ['error' => true, 'error_msg' => 'کاربری با این شماره وجود ندارد لطفا ثبت نام کنید.'];
        }

        $token = $this->issueToken((int)$user['id']);
        $user  = $this->getUserByMobile($mobile);

        return [
            'error'      => false,
            'error_msg'  => 'شما با موفقیت وارد شدید.',
            'token'      => $token,
            'user'       => $this->sanitizeUser($user),
            'categories' => $this->getUserCategories((int)$user['id']),
            'favorites'  => $this->getUserFavorites((int)$user['id']),
            'contents'   => $this->getUserContents((int)$user['id']),
        ];
    }

    public function register(string $mobile, string $fullName): array
    {
        if ($this->getUserByMobile($mobile) !== null) {
            return ['error' => true, 'error_msg' => 'این کاربر قبلا ثبت نام کرده است'];
        }

        // فعلاً ورود با رمز نداریم؛ یک رمز تصادفی هش‌شده ذخیره می‌شود
        // (زمانی که OTP یا رمز اضافه شد، همین فیلد استفاده می‌شود)
        $randomPassword = bin2hex(random_bytes(8));

        $stmt = $this->db->prepare(
            'INSERT INTO `users` (`full_name`, `password`, `email`, `mobile`, `create_at`, `update_at`)
             VALUES (?, ?, \'\', ?, NOW(), NOW())'
        );

        try {
            $stmt->execute([$fullName, password_hash($randomPassword, PASSWORD_DEFAULT), $mobile]);
        } catch (PDOException) {
            // خطای ایندکس یکتا (ثبت‌نام همزمان با همان شماره)
            return ['error' => true, 'error_msg' => 'این کاربر قبلا ثبت نام کرده است'];
        }

        $userId = (int)$this->db->lastInsertId();
        $token  = $this->issueToken($userId);
        $user   = $this->getUserByMobile($mobile);

        return [
            'error'      => false,
            'error_msg'  => 'ثبت نام با موفقیت انجام شد.',
            'token'      => $token,
            'user'       => $this->sanitizeUser($user),
            'categories' => [],
            'favorites'  => [],
            'contents'   => [],
        ];
    }

    private function sanitizeUser(array $user): array
    {
        unset($user['password'], $user['api_token_hash'], $user['api_token_expires_at']);
        return $user;
    }

    // ================== دسته‌بندی‌ها ==================

    public function getUserCategories(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `categories` WHERE user_id = ? OR user_id = 0 ORDER BY id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getCategoryById(int $categoryId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `categories` WHERE id = ? LIMIT 1');
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertCategory(string $title, string $description, int $userId): array
    {
        // جلوگیری از دسته تکراری برای همان کاربر
        $stmt = $this->db->prepare(
            'SELECT id FROM `categories` WHERE title = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$title, $userId]);
        if ($stmt->fetch()) {
            return ['error' => true, 'error_msg' => 'دسته بندی با این عنوان وجود دارد'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO `categories` (`user_id`, `title`, `description`) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $description]);

        $category = $this->getCategoryById((int)$this->db->lastInsertId());
        return ['error' => false, 'error_msg' => 'دسته بندی با موفقیت ثبت شد', 'category' => $category];
    }

    public function updateCategory(int $userId, int $categoryId, string $title, string $description): array
    {
        $category = $this->getCategoryById($categoryId);
        if ($category === null || (int)$category['user_id'] !== $userId) {
            return ['error' => true, 'error_msg' => 'دسته بندی یافت نشد یا متعلق به شما نیست', 'category' => null];
        }

        $stmt = $this->db->prepare(
            'UPDATE `categories` SET title = ?, description = ? WHERE id = ?'
        );
        $stmt->execute([$title, $description, $categoryId]);

        $updated = $this->getCategoryById($categoryId);
        return ['error' => false, 'error_msg' => ($updated['title'] ?? '') . ' با موفقیت ویرایش شد', 'category' => $updated];
    }

    public function deleteCategory(int $userId, int $categoryId): array
    {
        $category = $this->getCategoryById($categoryId);
        if ($category === null || (int)$category['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'دسته بندی یافت نشد یا متعلق به شما نیست'];
        }

        $this->db->beginTransaction();
        try {
            // حذف فایل‌های صوتی محتواهای این دسته
            $stmt = $this->db->prepare('SELECT audio_url FROM `contents` WHERE category_id = ?');
            $stmt->execute([$categoryId]);
            foreach ($stmt->fetchAll() as $row) {
                if (!empty($row['audio_url'])) {
                    $this->deleteAudioFile($row['audio_url']);
                }
            }

            // حذف علاقه‌مندی‌های محتواهای این دسته
            $stmt = $this->db->prepare(
                'DELETE f FROM `favorite` f INNER JOIN `contents` c ON c.id = f.content_id WHERE c.category_id = ?'
            );
            $stmt->execute([$categoryId]);

            // حذف محتواهای دسته
            $stmt = $this->db->prepare('DELETE FROM `contents` WHERE category_id = ?');
            $stmt->execute([$categoryId]);

            // حذف خود دسته
            $stmt = $this->db->prepare('DELETE FROM `categories` WHERE id = ?');
            $stmt->execute([$categoryId]);

            $this->db->commit();
            return ['success' => true, 'message' => 'دسته بندی و محتواهای آن با موفقیت حذف شد'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('deleteCategory error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در حذف دسته بندی'];
        }
    }

    // ================== محتواها ==================

    public function getUserContents(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM `contents` WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getContentWithCategory(int $categoryId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `contents` WHERE category_id = ? AND user_id = ? ORDER BY id DESC'
        );
        $stmt->execute([$categoryId, $userId]);
        return $stmt->fetchAll();
    }

    public function getContentWithUserIdAndId(int $userId, int $contentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `contents` WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$contentId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertContent(int $categoryId, int $userId, string $answer, string $content, string $subject, int $contentType): array
    {
        // بررسی مالکیت دسته — کسی نمی‌تواند داخل دسته دیگران محتوا بسازد
        // (دسته‌های مشترک با user_id=0 برای همه آزاد هستند)
        $category = $this->getCategoryById($categoryId);
        if ($category === null || ((int)$category['user_id'] !== $userId && (int)$category['user_id'] !== 0)) {
            return ['error' => true, 'error_msg' => 'دسته بندی یافت نشد یا متعلق به شما نیست'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO `contents` (`category_id`, `user_id`, `answer`, `content`, `subject`, `content_type`)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$categoryId, $userId, $answer, $content, $subject, $contentType]);

        $newContent = $this->getContentWithUserIdAndId($userId, (int)$this->db->lastInsertId());
        return ['error' => false, 'error_msg' => 'نوحه با موفقیت ثبت شد', 'content' => $newContent];
    }

    public function updateContent(int $userId, int $contentId, string $answer, string $content, string $subject, int $contentType): array
    {
        $contentBefore = $this->getContentWithUserIdAndId($userId, $contentId);
        if ($contentBefore === null) {
            return ['error' => true, 'error_msg' => 'محتوا یافت نشد یا متعلق به شما نیست', 'content' => null];
        }

        $stmt = $this->db->prepare(
            'UPDATE `contents` SET answer = ?, content = ?, subject = ?, content_type = ? WHERE id = ?'
        );
        $stmt->execute([$answer, $content, $subject, $contentType, $contentId]);

        $updated = $this->getContentWithUserIdAndId($userId, $contentId);
        return ['error' => false, 'error_msg' => ($updated['subject'] ?? '') . ' با موفقیت ویرایش شد', 'content' => $updated];
    }

    public function deleteContent(int $userId, int $contentId): array
    {
        $content = $this->getContentWithUserIdAndId($userId, $contentId);
        if ($content === null) {
            return ['success' => false, 'message' => 'محتوا وجود ندارد یا متعلق به شما نیست'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM `favorite` WHERE content_id = ?');
            $stmt->execute([$contentId]);

            $stmt = $this->db->prepare('DELETE FROM `contents` WHERE id = ?');
            $stmt->execute([$contentId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('deleteContent error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در حذف محتوا'];
        }

        if (!empty($content['audio_url'])) {
            $this->deleteAudioFile($content['audio_url']);
        }

        return ['success' => true, 'message' => 'متن با موفقیت حذف شد'];
    }

    public function updateContentAudio(int $contentId, string $audioUrl): void
    {
        $stmt = $this->db->prepare('UPDATE `contents` SET audio_url = ? WHERE id = ?');
        $stmt->execute([$audioUrl, $contentId]);
    }

    /**
     * لیست شعرهایی که سبک (ویس) دارند — ویژه نسخه پرو
     * (محتوای کاربر + محتوای مشترک با user_id = 0)
     */
    public function getPoemsWithAudio(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `contents`
             WHERE audio_url IS NOT NULL AND audio_url <> ""
               AND (user_id = ? OR user_id = 0)
             ORDER BY id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // ================== فاکتورهای زرین‌پال ==================

    public function createPremiumInvoice(int $userId, int $amount, string $authority): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `premium_invoices` (`user_id`, `amount`, `authority`, `status`)
             VALUES (?, ?, ?, "pending")'
        );
        $stmt->execute([$userId, $amount, $authority]);
    }

    /** آخرین فاکتور در انتظار پرداخت کاربر */
    public function getPendingInvoice(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `premium_invoices`
             WHERE user_id = ? AND status = "pending"
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findInvoiceByAuthority(string $authority): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `premium_invoices` WHERE authority = ? LIMIT 1'
        );
        $stmt->execute([$authority]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** آخرین فاکتور (برای debug) */
    public function dbLastInvoice(): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM `premium_invoices` ORDER BY id DESC LIMIT 1'
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * تسویه فاکتور: ثبت refId + فعال‌سازی پرو (idempotent)
     */
    public function settleZarinpalInvoice(int $invoiceId, string $refId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `premium_invoices`
             SET status = "active", ref_id = ?
             WHERE id = ? AND status = "pending"'
        );
        $stmt->execute([$refId, $invoiceId]);

        if ($stmt->rowCount() > 0) {
            $stmt2 = $this->db->prepare('SELECT * FROM `premium_invoices` WHERE id = ? LIMIT 1');
            $stmt2->execute([$invoiceId]);
            $invoice = $stmt2->fetch();

            if ($invoice) {
                $externalRef = $refId !== '' ? $refId : (string)$invoice['authority'];
                $this->activatePremium(
                    (int)$invoice['user_id'],
                    'zarinpal',
                    $externalRef
                );
            }
        }
    }


    // ================== علاقه‌مندی‌ها ==================

    public function getUserFavorites(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM `favorite` WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * محتواهای مورد علاقه کاربر (ردیف کامل محتوا)
     * برای لیست علاقه‌مندی‌ها در اپ
     */
    public function getFavoriteContents(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*
             FROM `favorite` f
             INNER JOIN `contents` c ON c.id = f.content_id
             WHERE f.user_id = ?
             ORDER BY f.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }


    public function insertFavorite(int $contentId, int $userId): array
    {
        // محتوا باید وجود داشته باشد
        $stmt = $this->db->prepare('SELECT id FROM `contents` WHERE id = ? LIMIT 1');
        $stmt->execute([$contentId]);
        if (!$stmt->fetch()) {
            return ['error' => true, 'error_msg' => 'محتوا یافت نشد', 'favorite' => null, 'action' => null];
        }

        $stmt = $this->db->prepare('SELECT * FROM `favorite` WHERE content_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$contentId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // حذف (Toggle)
            $stmt = $this->db->prepare('DELETE FROM `favorite` WHERE content_id = ? AND user_id = ?');
            $stmt->execute([$contentId, $userId]);
            return [
                'error'     => false,
                'error_msg' => 'نوحه با موفقیت از علاقه مندی ها حذف شد',
                'favorite'  => null,
                'action'    => 'removed',
            ];
        }

        // افزودن
        $stmt = $this->db->prepare(
            'INSERT INTO `favorite` (`content_id`, `user_id`, `created_at`, `update_at`) VALUES (?, ?, NOW(), NOW())'
        );
        $stmt->execute([$contentId, $userId]);

        $stmt = $this->db->prepare('SELECT * FROM `favorite` WHERE content_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$contentId, $userId]);
        $favorite = $stmt->fetch();

        return [
            'error'     => false,
            'error_msg' => 'نوحه با موفقیت به علاقه مندی ها اضافه شد',
            'favorite'  => $favorite,
            'action'    => 'added',
        ];
    }

    // ================== گزارش خطا (Crash) ==================

    public function insertCrash(string $exception, string $androidVersion, string $appVersion): array
    {
        $exception = mb_substr($exception, 0, 2000);

        $stmt = $this->db->prepare(
            'INSERT INTO `crashes` (`exception`, `android_version`, `app_version`, `update_at`)
             VALUES (?, ?, ?, NOW())'
        );
        $ok = $stmt->execute([$exception, $androidVersion, $appVersion]);

        return ['error' => !$ok, 'error_msg' => $ok ? 'گزارش خطا ثبت شد' : 'خطا در ثبت گزارش'];
    }

    // ================== محدودیت درخواست ==================

    public function countRequests(string $key, int $windowSeconds): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - $windowSeconds);

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS c FROM `request_log` WHERE request_key = ? AND created_at >= ?'
        );
        $stmt->execute([$key, $cutoff]);
        return (int)$stmt->fetch()['c'];
    }

    public function logRequest(string $key): void
    {
        $stmt = $this->db->prepare('INSERT INTO `request_log` (`request_key`, `created_at`) VALUES (?, NOW())');
        $stmt->execute([$key]);
    }

    // ================== نسخه پرو (Premium) ==================

    public function isPremium(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT is_premium, premium_source, premium_at FROM `users` WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * فعال‌سازی نسخه پرو — idempotent
     * (تکرار همان پرداخت، دوباره خطا نمی‌دهد؛ کلید یکتا external_ref کنترل می‌کند)
     */
    public function activatePremium(int $userId, string $source, string $externalRef): array
    {
        // ۱) source of truth: همیشه users آپدیت می‌شود (حتی اگر audit خراب باشد)
        $stmt = $this->db->prepare(
            'UPDATE `users` SET is_premium = 1, premium_source = ?, premium_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$source, $userId]);

        // ۲) audit row — best-effort؛ اگر جدول نباشد/تکراری باشد، بلاک نمی‌کند
        try {
            $stmt2 = $this->db->prepare(
                'INSERT INTO `premium_activations` (`user_id`, `source`, `external_ref`)
                 VALUES (?, ?, ?)'
            );
            $stmt2->execute([$userId, $source, $externalRef]);
        } catch (Throwable $e) {
            // 23000 = تکراری (قبلاً فعال)، 42S02 = جدول نیست — هر دو غیرمخرب
            error_log('activatePremium audit insert (non-fatal): ' . $e->getMessage());
        }

        return ['error' => false, 'error_msg' => 'نسخه پرو با موفقیت فعال شد', 'already' => false];
    }

    // ================== فایل‌های صوتی ==================

    public function deleteAudioFile(string $audioUrl): void
    {
        $name = basename((string)(parse_url($audioUrl, PHP_URL_PATH) ?: $audioUrl));
        if ($name === '') {
            return;
        }

        // ویس‌ها در uploads/songs ذخیره می‌شوند؛
        // مسیرهای قدیمی (بدون songs) هم چک می‌شوند برای فایل‌های قبلی
        $candidates = [
            dirname(dirname(__DIR__)) . '/uploads/songs/' . $name,
            dirname(__DIR__) . '/uploads/songs/' . $name,
            dirname(__DIR__) . '/uploads/' . $name,
            dirname(dirname(__DIR__)) . '/uploads/' . $name,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                @unlink($path);
                return;
            }
        }
    }
}
