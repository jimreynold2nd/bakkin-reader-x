<?php
/* Note:
    path format, used for caching: "/1 Yuru Yuri/Blu-Ray Special/16.jpg"
    thumb and img caches are in cache/thumbs and cache/imgs, with file names
    consists of sha1 of the path name above, plus ".jpg"
*/
$script_path = "/reader/";
$content_dir = "content";
$thumbcache_dir = "cache/thumbs";
$imgcache_dir = "cache/imgs";
$secret_file = ".htsecret";

function tourl($path) {
    return str_replace("%2F", "/", urlencode($path));
}

function normal_dir($d, $base) {
    return $d != "." && $d != ".." && is_dir($base . "/" . $d);
}

function startsWith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}
function endsWith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function sanitize($str) {
    if ($str == ".." || startsWith($str, "../") || endsWith($str, "/..") || strpos($str, '/../') !== false)
        exit(1);
    return $str;
}

function create_img($orig, $dest, $width, $height) {
    list($orig_width, $orig_height) = getimagesize($orig);

    // Make sure to keep image ratio
    $ratio = $orig_width/$orig_height;
    if ($width/$height > $ratio) {
        $width = $height * $ratio;
    } else {
        $height = $width / $ratio;
    }
    
    // Load the original image and create an img obj for the thumbnail
    if (endsWith($orig, ".png")) {
        $img_orig = imagecreatefrompng($orig);
    } elseif (endsWith($orig, ".jpg") || endsWith($orig, ".jpeg")) {
        $img_orig = imagecreatefromjpeg($orig);
    }
    $img_thumb = imagecreatetruecolor($width, $height);

    // Only downsize, don't expand
    if ($width < $orig_width || $height < $orig_height) {
        imagecopyresampled($img_thumb, $img_orig, 0, 0, 0, 0,
                           $width, $height, $orig_width, $orig_height);
    } else {
        imagecopyresampled($img_thumb, $img_orig, 0, 0, 0, 0,
                           $orig_width, $orig_height, $orig_width, $orig_height);
    }

    imageinterlace($img_thumb, true);
    imagejpeg($img_thumb, $dest, 80);
}

function caching_headers($file, $timestamp) {
    $gmt_mtime = gmdate('r', $timestamp);
    header('ETag: "'.md5($timestamp.$file).'"');
    header('Last-Modified: '.gmdate('r', $timestamp).' GMT');
    header('Expires: '.gmdate('r', $timestamp + 60*60*24*7).' GMT');
    header('Cache-Control: public');

    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        //if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == md5($timestamp.$file)) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        //}
    }
}

?>
