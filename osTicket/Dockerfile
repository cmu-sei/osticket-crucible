#Build Plugin Stage
FROM campbellsoftwaresolutions/osticket:1.12 as build

RUN apk add --no-cache --update bash

WORKDIR /data/upload

COPY osTicket/ .

COPY plugin/ .

RUN php -dphar.readonly=0 make.php build crucible && mv crucible.phar /tmp/osticket-plugin.phar

# osTicket Image
FROM campbellsoftwaresolutions/osticket:1.12 as prod
COPY --from=build /tmp/osticket-plugin.phar /tmp/osticket-plugin.phar

# Remove SAMEORIGIN from header requirements
RUN sed -i 's/header("Content-Security-Policy: frame-ancestors ".\$cfg->getAllowIframes().";");//g' /data/upload/include/client/header.inc.php && \
    sed -i 's/header("Content-Security-Policy: frame-ancestors ".\$cfg->getAllowIframes().";");//g' /data/upload/include/staff/header.inc.php && \
    sed -i 's/header("Content-Security-Policy: frame-ancestors ".\$cfg->getAllowIframes().";");//g' /data/upload/include/staff/login.header.php

# Update thread class to prevent collaboration event logging instead of using plugin
# - Setting $event to false
RUN sed -i 's/function addCollaborator(\$user, \$vars, \&\$errors, \$event=true) {/function addCollaborator(\$user, \$vars, \&\$errors, \$event=true) {\n        \$event=false;/g' /data/upload/include/class.thread.php

# Change thread class to use the more meaningful getAccount()->getUserName() instead of getFullName()
RUN sed -i 's/\$username = \$thisclient->getFullName();/\$username = \$thisclient->getAccount()->getUserName();/g' /data/upload/include/class.thread.php

RUN apk add --no-cache --update bash

WORKDIR /data/upload

COPY osTicket/ .

COPY plugin/ .

RUN curl -s https://github.com/soundasleep/html2text/blob/master/html2text.php --output /tmp/Html2Text.php

RUN echo "curl.cainfo='/etc/ssl/certs/bundle.pem'" >> /usr/local/etc/php/conf.d/php-osticket.ini

RUN mkdir -p ./assets/oauth/images/

COPY ./osTicket/resources/sketch.gif ./assets/oauth/images/

COPY ./osTicket/resources/logo-white.png ./assets/default/images/logo.png

COPY ./osTicket/resources/logo-black.png ./scp/images/ost-logo.png

RUN ["chmod", "+x", "./entrypoint.sh"]

ENTRYPOINT ["./entrypoint.sh"]
CMD ["/data/bin/start.sh"]
