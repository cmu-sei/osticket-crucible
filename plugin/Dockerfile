FROM devinsolutions/osticket:1.14.1 as builder

RUN apk add --no-cache --update bash
COPY . .
RUN php -dphar.readonly=0 make.php build crucible

FROM alpine:latest as plugin
RUN mkdir /plugin
COPY --from=builder /data/upload/crucible.phar /