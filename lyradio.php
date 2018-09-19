<?php
chdir(__DIR__);
$url_stream = $argv[1];
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

try {
    $MadelineProto = new \danog\MadelineProto\API('session.madeline');
}
catch (\danog\MadelineProto\Exception $e) {
    \danog\MadelineProto\Logger::log($e->getMessage());
}
#$url_stream = "http://nashe.streamr.ru/rock-128.mp3";
$me = $MadelineProto->get_self();
if( $me === false ){
    $MadelineProto = new \danog\MadelineProto\API('session.madeline');
    $MadelineProto->start();
    $me = $MadelineProto->get_self();
}
    #$MadelineProto->settings = ['authorization' => ['default_temp_auth_key_expires_in' => 86400, ]];
    $MadelineProto->settings['updates']['handle_old_updates'] = false;
    #$MadelineProto->settings['updates']['handle_old_updates'] = false;
    $times = [];
    $calls = [];
    $old_calls_n = 1;
    $stat_message = "Users online: 1";

    $me = $MadelineProto->get_self();
    print_r($me);
    $lastUpdate = $MadelineProto->API->get_updates();

    //file_put_contents("log.txt", json_encode($lastUpdate,JSON_PRETTY_PRINT), FILE_APPEND | LOCK_EX);

    $offset = intval(end($lastUpdate)['update_id']) + 1;
    while (1) {
        try {
#           sleep(0.5);
            $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);

            foreach ($calls as $key => $call) {
                if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                    unset($calls[$key]);
                } elseif (isset($times[$call->getOtherID()]) && $times[$call->getOtherID()][0] < time()) {
                    $times[$call->getOtherID()][0] += 10;
                    try {
                        $cur_calls_n = count($calls);
                        if( $cur_calls_n != $old_calls_n ) {
                            sleep(0.5);
                            $old_calls_n = $cur_calls_n;
                            $message = "<b>Users online: </b>".$old_calls_n."\r";
                            $stat_message = $message;
    #                        $MadelineProto->messages->editMessage(['id' => $times[$call->getOtherID()][1], 'peer' => $call->getOtherID(), 'message' => $message, 'parse_mode' => 'HTML' ]);
                        }
                     } catch (\danog\MadelineProto\Exception $e) {
                        echo $e;
                        sleep(0.5);
                    }
                }
            }
            foreach ($updates as $update) {
                $offset = intval($update['update_id']) + 1;
                switch ($update['update']['_']) {
                    case 'updateNewMessage':
                        if ($update['update']['message']['out'] || $update['update']['message']['to_id']['_'] !== 'peerUser' || !isset($update['update']['message']['from_id'])) {
                            continue;
                        }

                        if ( isset($update['update']['message']['message']) ) {
                            sleep(0.5);
                            $ad_text = "<b>Realtime radio and audio streaming</b>\nCall to start using\nBy @LyoSU, @SlavikMIPT\n";
                            $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => $ad_text, 'parse_mode' => 'HTML']);
                        }
                        break;
                    case 'updatePhoneCall':
                        if (is_object($update['update']['phone_call']) &&
                            isset($update['update']['phone_call']->madeline) &&
                            $update['update']['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
                            sleep(0.5);
                            $peer_id = $update['update']['phone_call']->getOtherID();
                            $updnewmsg_id = $MadelineProto->messages->sendMessage(['peer' => $peer_id,'message' => $stat_message, 'parse_mode' => 'HTML'])['id'];
                            $times[$peer_id] = [time(), $updnewmsg_id];
                            $file = "/root/".$peer_id."_".$updnewmsg_id.".raw";
                            exec("php PlayRadio.php $file $url_stream > /dev/null 2>&1 &");
                            $start_time = time();
                            while (!file_exists($file)) {
                                if ((time() - $start_time) > 10) {
                                    break;
                                }
                            }
                            if (file_exists($file)){
                               $update['update']['phone_call']->accept()->play($file);
                               unlink($file);
                            }
                            $calls[$peer_id] = $update['update']['phone_call'];
                            $controller = $calls[$peer_id];
                            $controller->configuration['shared_config']['audio_init_bitrate'] = 120*1000; // Audio bitrate set when the call is started
                            $controller->configuration['shared_config']['audio_max_bitrate']  = 120*1000; // Maximum audio bitrate
                            $controller->configuration['shared_config']['audio_min_bitrate']  = 80*1000; // Minimum audio bitrate
                            $controller->configuration['shared_config']['audio_bitrate_step_decr']  = 2000; // Decreasing step: when libtgvoip has to lower the bitrate, it decreases it `audio_bitrate_step_decr` bps at a time
                            $controller->configuration['shared_config']['audio_bitrate_step_incr']  = 5000; // Increasing step: when libtgvoip has to make the bitrate higher, it increases it `audio_bitrate_step_decr` bps at a time
                            $controller->parseConfig();
                        }
                        break;
                }
            }
        }catch (\danog\MadelineProto\Exception $e) {
            sleep(1);
            echo $e;
        }
    }