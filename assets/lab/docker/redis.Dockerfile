# Use Debian bookworm to match the local setup exactly
FROM debian:bookworm

# Install Redis from the setup instructions
RUN apt-get update && \
    apt-get install -y redis-server

# Copy Redis configuration
COPY ../cache/redis.conf /redis.conf

# Copy production data
COPY ../cache/production_data.redis /production_data.redis

# Create startup script that starts Redis and loads data
RUN echo '#!/bin/bash' > /startup.sh && \
    echo 'redis-server /redis.conf &' >> /startup.sh && \
    echo 'sleep 5' >> /startup.sh && \
    echo 'redis-cli < /production_data.redis' >> /startup.sh && \
    echo 'redis-cli shutdown' >> /startup.sh && \
    echo 'exec redis-server /redis.conf' >> /startup.sh && \
    chmod +x /startup.sh

# Expose Redis port
EXPOSE 6379

# Run startup script
CMD ["/startup.sh"]
