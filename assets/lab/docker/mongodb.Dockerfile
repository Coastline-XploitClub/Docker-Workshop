# Use Debian bookworm to match the local setup exactly
FROM debian:bookworm

# Install prerequisites
RUN apt-get update && \
    apt-get install -y \
    curl \
    gnupg

# Install MongoDB from the setup instructions
# Import MongoDB GPG key
RUN curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc | gpg -o /usr/share/keyrings/mongodb-server-7.0.gpg --dearmor

# Add MongoDB repository
RUN echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] https://repo.mongodb.org/apt/debian bookworm/mongodb-org/7.0 main" > /etc/apt/sources.list.d/mongodb-org-7.0.list

# Update and install MongoDB
RUN apt-get update && \
    apt-get install -y mongodb-org mongodb-mongosh

# Create data directory
RUN mkdir -p /database_data

# Copy database initialization scripts
COPY ../database/schema.js /schema.js
COPY ../database/seed.js /seed.js

# Create startup script that runs MongoDB and loads data
RUN echo '#!/bin/bash' > /startup.sh && \
    echo 'mongod --dbpath /database_data --bind_ip_all &' >> /startup.sh && \
    echo 'sleep 10' >> /startup.sh && \
    echo 'mongosh taskapp < /schema.js' >> /startup.sh && \
    echo 'mongosh taskapp < /seed.js' >> /startup.sh && \
    echo 'mongod --dbpath /database_data --bind_ip_all --shutdown' >> /startup.sh && \
    echo 'exec mongod --dbpath /database_data --bind_ip_all' >> /startup.sh && \
    chmod +x /startup.sh

# Expose MongoDB port
EXPOSE 27017

# Run startup script
CMD ["/startup.sh"]
