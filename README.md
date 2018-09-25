## DodoFM
1. Загружаем docker образ
```bash
sudo docker pull slavikmipt/dodofm:latest
```
2. Запускаем, потребуется api_id и api_hash с my.telegram.org и аккаунт Telegram
```bash
sudo docker run --name echomsk --restart always -ti slavikmipt/dodofm:latest lyradio.php http://ice912.echo.msk.ru:9120/24.aac
```
### Потоки:
- RockFM http://nashe.streamr.ru/rock-128.mp3
- Эхо Москвы http://ice912.echo.msk.ru:9120/24.aac
- Бизнес FM http://bfm.hostingradio.ru:8004/fm
- Comedy http://ic2.101.ru:8000/e8_1
### Контакты
- Канал с радио в Telegram: https://t.me/radio_dodofm
- Канал разработчика https://t.me/mediatube_stream
- Чат https://t.me/mediatube_chat

[@SlavikMIPT], [@LyoSu], [@MadelineProto]
