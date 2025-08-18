const express = require('express');
const morgan = require('morgan');
const app = express();

// Security demonstration: logging sensitive information
app.use(morgan('combined'));

app.get('/', (req, res) => {
  res.json({ 
    message: 'Vulnerable Docker App',
    user: process.getuid(),
    env: process.env,
    version: process.version,
    platform: process.platform
  });
});

app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Intentionally vulnerable endpoint
app.get('/debug', (req, res) => {
  res.json({
    environment: process.env,
    cwd: process.cwd(),
    user: process.getuid(),
    groups: process.getgroups()
  });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
  console.log(`Environment: ${JSON.stringify(process.env)}`);
});