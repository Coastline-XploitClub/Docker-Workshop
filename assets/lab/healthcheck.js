const http = require('http');

const options = {
  host: 'localhost',
  port: process.env.PORT || 3000,
  timeout: 2000,
  path: '/health'
};

const request = http.request(options, (res) => {
  if (res.statusCode === 200) {
    console.log('Health check passed');
    process.exit(0);
  } else {
    console.log(`Health check failed with status: ${res.statusCode}`);
    process.exit(1);
  }
});

request.on('error', (err) => {
  console.log(`Health check failed with error: ${err.message}`);
  process.exit(1);
});

request.on('timeout', () => {
  console.log('Health check timed out');
  request.abort();
  process.exit(1);
});

request.end();