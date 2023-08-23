FROM sylius/standard:1.11-traditional-alpine

ARG USER_ID

# for composer
RUN apk add git

## edit the root users default group to have the id of the
## host user
RUN apk add sudo
RUN apk add shadow
RUN usermod -u ${USER_ID} www-data
RUN groupmod -g ${USER_ID} www-data