require('dotenv').config();
const express = require('express');
const cors = require('cors');
const path = require('path');

const authRoutes = require('./routes/auth');
const profileRoutes = require('./routes/profile');
const jumbotronRoutes = require('./routes/jumbotron');
const organisasiRoutes = require('./routes/organisasi');
const beritaRoutes = require('./routes/berita');
const layananRoutes = require('./routes/layanan');
const kontakRoutes = require('./routes/kontak');
const { generalLimiter, authLimiter, kontakLimiter } = require('./middleware/rateLimiter');

const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static uploaded files
const uploadPath = process.env.UPLOAD_PATH || './uploads';
app.use('/uploads', express.static(path.resolve(uploadPath)));

// Apply general rate limiting to all API routes
app.use('/api/', generalLimiter);

// API Routes
app.use('/api/auth', authLimiter, authRoutes);
app.use('/api/profile', profileRoutes);
app.use('/api/jumbotron', jumbotronRoutes);
app.use('/api/organisasi', organisasiRoutes);
app.use('/api/berita', beritaRoutes);
app.use('/api/layanan', layananRoutes);
app.use('/api/kontak', kontakRoutes);

// Health check
app.get('/api/health', (req, res) => {
  res.status(200).json({
    success: true,
    message: 'BPKAD Donggala API is running.',
    timestamp: new Date().toISOString(),
  });
});

// Root
app.get('/', (req, res) => {
  res.status(200).json({
    success: true,
    message: 'Selamat datang di API Company Profile BPKAD Kabupaten Donggala.',
    version: '1.0.0',
    endpoints: {
      auth: '/api/auth',
      profile: '/api/profile',
      jumbotron: '/api/jumbotron',
      organisasi: '/api/organisasi',
      berita: '/api/berita',
      layanan: '/api/layanan',
      kontak: '/api/kontak',
    },
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: 'Endpoint tidak ditemukan.',
  });
});

// Error handler
app.use((err, req, res, next) => {
  console.error(err.stack);

  // Handle multer errors
  if (err.code === 'LIMIT_FILE_SIZE') {
    return res.status(400).json({
      success: false,
      message: 'Ukuran file terlalu besar.',
    });
  }

  if (err.message && err.message.includes('Hanya file')) {
    return res.status(400).json({
      success: false,
      message: err.message,
    });
  }

  return res.status(500).json({
    success: false,
    message: 'Terjadi kesalahan pada server.',
    error: process.env.NODE_ENV === 'development' ? err.message : undefined,
  });
});

module.exports = app;
