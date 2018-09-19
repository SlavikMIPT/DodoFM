FROM jrottenberg/ffmpeg:4.0-ubuntu
RUN apt-get update -y && apt-get clean
RUN apt-get install --fix-missing -y python-software-properties software-properties-common
RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
RUN apt-get update -y && apt-get clean
RUN apt-get install -y php7.2 php7.2-dev php7.2-fpm php7.2-curl php7.2-xml php7.2-zip php7.2-gmp php7.2-cli php7.2-mbstring git -y && apt-get clean
ENV HOME /root
########## ENV ##########
ADD . /root
WORKDIR /root
RUN apt-get install -y libopus-dev libssl-dev build-essential php$(echo "<?php echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" | php)-dev 
RUN git clone https://github.com/CopernicaMarketingSoftware/PHP-CPP && cd PHP-CPP && make -j$(nproc) && make install && cd ..
RUN git clone https://github.com/danog/PrimeModule-ext && cd PrimeModule-ext && make -j$(nproc) && make install
ENTRYPOINT ["/bin/bash"]
