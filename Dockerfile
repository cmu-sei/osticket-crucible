FROM campbellsoftwaresolutions/osticket as builder

RUN apk add --no-cache bash

WORKDIR  /data/upload

COPY . .

RUN wget https://github.com/soundasleep/html2text/blob/master/html2text.php

RUN php -dphar.readonly=0 make.php build crucible

RUN mkdir output && cp crucible.phar output

RUN echo "curl.cainfo='/etc/ssl/certs/bundle.pem'" >> /usr/local/etc/php/conf.d/php-osticket.ini 

RUN mkdir -p ./assets/oauth/images/

COPY resources/sketch.gif ./assets/oauth/images/

COPY resources/logo-white.png ./assets/default/images/logo.png

COPY resources/logo-black.png ./scp/images/ost-logo.png

COPY resources/bootstrap-theme.tar.bz2  ./

RUN tar xvjf bootstrap-theme.tar.bz2

RUN ["chmod", "+x", "./entrypoint.sh"]

ENTRYPOINT ["./entrypoint.sh"]
CMD ["../bin/start.sh"]
