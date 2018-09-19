<?php
chdir(__DIR__);

$file = $argv[1];
echo $file;
exec("mkfifo $file");
exec("ffmpeg -itsoffset 1 -i http://air.radiorecord.ru:805/rr_320 -f s16le -ac 1 -ar 48000 -acodec pcm_s16le pipe:1 > $file &");
#unlink($file);
