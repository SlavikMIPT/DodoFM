<?php
chdir(__DIR__);

$file = $argv[1];
$url = $argv[2];
echo $file;
echo $url;
exec("mkfifo $file");
exec("ffmpeg -itsoffset 1 -i $url -f s16le -ac 1 -ar 48000 -acodec pcm_s16le pipe:1 > $file &");
