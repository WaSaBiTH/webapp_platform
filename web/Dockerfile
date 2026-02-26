FROM php:8.2-apache

# Install MariaDB client, extensions, rclone for backup, and cron
RUN apt-get update && apt-get install -y \
    mariadb-client \
    rclone \
    cron \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite

# Setup a dummy rclone config if it doesn't exist so rclone doesn't crash on boot 
# (real one will be mounted from host)
RUN mkdir -p /root/.config/rclone

# Copy the crontab file
COPY crontab /etc/cron.d/backup-cron
RUN chmod 0644 /etc/cron.d/backup-cron && crontab /etc/cron.d/backup-cron

# Copy a startup script to run cron alongside apache
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
