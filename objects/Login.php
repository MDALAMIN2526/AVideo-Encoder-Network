<?php
require_once dirname(__FILE__) . '/../configuration.php';
require_once dirname(__FILE__) . '/Streamer.php';
require_once dirname(__FILE__) . '/functions.php';

class Login
{

    static function run($user, $pass, $aVideoURL, $encodedPass = false)
    {
        global $global;
        if (substr($aVideoURL, -1) !== '/') {
            $aVideoURL .= "/";
        }

        $postdata = http_build_query(
            array(
                'user' => $user,
                'pass' => $pass,
                'encodedPass' => $encodedPass
            )
        );

        $opts = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);

        $result = @url_get_contents($aVideoURL . 'login', $context);
        if (empty($result)) {
            $object = new stdClass();
            $object->streamer = false;
            $object->streamers_id = 0;
            $object->isLogged = false;
            $object->isAdmin = false;
            $object->canUpload = false;
            $object->canComment = false;
        } else {
            $object = json_decode($result);
            $object->streamer = $aVideoURL;
            $object->streamers_id = 0;
            if (!empty($object->canUpload)) {
                $object->streamers_id = Streamer::createIfNotExists($user, $pass, $aVideoURL, $encodedPass);
            }
            if ($object->streamers_id) {
                $s = new Streamer($object->streamers_id);
                if (!$encodedPass || $encodedPass === 'false') {
                    $pass = md5($pass);
                }
                // update pass
                $s->setPass($pass);
                $s->save();
            }
        }
        $_SESSION['login'] = $object;
    }

    static function logoff()
    {
        unset($_SESSION['login']);
    }

    static function isLogged()
    {
        return !empty($_SESSION['login']->isLogged);
    }

    static function isAdmin()
    {
        return !empty($_SESSION['login']->isAdmin);
    }

    static function canUpload()
    {
        return !empty($_SESSION['login']->canUpload);
    }

    static function canComment()
    {
        return !empty($_SESSION['login']->canComment);
    }

    static function getStreamerURL()
    {
        if (!static::isLogged()) {
            return false;
        }
        return $_SESSION['login']->streamer;
    }

    static function getStreamerId()
    {
        if (!static::isLogged()) {
            return false;
        }
        return $_SESSION['login']->streamers_id;
    }

}
