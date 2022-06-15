FROM debian:stretch

RUN apt-get update && apt-get install -y apache2
RUN apt-get install -y ca-certificates apt-transport-https wget curl cron htop

RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ stretch main" > /etc/apt/sources.list.d/php.list

RUN apt-get update
RUN apt-get install -y php7.4 php7.4-curl php7.4-mysql php7.4-bcmath php7.4-mbstring php7.4-xml php7.4-soap php7.4-gd php7.4-zip

RUN a2enmod php7.4
RUN a2enmod rewrite

ADD docker/apache/myorders.conf /etc/apache2/sites-available/myorders.conf
ADD docker/crontab/root /var/spool/cron/crontabs/root


RUN chmod 0644 /var/spool/cron/crontabs/root
RUN crontab /var/spool/cron/crontabs/root

RUN a2ensite myorders.conf
RUN a2dissite 000-default.conf
RUN a2disconf other-vhosts-access-log

COPY docker/start.sh /
RUN chmod a+x /start.sh
CMD ["/start.sh"]
