FROM jrottenberg/ffmpeg:4.0-ubuntu

RUN apt-get update -y && \
    apt-get clean && \
    apt-get install -y \
    python-software-properties \
    software-properties-common && \
    LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php && \
    apt-get update -y && \
    apt-get install -y \
    php7.2 php7.2-dev php7.2-fpm php7.2-curl \
    php7.2-xml php7.2-zip php7.2-gmp php7.2-cli \
    php7.2-mbstring git -y && \
    apt-get clean
WORKDIR /root
RUN apt-get install -y \
    libopus-dev \
    libssl-dev \
    build-essential \
    php$(echo "<?php echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" | php)-dev
RUN git clone https://github.com/CopernicaMarketingSoftware/PHP-CPP && \
    cd PHP-CPP && make -j$(nproc) && make install && cd .. && \
    git clone --recursive https://github.com/danog/php-libtgvoip && \
    cd php-libtgvoip && make -j$(nproc) && make install && cd .. && \
    git clone https://github.com/danog/PrimeModule-ext && \
    cd PrimeModule-ext && make -j$(nproc) && make install && cd ..
ADD ./php/ .
ENTRYPOINT ["/usr/bin/php"]