<?php

class DB_Functions {
    private $connection;

    function __construct() {
        require_once 'DB_Connection.php';
        $db               = new DB_Connection();
        $this->connection = $db->connect();
        $this->connection->set_charset("utf8");
    }

    function __destruct() {

    }
    
    public function getAppInfo() {
        $sql    = "SELECT * FROM `app_detail`";
        $result = mysqli_query($this->connection, $sql);
//        $data   = array();
//        while ($row = mysqli_fetch_assoc($result))
//            $data[] = $row;
        return mysqli_fetch_assoc($result);
    }

    public function getMessages($userId) {
        $sql    = "SELECT * FROM `messages` ORDER BY id DESC";
        $result = mysqli_query($this->connection, $sql);
        $data   = array();
        while ($row = mysqli_fetch_assoc($result))
            $data[] = $row;
        return $data;
    }

    public function getCategories() {
        $sql    = "SELECT * FROM `categories`";
        $result = mysqli_query($this->connection, $sql);
        $data   = array();
        while ($row = mysqli_fetch_assoc($result))
            $data[] = $row;
        return $data;
    }

    public function updateUserPassword() {
        $sql    = "SELECT * FROM `users`";
        $result = mysqli_query($this->connection, $sql);
        $data   = array();
        while ($row = mysqli_fetch_assoc($result)) {
//            $data[] = $row;
            $sql = "UPDATE `users` SET `password` = '" . $row['mobile'] . "' WHERE id = " . $row['id'];
            mysqli_query($this->connection, $sql);
            print($sql."</br>");
        }
        return $data;
    }

    public function getUserCategories($userId) {
        $sql    = "SELECT * FROM `categories` WHERE user_id='$userId' OR user_id=0 ORDER BY id DESC";
        $result = mysqli_query($this->connection, $sql);
        $data   = array();
        while ($row = mysqli_fetch_assoc($result))
            $data[] = $row;
        return $data;
    }

    public function getUserContents($userId) {
        $sql    = "SELECT * FROM `contents` WHERE user_id='$userId'";
        $result = mysqli_query($this->connection, $sql);
        $data   = array();
        while ($row = mysqli_fetch_assoc($result))
            $data[] = $row;
        return $data;
    }

    public function getUserFavorites($userId) {
        $sql    = "SELECT * FROM `favorite` WHERE user_id='$userId'";
        $result = mysqli_query($this->connection, $sql);
        $data   = array();
        while ($row = mysqli_fetch_assoc($result))
            $data[] = $row;
        return $data;
    }

    public function getContentWithCategory($category_id, $user_id) {
        $sql    = "SELECT * FROM `contents` WHERE category_id='$category_id' AND user_id='$user_id'";
        $result = mysqli_query($this->connection, $sql);
        $data   = array();
        while ($row = mysqli_fetch_assoc($result))
            $data[] = $row;
        return $data;
    }

    public function getContentWithUserIdAndId($user_id, $id) {
        $sql    = "SELECT * FROM `contents` WHERE id = $id AND user_id = $user_id";
        $result = mysqli_query($this->connection, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function getContentWithId($user_id, $id) {
        $sql    = "SELECT * FROM `contents` WHERE id = $id";
        $result = mysqli_query($this->connection, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function getCategoryWithId($user_id, $id) {
        $sql    = "SELECT * FROM `categories` WHERE id = $id";
        $result = mysqli_query($this->connection, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function getCategoryWithUserIdAndId($user_id, $id) {
        $sql    = "SELECT * FROM `categories` WHERE id = $id AND user_id = $user_id";
        $result = mysqli_query($this->connection, $sql);
        return mysqli_fetch_assoc($result);
    }

 
    public function getUserByEmailOrMobile($mobile) {
        $data = array();
        $user = $this->isUserExisted($mobile);
        if ($user == null) {
            $data["error"]     = ($user == null);
            $data["error_msg"] = "کاربری با این شماره وجود ندارد لطفا ثبت نام کنید.";
        } else {
            $data['user'] = $user;
//            $data['contents']   = $this->getUserContents($user['id']);
            $data['categories'] = $this->getUserCategories($user['id']);
            $data['favorites']  = $this->getUserFavorites($user['id']);
            $data['contents']   = $this->getUserContents($user['id']);
            $data["error"]      = ($user == null);
            $data["error_msg"]  = "شما با موفقیت وارد شدید.";
        }
        return $data;
    }


    public function insertCrash($exception,$android_version,$app_version) {
        $data = array();
            $query ="INSERT INTO `crashes` (`id`, `exception`, `android_version`, `app_version`, `update_at`) VALUES (NULL, '$exception', '$android_version', '$app_version', current_timestamp())";
            if (mysqli_query($this->connection, $query)){
                $data["error"]      = false;
                $data["error_msg"]  = "Insert SUCCESS FULL"; 
            }else{
                $data["error"]      = true;
                $data["error_msg"]  = "Insert UN SUCCESS FULL"; 
            }
       
        return $data;
    }
    
    public function insertUser($mobile,$full_name) {
        $user = $this->isUserExisted($mobile);
        $data = array();
        if ($user != null) {
            $data["error"]     = true;
            $data["error_msg"] = "این کاربر قبلا ثبت نام کرده است";
        } else {
            $query ="INSERT INTO `users` (`id`, `full_name`, `password`, `email`, `mobile`, `create_at`, `update_at`) VALUES (NULL, '$full_name', '$mobile', '', '$mobile', NOW(), NOW());";
            if (mysqli_query($this->connection, $query))
                $data = $this->getUserByEmailOrMobile($mobile);
        }
        return $data;
    }

    private function isUserExisted($mobile) {
        $query  = "SELECT * FROM `users` WHERE mobile='$mobile'";
        $result = mysqli_query($this->connection, $query);
        $user   = null;
        if ($result->num_rows == 1)
            $user = mysqli_fetch_assoc($result);

        return $user;
    }
    
    

    private function isUserAliExisted($phone, $password) {
        $query  = "SELECT * FROM `ali_users` WHERE phone='$phone' AND password='$password'";
        $result = mysqli_query($this->connection, $query);
         echo $query;
        $user   = null;
        if ($result->num_rows == 1)
            $user = mysqli_fetch_assoc($result);

        return $user;
    }
    

    private function isAliUserExisted($phone, $password) {
        $query  = "SELECT * FROM `ali_users` WHERE phone='$phone' AND password='$password'";
        $result = mysqli_query($this->connection, $query);
        echo $query;
        $data   = null;
        if ($result->num_rows == 1)
            $data = mysqli_fetch_assoc($result);

        return $data;
    }

    
    private function isFavoriteExisted($content_id, $user_id) {
        $query  = "SELECT * FROM `favorite` WHERE content_id = '$content_id' AND user_id = '$user_id'";
        $result = mysqli_query($this->connection, $query);
        $user   = null;
        if ($result->num_rows == 1)
            $user = mysqli_fetch_assoc($result);

        return $user;
    }


  public function insertFavorite($content_id, $user_id) {
    $user = $this->isFavoriteExisted($content_id, $user_id);
    $data = array();
    
    if ($user != null) {
        // ✅ اگر وجود دارد، حذف کن
        $query = "DELETE FROM `favorite` WHERE `content_id` = '$content_id' AND `user_id` = '$user_id'";
        if (mysqli_query($this->connection, $query)) {
            $data["error"] = false;
            $data["error_msg"] = "نوحه با موفقیت از علاقه مندی ها حذف شد";
            $data["favorite"] = null; // ✅ برای حذف، null برگردان
            $data["action"] = "removed";
        } else {
            $data["error"] = true;
            $data["error_msg"] = "خطا در حذف از علاقه مندی ها";
            $data["favorite"] = null;
        }
    } else {
        // ✅ اگر وجود ندارد، اضافه کن
        $query = "INSERT INTO `favorite` (`id`, `content_id`, `user_id`, `created_at`, `update_at`) 
                  VALUES (NULL, '$content_id', '$user_id', current_timestamp(), current_timestamp())";
        
        if (mysqli_query($this->connection, $query)) {
            $category = $this->isFavoriteExisted($content_id, $user_id);
            $data["error"] = false;
            $data["error_msg"] = "نوحه با موفقیت به علاقه مندی ها اضافه شد";
            $data["favorite"] = $category;
            $data["action"] = "added";
        } else {
            $data["error"] = true;
            $data["error_msg"] = "خطا در افزودن به علاقه مندی ها";
            $data["favorite"] = null;
        }
    }
    
    return $data;
}

    public function insertCategory($title, $description, $user_id) {
        $user     = $this->isCategoryExisted($title, $user_id);
        $category = null;
        $data     = array();
        if ($user != null) {
            $data["error"]     = true;
            $data["error_msg"] = "دسته بندی وجود دارد";
        } else {
            $query = "INSERT INTO `categories` (`id`, `user_id`, `title`, `description`) VALUES"
                . " (NULL, '$user_id', '$title', '$description');";
            if (mysqli_query($this->connection, $query))
                $category = $this->isCategoryExisted($title, $user_id);
            $data["error"]     = ($category == null);
            $data["error_msg"] = "دسته بندی با موفقیت ثبت شد";
            $data["category"]  = $category;
        }
        return $data;
    }
    private function isCategoryExisted($title, $user_id) {
        $query  = "SELECT * FROM `categories` WHERE title='$title' AND user_id='$user_id'";
        $result = mysqli_query($this->connection, $query);
        $data   = null;
        if ($result->num_rows == 1)
            $data = mysqli_fetch_assoc($result);

        return $data;
    }

    public function insertContent($category_id, $user_id, $answer, $content, $subject, $content_type) {
        $data   = array();
        $query  = "INSERT INTO `contents`"
            . " (`id`, `category_id`, `user_id`, `answer`, `content`, `subject`, `content_type`) VALUES"
            . " (NULL, '$category_id', '$user_id', '$answer', '$content', '$subject', '$content_type');";
        $result = mysqli_query($this->connection, $query);
        if ($result) {
            $last_id           = $this->connection->insert_id;
            $content           = $this->getContentWithUserIdAndId($user_id, $last_id);
            $data["error"]     = ($content == null);
            $data["error_msg"] = $result . "نوحه با موفقیت ثبت شد";
            $data["content"]   = $content;
        } else {
            $data["error"]     = true;
            $data["error_msg"] = "$result => " . $result . " => ثبت با خطا مواجه شد دوباره سعی کنید";
        }


        return $data;
    }

    public function updateContent($user_id, $contentID, $answer, $contentContent, $subject) {
        $data          = array();
        $contentBefore = $this->getContentWithUserIdAndId($user_id, $contentID);
        if ($contentBefore == null) {
            $data["error"]     = true;
            $data["error_msg"] = "مشکلی رخ داده است لطفا دوباره امتحان کنید";
            $data["content"]   = null;
        } else {
            $query  = "UPDATE `contents` SET "
                . "`answer` = '$answer',"
                . " `content` = '$contentContent',"
                . " `subject` = '$subject'"
                . " WHERE id = $contentID;";
            $result = mysqli_query($this->connection, $query);
            if ($result) {
                $content           = $this->getContentWithUserIdAndId($user_id, $contentID);
                $data["error"]     = ($content == null);
                $data["error_msg"] = $content['subject'] . " با موفقیت ویرایش شد ";
                $data["content"]   = $content;
            } else {
                $data["error"]     = true;
                $data["error_msg"] = "مشکلی رخ داده است لطفا دوباره امتحان کنید";
                $data["content"]   = null;
            }
        }
        return $data;
    }

    public function deleteContent($user_id, $contentId) {
        $data    = array();
        $content = $this->getContentWithUserIdAndId($user_id, $contentId);
        if ($content != null) {
            $query           = "DELETE FROM contents WHERE id = $contentId";
            $result          = mysqli_query($this->connection, $query);
            $data["success"] = $result;
            $data["message"] = $result ? "متن با موفقیت حذف شد" : " متن حذف نشد";
        } else {
            $data["success"] = false;
            $data["message"] = "متن وجود ندارد";
        }
        return $data;
    }

    public function deleteCategory($user_id, $categoryId) {
        $data    = array();
        $content = $this->getCategoryWithUserIdAndId($user_id, $categoryId);
        if ($user_id == 0) {
            $data["success"] = false;
            $data["message"] = "شما اجازه حذف این دسته بندی را ندارید";
        } else if ($content['title'] == null) {
            $data["success"] = false;
            $data["message"] = "دسته بندی یافت نشد";
        } else {
            $query           = "DELETE FROM categories WHERE id = $categoryId AND user_id = $user_id";
            $result          = mysqli_query($this->connection, $query);
            // $data["title"]   = $content['title'];
            $data["success"] = $result;
            $data["message"] = $result ? "دسته بندی با موفقیت حذف شد" : " دسته بندی حذف نشد";
        }
        return $data;
    }

    public function updateCategory($user_id, $category_id, $title, $description) {
        $data          = array();
        $contentBefore = $this->getCategoryWithUserIdAndId($user_id, $category_id);
        if ($user_id == 0) {
            $data["error"]     = true;
            $data["error_msg"] = "مشکلی رخ داده است لطفا دوباره امتحان کنید";
            $data["category"]  = null;
        } else if ($contentBefore == null) {
            $data["error"]     = true;
            $data["error_msg"] = "مشکلی رخ داده است لطفا دوباره امتحان کنید";
            $data["category"]  = null;
        } else {
            $query = "UPDATE `categories` SET "
                . "`title` = '$title', "
                . "`description` = '$description' "
                . "WHERE id = $category_id";

            $result = mysqli_query($this->connection, $query);
            if ($result) {
                $content           = $this->getCategoryWithUserIdAndId($user_id, $category_id);
                $data["error"]     = ($content == null);
                $data["error_msg"] = $content['title'] . " با موفقیت ویرایش شد ";
                $data["category"]  = $content;
            } else {
                $data["error"]     = true;
                $data["error_msg"] = "مشکلی رخ داده است لطفا دوباره امتحان کنید";
                $data["category"]  = null;
            }
        }
        return $data;
    }

}

