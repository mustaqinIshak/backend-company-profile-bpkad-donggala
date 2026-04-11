const express = require('express');
const router = express.Router();
const {
  createKontak,
  getAllKontak,
  getKontakById,
  updateStatusKontak,
  deleteKontak,
} = require('../controllers/kontakController');
const authMiddleware = require('../middleware/auth');
const { kontakLimiter } = require('../middleware/rateLimiter');

/**
 * @route   POST /api/kontak
 * @desc    Send contact message
 * @access  Public
 */
router.post('/', kontakLimiter, createKontak);

/**
 * @route   GET /api/kontak
 * @desc    Get all contact messages (admin only)
 * @access  Private
 */
router.get('/', authMiddleware, getAllKontak);

/**
 * @route   GET /api/kontak/:id
 * @desc    Get contact message by ID (auto marks as read)
 * @access  Private
 */
router.get('/:id', authMiddleware, getKontakById);

/**
 * @route   PATCH /api/kontak/:id/status
 * @desc    Update contact message status
 * @access  Private
 */
router.patch('/:id/status', authMiddleware, updateStatusKontak);

/**
 * @route   DELETE /api/kontak/:id
 * @desc    Delete contact message
 * @access  Private
 */
router.delete('/:id', authMiddleware, deleteKontak);

module.exports = router;
