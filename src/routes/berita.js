const express = require('express');
const router = express.Router();
const {
  getAllBerita,
  getBeritaBySlug,
  getBeritaById,
  createBerita,
  updateBerita,
  deleteBerita,
} = require('../controllers/beritaController');
const authMiddleware = require('../middleware/auth');
const { uploadBerita } = require('../middleware/upload');

/**
 * @route   GET /api/berita
 * @desc    Get all berita with pagination (?page=1&limit=10&kategori=&aktif=true)
 * @access  Public
 */
router.get('/', getAllBerita);

/**
 * @route   GET /api/berita/slug/:slug
 * @desc    Get berita by slug
 * @access  Public
 */
router.get('/slug/:slug', getBeritaBySlug);

/**
 * @route   GET /api/berita/:id
 * @desc    Get berita by ID
 * @access  Public
 */
router.get('/:id', getBeritaById);

/**
 * @route   POST /api/berita
 * @desc    Create berita with optional image upload
 * @access  Private
 */
router.post('/', authMiddleware, uploadBerita.single('gambar'), createBerita);

/**
 * @route   PUT /api/berita/:id
 * @desc    Update berita
 * @access  Private
 */
router.put('/:id', authMiddleware, uploadBerita.single('gambar'), updateBerita);

/**
 * @route   DELETE /api/berita/:id
 * @desc    Delete berita
 * @access  Private
 */
router.delete('/:id', authMiddleware, deleteBerita);

module.exports = router;
