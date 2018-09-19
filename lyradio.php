<?php
chdir(__DIR__);
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
$url_stream = "http://air.radiorecord.ru:805/rr_320";
$me = $MadelineProto->get_self();
if( $me === false ){
    $MadelineProto = new \danog\MadelineProto\API('session.madeline');
    $MadelineProto->start();
    $me = $MadelineProto->get_self();
}
    $times = [];
    $calls = [];

    $me = $MadelineProto->get_self();
    print_r($me);
    $lastUpdate = $MadelineProto->API->get_updates();

    //file_put_contents("log.txt", json_encode($lastUpdate,JSON_PRETTY_PRINT), FILE_APPEND | LOCK_EX);
    
    $offset = end($lastUpdate)['update_id']+1;

    while (1) {
        $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 5000, 'timeout' => 0]);
         

        foreach ($calls as $key => $call) {
            if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                unset($calls[$key]);
            } elseif (isset($times[$call->getOtherID()]) && $times[$call->getOtherID()][0] < time()) {
                $times[$call->getOtherID()][0] += 10;
                try {
                    $message = "<b>Слушателей:</b> ".count($calls)."";

                    $MadelineProto->messages->editMessage(['id' => $times[$call->getOtherID()][1], 'peer' => $call->getOtherID(), 'message' => $message, 'parse_mode' => 'HTML' ]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    echo $e;
                }
            }
        }

        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;
            switch ($update['update']['_']) {
                case 'updateNewMessage':
                    if ($update['update']['message']['out'] || $update['update']['message']['to_id']['_'] !== 'peerUser' || !isset($update['update']['message']['from_id'])) {
                        continue;
                    }
                    
                    if ( isset($update['update']['message']['message']) ) {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => "Привет✌️\nЯ радио бот в телеграме.\nПозвони мне.\n\n<b>Разработчики:</b> @LyoSU, @SlavikMIPT\nОсновано на @MadelineProto", 'parse_mode' => 'HTML']);
                    }
                    break;
                case 'updatePhoneCall':

                    if (is_object($update['update']['phone_call']) && isset($update['update']['phone_call']->madeline) && $update['update']['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {    
                        $times[$update['update']['phone_call']->getOtherID()] = 
                        [time(), $MadelineProto->messages->sendMessage(
                        ['peer' => $update['update']['phone_call']->getOtherID(),
                         'message' => 'Слушателей: '.count($calls).PHP_EOL.PHP_EOL])['id']];
                        $file = "/root/".$update['update']['phone_call']->getOtherID().".raw";
                        exec("php PlayRadio.php $file $url_stream > /dev/null 2>&1 &");
                        $start_time = time();
                        while (!file_exists($file)) {
                            if ((time() - $start_time) > 10) {
                                break;
                            }
                        }
                        $update['update']['phone_call']->accept()->play($file);
                        unlink($file);

                        $controller = $calls[$update['update']['phone_call']->getOtherID()] = $update['update']['phone_call'];
                        $controller->configuration['shared_config']['audio_init_bitrate'] = 80*1000; // Audio bitrate set when the call is started
                        $controller->configuration['shared_config']['audio_max_bitrate']  = 110*1000; // Maximum audio bitrate
                        $controller->configuration['shared_config']['audio_min_bitrate']  = 80*1000; // Minimum audio bitrate
                        $controller->configuration['shared_config']['audio_bitrate_step_decr']  = 1000; // Decreasing step: when libtgvoip has to lower the bitrate, it decreases it `audio_bitrate_step_decr` bps at a time
                        $controller->configuration['shared_config']['audio_bitrate_step_incr']  = 1000; // Increasing step: when libtgvoip has to make the bitrate higher, it increases it `audio_bitrate_step_decr` bps at a time
                        $controller->parseConfig();
                    }
                    break;
           }
        }
    }
