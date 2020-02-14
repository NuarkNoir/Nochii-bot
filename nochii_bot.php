<?php
define("TOKEN", "your-token-goes-here");
define("BOTNAME", "your-botname-goes-here");

require_once __DIR__."/vendor/autoload.php";

$bot = new PHPTelebot(TOKEN, BOTNAME);

function isChinese($text) {
    return boolval(preg_match("/[\x{4e00}-\x{9fa5}]+/u", $text));
}

$bot->cmd("/start", "Welcome here! Use /help command to learn more 'bout me");
$bot->cmd("/help", "Add me to group, give me ability to delete messages and kick users - thats all! I'll start working. But there is a little issue: I am working on shared hosting, so if there is a lot of messages I may miss something, so sorry if this happens.");
$bot->cmd("*", function() {
    $mso = Bot::message();
    $cid = $mso["chat"]["id"];
    $mid = $mso["message_id"];
    $is_chat = in_array($mso["chat"]["type"], ["supergroup", "group"]);
    $ncm = isset($mso["new_chat_members"]);
    $snd_isbot = boolval($mso["from"]["is_bot"]);
    if ($snd_isbot) {
        return 'ok';
    }
    
    $toban = false;
    if ($is_chat) {
        if ($ncm) {
            $nu = $mso["new_chat_members"][0];
            $toban = (isChinese($nu["first_name"]) || isChinese($nu["last_name"]));
        }
        else {
            $uid = $mso["from"]["id"];
            $is_admin = false;
            $data = json_decode(Bot::getChatAdministrators($cid), true);
            foreach ($data['result'] as $admin) {
                $ck = $uid === $admin['user']['id'];
                if ($ck) {
                    $is_admin = true;
                    break;
                }
            }
            if ($is_admin) {
                return 'ok';
            }
            $forb_uname = (isChinese($mso["from"]["first_name"]) || isChinese($mso["from"]["last_name"]));
            $forb_msg = isChinese($mso["text"]);
            $toban = ($forb_uname || $forb_msg);
        }
    }
    
    if ($toban) {
        if ($ncm) {
            $uid = $mso["new_chat_members"][0]["id"];
            $ep = "https://api.telegram.org/bot".TOKEN."/kickChatMember?chat_id=$cid&user_id=$uid";
            $result = file_get_contents($ep);
        }
        else {
            $ep = "https://api.telegram.org/bot".TOKEN."/deleteMessage?chat_id=$cid&message_id=$mid";
            $result = file_get_contents($ep);
        }
    }
    return 'ok';
});

$bot->run();
