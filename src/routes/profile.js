const express = require('express');
const router = express.Router();
const { getProfile, upsertProfile } = require('../controllers/profileController');
const authMiddleware = require('../middleware/auth');
const { uploadProfile } = require('../middleware/upload');

/**
 * @route   GET /api/profile
 * @desc    Get profile (visi, misi, struktur organisasi)
 * @access  Public
 */
router.get('/', getProfile);

/**
 * @route   PUT /api/profile
 * @desc    Create or update profile
 * @access  Private
 */
router.put(
  '/',
  authMiddleware,
  uploadProfile.fields([
    { name: 'logo', maxCount: 1 },
    { name: 'struktur_organisasi_gambar', maxCount: 1 },
  ]),
  upsertProfile
);

module.exports = router;
