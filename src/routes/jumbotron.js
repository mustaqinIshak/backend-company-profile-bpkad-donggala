const express = require('express');
const router = express.Router();
const {
  getAllJumbotron,
  getJumbotronById,
  createJumbotron,
  updateJumbotron,
  deleteJumbotron,
} = require('../controllers/jumbotronController');
const authMiddleware = require('../middleware/auth');
const { uploadJumbotron } = require('../middleware/upload');

/**
 * @route   GET /api/jumbotron
 * @desc    Get all jumbotron (with optional ?aktif=true filter)
 * @access  Public
 */
router.get('/', getAllJumbotron);

/**
 * @route   GET /api/jumbotron/:id
 * @desc    Get jumbotron by ID
 * @access  Public
 */
router.get('/:id', getJumbotronById);

/**
 * @route   POST /api/jumbotron
 * @desc    Upload new jumbotron
 * @access  Private
 */
router.post('/', authMiddleware, uploadJumbotron.single('gambar'), createJumbotron);

/**
 * @route   PUT /api/jumbotron/:id
 * @desc    Update jumbotron
 * @access  Private
 */
router.put('/:id', authMiddleware, uploadJumbotron.single('gambar'), updateJumbotron);

/**
 * @route   DELETE /api/jumbotron/:id
 * @desc    Delete jumbotron
 * @access  Private
 */
router.delete('/:id', authMiddleware, deleteJumbotron);

module.exports = router;
